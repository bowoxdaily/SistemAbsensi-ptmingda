<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\WorkSchedule;
use App\Models\OfficeSetting;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display employee attendance page
     */
    public function index()
    {
        // Check if user has employee data
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            // Redirect to admin attendance if user doesn't have employee data
            return redirect()->route('admin.attendance.index')
                ->with('info', 'Anda tidak memiliki data karyawan. Silakan gunakan panel admin.');
        }

        return view('employee.attendance.index');
    }

    /**
     * Get current employee data
     */
    public function getCurrentEmployee()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi'
                ], 401);
            }

            $employee = Employee::where('user_id', $user->id)
                ->with(['department', 'position', 'workSchedule'])
                ->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan untuk user ini. Silakan hubungi administrator.'
                ], 404);
            }

            // Format shift info
            $employee->shift_type = $employee->workSchedule ? $employee->workSchedule->name : '-';
            if ($employee->workSchedule) {
                $startTime = Carbon::parse($employee->workSchedule->start_time)->format('H:i');
                $endTime = Carbon::parse($employee->workSchedule->end_time)->format('H:i');
                $employee->shift_type .= ' (' . $startTime . ' - ' . $endTime . ')';
            }

            return response()->json([
                'success' => true,
                'data' => $employee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's attendance for current employee
     */
    public function getTodayAttendance()
    {
        try {
            $user = Auth::user();
            $employee = Employee::where('user_id', $user->id)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan'
                ], 404);
            }

            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', today())
                ->first();

            return response()->json([
                'success' => true,
                'data' => $attendance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data absensi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check in with face detection
     */
    public function checkIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|string', // Base64 image
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric',
            'is_mocked' => 'nullable|boolean',
            'fake_gps_warnings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi'
                ], 401);
            }

            $employee = Employee::where('user_id', $user->id)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan untuk user ini'
                ], 404);
            }

            // Validate location if enforcement is enabled
            $officeSetting = OfficeSetting::get();
            if ($officeSetting->enforce_location) {
                $isWithinRadius = $officeSetting->isWithinRadius(
                    $request->latitude,
                    $request->longitude
                );

                if (!$isWithinRadius) {
                    $distance = OfficeSetting::calculateDistance(
                        $officeSetting->latitude,
                        $officeSetting->longitude,
                        $request->latitude,
                        $request->longitude
                    );

                    return response()->json([
                        'success' => false,
                        'message' => 'Anda berada di luar area kantor. Jarak Anda: ' . round($distance) . ' meter dari kantor (maksimal: ' . $officeSetting->radius_meters . ' meter)',
                        'distance' => round($distance),
                        'max_radius' => $officeSetting->radius_meters
                    ], 403);
                }
            }

            // Check if already checked in today
            $existingAttendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', today())
                ->first();

            if ($existingAttendance && $existingAttendance->check_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan check-in hari ini pada ' . $existingAttendance->check_in
                ], 400);
            }

            // Check if employee is on leave, sick, or has permission today
            if ($existingAttendance) {
                $blockedStatuses = ['cuti', 'leave', 'izin', 'sick', 'sakit', 'alpha', 'absent'];
                if (in_array(strtolower($existingAttendance->status), $blockedStatuses)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda tidak dapat melakukan check-in karena sedang dalam status: ' . strtoupper($existingAttendance->status)
                    ], 403);
                }
            }

            // Save photo
            $photoPath = $this->saveBase64Image($request->photo, 'attendance/check-in');

            // Get work schedule to check if late
            $checkInTime = now();
            // Get employee's work schedule
            $schedule = $employee->workSchedule;

            $lateMinutes = 0;
            $status = 'hadir';

            if ($schedule && $schedule->start_time) {
                $scheduledTime = Carbon::parse($schedule->start_time);
                $lateToleranceTime = $scheduledTime->copy()->addMinutes($schedule->late_tolerance ?? 0);

                if ($checkInTime->gt($lateToleranceTime)) {
                    $lateMinutes = $checkInTime->diffInMinutes($scheduledTime);
                    $status = 'terlambat';
                }
            }

            // Create or update attendance
            $attendance = Attendance::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'attendance_date' => today()
                ],
                [
                    'check_in' => $checkInTime->format('H:i:s'),
                    'photo_in' => $photoPath,
                    'location_in' => $request->latitude . ',' . $request->longitude,
                    'status' => $status,
                    'late_minutes' => $lateMinutes,
                    'gps_accuracy_in' => $request->accuracy,
                    'is_mocked_in' => $request->is_mocked ?? false,
                    'gps_warnings_in' => $request->fake_gps_warnings ? json_encode($request->fake_gps_warnings) : null,
                    'is_suspicious_in' => !empty($request->fake_gps_warnings),
                ]
            );

            // Load employee relation for WhatsApp
            $attendance->load('employee');

            // Send WhatsApp notification
            try {
                $whatsappService = new WhatsAppService();
                $whatsappService->sendCheckinNotification($attendance);
            } catch (\Exception $e) {
                Log::warning('Failed to send WhatsApp check-in notification: ' . $e->getMessage());
                // Don't fail the check-in if WhatsApp fails
            }

            return response()->json([
                'success' => true,
                'message' => 'Check-in berhasil',
                'data' => [
                    'attendance' => $attendance,
                    'late_minutes' => $lateMinutes,
                    'status' => $status
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan check-in: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check out with face detection
     */
    public function checkOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|string', // Base64 image
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric',
            'is_mocked' => 'nullable|boolean',
            'fake_gps_warnings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak terautentikasi'
                ], 401);
            }

            $employee = Employee::where('user_id', $user->id)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan untuk user ini'
                ], 404);
            }

            // Validate location if enforcement is enabled
            $officeSetting = OfficeSetting::get();
            if ($officeSetting->enforce_location) {
                $isWithinRadius = $officeSetting->isWithinRadius(
                    $request->latitude,
                    $request->longitude
                );

                if (!$isWithinRadius) {
                    $distance = OfficeSetting::calculateDistance(
                        $officeSetting->latitude,
                        $officeSetting->longitude,
                        $request->latitude,
                        $request->longitude
                    );

                    return response()->json([
                        'success' => false,
                        'message' => 'Anda berada di luar area kantor. Jarak Anda: ' . round($distance) . ' meter dari kantor (maksimal: ' . $officeSetting->radius_meters . ' meter)',
                        'distance' => round($distance),
                        'max_radius' => $officeSetting->radius_meters
                    ], 403);
                }
            }

            // Check if checked in today
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', today())
                ->first();

            if (!$attendance || !$attendance->check_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum melakukan check-in hari ini'
                ], 400);
            }

            if ($attendance->check_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan check-out hari ini pada ' . $attendance->check_out
                ], 400);
            }

            // Check if employee is on leave, sick, or has permission today
            $blockedStatuses = ['cuti', 'leave', 'izin', 'sick', 'sakit', 'alpha', 'absent'];
            if (in_array(strtolower($attendance->status), $blockedStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak dapat melakukan check-out karena sedang dalam status: ' . strtoupper($attendance->status)
                ], 403);
            }

            // Validate check-out time based on work schedule
            $schedule = $employee->workSchedule;

            // Check if schedule exists
            if (!$schedule || !$schedule->end_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jadwal shift tidak ditemukan. Hubungi admin.'
                ], 403);
            }

            try {
                $now = Carbon::now();

                // Parse end_time using Carbon (same as work-schedule.blade.php)
                $endTime = Carbon::parse($schedule->end_time);
                $endTimeFormatted = $endTime->format('H:i');

                // Create scheduled end time for today
                $scheduledEndTime = Carbon::today()->setTime($endTime->hour, $endTime->minute, 0);

                // Block checkout if current time is before scheduled end time
                if ($now->lessThan($scheduledEndTime)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Belum saatnya check-out',
                        'shift_end' => $endTimeFormatted,
                        'current_time' => $now->format('H:i')
                    ], 403);
                }
            } catch (\Exception $e) {
                // If parsing fails, log and allow checkout (fail-safe)
                Log::warning('Failed to parse work schedule end_time: ' . $e->getMessage(), [
                    'schedule_id' => $schedule->id,
                    'end_time' => $schedule->end_time
                ]);
            }

            // Save photo
            $photoPath = $this->saveBase64Image($request->photo, 'attendance/check-out');

            // Update attendance
            $attendance->update([
                'check_out' => now()->format('H:i:s'),
                'photo_out' => $photoPath,
                'location_out' => $request->latitude . ',' . $request->longitude,
                'gps_accuracy_out' => $request->accuracy,
                'is_mocked_out' => $request->is_mocked ?? false,
                'gps_warnings_out' => $request->fake_gps_warnings ? json_encode($request->fake_gps_warnings) : null,
                'is_suspicious_out' => !empty($request->fake_gps_warnings),
            ]);

            // Load employee relation for WhatsApp
            $attendance->load('employee');

            // Send WhatsApp notification
            try {
                $whatsappService = new WhatsAppService();
                $whatsappService->sendCheckoutNotification($attendance);
            } catch (\Exception $e) {
                Log::warning('Failed to send WhatsApp check-out notification: ' . $e->getMessage());
                // Don't fail the check-out if WhatsApp fails
            }

            return response()->json([
                'success' => true,
                'message' => 'Check-out berhasil',
                'data' => $attendance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan check-out: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance history for current employee
     */
    public function history(Request $request)
    {
        try {
            $user = Auth::user();
            $employee = Employee::where('user_id', $user->id)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan'
                ], 404);
            }

            $perPage = $request->get('per_page', 10);
            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);

            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
                ->orderBy('attendance_date', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $attendances
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat absensi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance summary for current employee
     */
    public function summary(Request $request)
    {
        try {
            $user = Auth::user();
            $employee = Employee::where('user_id', $user->id)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan'
                ], 404);
            }

            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);

            $query = Attendance::where('employee_id', $employee->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month);

            $summary = [
                'total' => $query->count(),
                'hadir' => $query->clone()->where('status', 'hadir')->count(),
                'terlambat' => $query->clone()->where('status', 'terlambat')->count(),
                'izin' => $query->clone()->where('status', 'izin')->count(),
                'sakit' => $query->clone()->where('status', 'sakit')->count(),
                'alpha' => $query->clone()->where('status', 'alpha')->count(),
                'cuti' => $query->clone()->where('status', 'cuti')->count(),
                'total_late_minutes' => $query->sum('late_minutes'),
            ];

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil ringkasan absensi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display attendance history page
     */
    public function historyPage(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return redirect()->route('admin.attendance.index')
                ->with('info', 'Anda tidak memiliki data karyawan. Silakan gunakan panel admin.');
        }

        // Get filter parameters - convert to integer to avoid Carbon error
        $month = (int) $request->get('month', date('m'));
        $year = (int) $request->get('year', date('Y'));
        $status = $request->get('status');

        // Build query
        $query = Attendance::where('employee_id', $employee->id)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month);

        // Apply status filter if provided
        if ($status) {
            $query->where('status', $status);
        }

        // Get attendance records with pagination
        $attendances = $query->orderBy('attendance_date', 'desc')
            ->paginate(20)
            ->appends($request->all());

        // Calculate statistics for the filtered period
        $stats = [
            'hadir' => Attendance::where('employee_id', $employee->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
                ->where('status', 'hadir')
                ->count(),
            'terlambat' => Attendance::where('employee_id', $employee->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
                ->where('status', 'terlambat')
                ->count(),
            'cuti' => Attendance::where('employee_id', $employee->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
                ->where('status', 'cuti')
                ->count(),
            'izin' => Attendance::where('employee_id', $employee->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
                ->where('status', 'izin')
                ->count(),
            'sakit' => Attendance::where('employee_id', $employee->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
                ->where('status', 'sakit')
                ->count(),
            'alpha' => Attendance::where('employee_id', $employee->id)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $month)
                ->where('status', 'alpha')
                ->count(),
        ];

        return view('employee.attendance.history', compact('attendances', 'stats', 'month', 'year', 'employee'));
    }

    /**
     * Get attendance detail
     */
    public function detail($id)
    {
        try {
            $user = Auth::user();
            $employee = Employee::where('user_id', $user->id)->first();

            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data karyawan tidak ditemukan'
                ], 404);
            }

            $attendance = Attendance::where('id', $id)
                ->where('employee_id', $employee->id)
                ->first();

            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data absensi tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $attendance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail absensi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save base64 image to storage
     */
    private function saveBase64Image($base64String, $path)
    {
        // Remove data:image/png;base64, or data:image/jpeg;base64,
        $image = str_replace('data:image/png;base64,', '', $base64String);
        $image = str_replace('data:image/jpeg;base64,', '', $image);
        $image = str_replace('data:image/jpg;base64,', '', $image);
        $image = str_replace(' ', '+', $image);

        $imageName = uniqid() . '_' . time() . '.png';
        $imagePath = $path . '/' . $imageName;

        Storage::disk('public')->put($imagePath, base64_decode($image));

        return $imagePath;
    }
}
