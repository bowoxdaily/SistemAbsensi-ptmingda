<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Karyawans;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    /**
     * Display attendance dashboard
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $search = $request->get('search');
        $dateFrom = $request->get('date_from', now()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $status = $request->get('status');
        $department = $request->get('department');
        $perPage = $request->get('per_page', 10);

        // Build query
        $query = Attendance::with(['employee.department', 'employee.subDepartment', 'employee.position', 'employee.workSchedule']);

        // Apply filters
        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($department) {
            $query->whereHas('employee', function ($q) use ($department) {
                $q->where('department_id', $department);
            });
        }

        $query->whereBetween('attendance_date', [$dateFrom, $dateTo]);

        // Get paginated results
        // If per_page is 'all', get all records, otherwise paginate
        if ($perPage === 'all') {
            $attendances = $query->orderBy('attendance_date', 'desc')
                ->orderBy('check_in', 'desc')
                ->get();
            // Convert to paginator for compatibility with blade
            $attendances = new \Illuminate\Pagination\LengthAwarePaginator(
                $attendances,
                $attendances->count(),
                $attendances->count(),
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $attendances = $query->orderBy('attendance_date', 'desc')
                ->orderBy('check_in', 'desc')
                ->paginate((int)$perPage)
                ->appends($request->all());
        }

        // Get statistics
        $stats = [
            'total' => Attendance::whereBetween('attendance_date', [$dateFrom, $dateTo])->count(),
            'hadir' => Attendance::where('status', 'hadir')->whereBetween('attendance_date', [$dateFrom, $dateTo])->count(),
            'terlambat' => Attendance::where('status', 'terlambat')->whereBetween('attendance_date', [$dateFrom, $dateTo])->count(),
            'izin' => Attendance::where('status', 'izin')->whereBetween('attendance_date', [$dateFrom, $dateTo])->count(),
            'alpha' => Attendance::where('status', 'alpha')->whereBetween('attendance_date', [$dateFrom, $dateTo])->count(),
        ];

        // Get departments for filter
        $departments = \App\Models\Department::orderBy('name')->get();

        return view('admin.attendance.index', compact('attendances', 'stats', 'departments', 'dateFrom', 'dateTo'));
    }

    /**
     * Get attendance list with filters
     */
    public function list(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search', '');
        $dateFrom = $request->get('date_from', now()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $status = $request->get('status', '');

        $attendances = Attendance::with(['employee.department', 'employee.position'])
            ->when($search, function ($query, $search) {
                return $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->orderBy('attendance_date', 'desc')
            ->orderBy('check_in', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }

    /**
     * Get today's attendance for specific employee
     */
    public function getTodayAttendance($employeeId)
    {
        $attendance = Attendance::where('employee_id', $employeeId)
            ->whereDate('attendance_date', today())
            ->first();

        return response()->json([
            'success' => true,
            'data' => $attendance
        ]);
    }

    /**
     * Get attendance by date for specific employee
     */
    public function getAttendanceByDate($employeeId, Request $request)
    {
        $date = $request->query('date', today()->format('Y-m-d'));

        $attendance = Attendance::where('employee_id', $employeeId)
            ->whereDate('attendance_date', $date)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $attendance,
            'date' => $date
        ]);
    }

    /**
     * Check in with face detection
     */
    public function checkIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'date' => 'nullable|date',
            'check_in_time' => 'nullable|date_format:H:i',
            'status' => 'nullable|in:hadir,terlambat,izin,sakit,alpha,cuti',
            'photo' => 'nullable|string', // Base64 image
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Use provided date or today
            $attendanceDate = $request->date ? Carbon::parse($request->date)->startOfDay() : today();
            $dateString = $attendanceDate->toDateString(); // Get Y-m-d format

            // Check if already checked in on this date
            $existingAttendance = Attendance::where('employee_id', $request->employee_id)
                ->whereDate('attendance_date', $dateString)
                ->first();

            if ($existingAttendance && $existingAttendance->check_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sudah melakukan check-in pada tanggal ini'
                ], 400);
            }

            // Get employee
            $employee = Karyawans::findOrFail($request->employee_id);

            // Get work schedule to check if late
            // For status like alpha, cuti, sakit, izin - check_in_time is optional
            if ($request->check_in_time) {
                // Clean up the time string (remove any whitespace)
                $timeString = trim($request->check_in_time);
                $combinedDateTime = $dateString . ' ' . $timeString;

                try {
                    $checkInTime = Carbon::createFromFormat('Y-m-d H:i', $combinedDateTime);
                } catch (\Exception $e) {
                    \Log::error('Check-in time parsing error:', [
                        'dateString' => $dateString,
                        'timeString' => $timeString,
                        'combined' => $combinedDateTime,
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception('Format waktu check-in tidak valid: ' . $e->getMessage());
                }
            } else {
                // If no time provided, use null (for alpha, cuti, sakit, izin)
                $checkInTime = null;
            }

            $schedule = $employee->workSchedule;

            $lateMinutes = 0;

            // Use status from request if provided, otherwise calculate automatically
            if ($request->status) {
                $status = $request->status;

                // Only calculate late minutes for 'hadir' and 'terlambat' status (and if checkInTime exists)
                if (in_array($status, ['hadir', 'terlambat']) && $schedule && $checkInTime) {
                    try {
                        // Get start time in H:i format (remove seconds if present)
                        $startTime = substr($schedule->start_time, 0, 5); // Get HH:MM only
                        $scheduledTime = Carbon::createFromFormat('Y-m-d H:i', $dateString . ' ' . $startTime);

                        if ($checkInTime->gt($scheduledTime)) {
                            $lateMinutes = $scheduledTime->diffInMinutes($checkInTime, false);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Schedule time parsing error:', [
                            'dateString' => $dateString,
                            'start_time' => $schedule->start_time,
                            'startTime' => $startTime ?? null,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } else {
                // Auto-calculate status based on schedule (only if checkInTime exists)
                $status = 'hadir';

                if ($schedule && $checkInTime) {
                    try {
                        // Get start time in H:i format (remove seconds if present)
                        $startTime = substr($schedule->start_time, 0, 5); // Get HH:MM only
                        $scheduledTime = Carbon::createFromFormat('Y-m-d H:i', $dateString . ' ' . $startTime);

                        if ($checkInTime->gt($scheduledTime)) {
                            $lateMinutes = $scheduledTime->diffInMinutes($checkInTime, false);
                            $status = 'terlambat';
                        }
                    } catch (\Exception $e) {
                        \Log::error('Schedule time parsing error:', [
                            'dateString' => $dateString,
                            'start_time' => $schedule->start_time,
                            'startTime' => $startTime ?? null,
                            'error' => $e->getMessage()
                        ]);
                        // Continue without late calculation if schedule parsing fails
                    }
                }
            }

            // Create or update attendance
            $attendance = Attendance::updateOrCreate(
                [
                    'employee_id' => $request->employee_id,
                    'attendance_date' => $dateString
                ],
                [
                    'check_in' => $checkInTime ? $checkInTime->format('H:i:s') : null,
                    'photo_in' => null, // Always null for manual input
                    'location_in' => null, // Always null for manual input
                    'status' => $status,
                    'late_minutes' => $lateMinutes,
                    'notes' => $request->notes,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Check-in berhasil dicatat',
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
            'employee_id' => 'required|exists:employees,id',
            'date' => 'nullable|date',
            'check_out_time' => 'nullable|date_format:H:i',
            'photo' => 'nullable|string', // Base64 image
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Use provided date or today
            $attendanceDate = $request->date ? Carbon::parse($request->date)->startOfDay() : today();
            $dateString = $attendanceDate->toDateString(); // Get Y-m-d format

            // Check if checked in on this date
            $attendance = Attendance::where('employee_id', $request->employee_id)
                ->whereDate('attendance_date', $dateString)
                ->first();

            if (!$attendance || !$attendance->check_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Belum melakukan check-in pada tanggal ini'
                ], 400);
            }

            if ($attendance->check_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sudah melakukan check-out pada tanggal ini'
                ], 400);
            }

            // Use provided time or current time
            if ($request->check_out_time) {
                // Clean up the time string (remove any whitespace)
                $timeString = trim($request->check_out_time);
                $combinedDateTime = $dateString . ' ' . $timeString;

                try {
                    $checkOutTime = Carbon::createFromFormat('Y-m-d H:i', $combinedDateTime);
                } catch (\Exception $e) {
                    \Log::error('Check-out time parsing error:', [
                        'dateString' => $dateString,
                        'timeString' => $timeString,
                        'combined' => $combinedDateTime,
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception('Format waktu check-out tidak valid: ' . $e->getMessage());
                }
            } else {
                $checkOutTime = now();
            }

            // Calculate overtime if applicable
            $overtimeMinutes = 0;
            if (in_array($attendance->status, ['hadir', 'terlambat'])) {
                try {
                    $employee = Employee::with('workSchedule')->find($request->employee_id);
                    if ($employee && $employee->workSchedule) {
                        $schedule = $employee->workSchedule;
                        $endTime = Carbon::parse($schedule->end_time);
                        $overtimeThreshold = $schedule->overtime_threshold ?? 50;

                        // Create scheduled end time
                        $scheduledEndTime = Carbon::parse($dateString)
                            ->setTime($endTime->hour, $endTime->minute, 0);

                        // Calculate threshold time (end_time + overtime_threshold minutes)
                        $thresholdTime = Carbon::parse($dateString)
                            ->setTime($endTime->hour, $endTime->minute, 0)
                            ->addMinutes($overtimeThreshold);

                        // Only calculate overtime if checkout is after threshold time
                        // But calculate from end_time, not from threshold
                        if ($checkOutTime->greaterThan($thresholdTime)) {
                            $overtimeMinutes = $scheduledEndTime->diffInMinutes($checkOutTime);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to calculate overtime: ' . $e->getMessage());
                    $overtimeMinutes = 0;
                }
            }

            // Update attendance
            $attendance->update([
                'check_out' => $checkOutTime->format('H:i:s'),
                'photo_out' => null, // Always null for manual input
                'location_out' => null, // Always null for manual input
                'notes' => $attendance->notes ? $attendance->notes . ' | ' . $request->notes : $request->notes,
                'overtime_minutes' => $overtimeMinutes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-out berhasil dicatat',
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
     * Get attendance summary
     */
    public function summary(Request $request)
    {
        $employeeId = $request->get('employee_id');
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $query = Attendance::query();

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $query->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month);

        $summary = [
            'total' => $query->count(),
            'hadir' => $query->clone()->where('status', 'hadir')->count(),
            'terlambat' => $query->clone()->where('status', 'terlambat')->count(),
            'izin' => $query->clone()->where('status', 'izin')->count(),
            'sakit' => $query->clone()->where('status', 'sakit')->count(),
            'alpha' => $query->clone()->where('status', 'alpha')->count(),
            'cuti' => $query->clone()->where('status', 'cuti')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Check existing attendance by date
     */
    public function checkExistingByDate(Request $request)
    {
        $date = $request->get('date');
        
        if (!$date) {
            return response()->json([
                'success' => false,
                'message' => 'Date parameter required',
                'count' => 0
            ]);
        }

        $count = Attendance::whereDate('attendance_date', $date)->count();

        return response()->json([
            'success' => true,
            'count' => $count,
            'date' => $date
        ]);
    }

    /**
     * Verify employee face (compare with profile photo)
     */
    public function verifyFace(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'photo' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $employee = Karyawans::findOrFail($request->employee_id);

            // Here you would implement actual face recognition
            // For now, we'll just check if employee exists and has a profile photo

            if (!$employee->photo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karyawan belum memiliki foto profil. Silakan upload foto profil terlebih dahulu.',
                    'verified' => false
                ], 400);
            }

            // In production, integrate with face recognition API like:
            // - AWS Rekognition
            // - Azure Face API
            // - Face-API.js (client-side)
            // - Python face_recognition library

            // For now, return success (you need to implement actual face comparison)
            return response()->json([
                'success' => true,
                'message' => 'Verifikasi wajah berhasil',
                'verified' => true,
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'department' => $employee->department->name,
                    'position' => $employee->position->name,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memverifikasi wajah: ' . $e->getMessage()
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

    /**
     * Manual attendance entry (for admin)
     */
    public function manualEntry(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'attendance_date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'required|in:hadir,terlambat,izin,sakit,alpha,cuti',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $attendance = Attendance::updateOrCreate(
                [
                    'employee_id' => $request->employee_id,
                    'attendance_date' => $request->attendance_date
                ],
                [
                    'check_in' => $request->check_in,
                    'check_out' => $request->check_out,
                    'status' => $request->status,
                    'notes' => $request->notes,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Data absensi berhasil disimpan',
                'data' => $attendance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data absensi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display face detection page
     */
    public function faceDetection()
    {
        return view('admin.attendance.face-detection');
    }

    /**
     * Display report and analytics page
     */
    public function report(Request $request)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);
        $department = $request->get('department');

        // Get date range for selected month
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Base query
        $query = Attendance::whereBetween('attendance_date', [$startDate, $endDate]);

        if ($department) {
            $query->whereHas('employee', function ($q) use ($department) {
                $q->where('department_id', $department);
            });
        }

        // Get statistics
        $totalAttendance = $query->count();
        $hadirCount = (clone $query)->where('status', 'hadir')->count();
        $terlambatCount = (clone $query)->where('status', 'terlambat')->count();
        $izinCount = (clone $query)->where('status', 'izin')->count();
        $alphaCount = (clone $query)->where('status', 'alpha')->count();

        // Get daily statistics for chart
        $dailyStats = Attendance::selectRaw('DATE(attendance_date) as date, status, COUNT(*) as count')
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->when($department, function ($q) use ($department) {
                return $q->whereHas('employee', function ($subQ) use ($department) {
                    $subQ->where('department_id', $department);
                });
            })
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();

        // Get department statistics
        $departmentStats = Attendance::selectRaw('departments.name as department, attendances.status, COUNT(*) as count')
            ->join('employees', 'attendances.employee_id', '=', 'employees.id')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->when($department, function ($q) use ($department) {
                return $q->where('departments.id', $department);
            })
            ->groupBy('departments.name', 'attendances.status')
            ->get();

        // Get top late employees
        $topLateEmployees = Attendance::selectRaw('employees.name, employees.employee_code, SUM(attendances.late_minutes) as total_late, COUNT(*) as late_count')
            ->join('employees', 'attendances.employee_id', '=', 'employees.id')
            ->where('attendances.status', 'terlambat')
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->when($department, function ($q) use ($department) {
                return $q->where('employees.department_id', $department);
            })
            ->groupBy('employees.id', 'employees.name', 'employees.employee_code')
            ->orderBy('total_late', 'desc')
            ->limit(10)
            ->get();

        // Get departments for filter
        $departments = \App\Models\Department::orderBy('name')->get();

        // Prepare chart data
        $chartData = $this->prepareChartData($dailyStats, $startDate, $endDate);

        return view('admin.attendance.report', compact(
            'totalAttendance',
            'hadirCount',
            'terlambatCount',
            'izinCount',
            'alphaCount',
            'dailyStats',
            'departmentStats',
            'topLateEmployees',
            'departments',
            'chartData',
            'year',
            'month',
            'department'
        ));
    }

    /**
     * Prepare data for charts
     */
    private function prepareChartData($dailyStats, $startDate, $endDate)
    {
        $dates = [];
        $hadir = [];
        $terlambat = [];
        $izin = [];
        $alpha = [];

        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');
            $dates[] = $current->format('d/m');

            $dayStats = $dailyStats->where('date', $dateStr);

            $hadir[] = $dayStats->where('status', 'hadir')->sum('count');
            $terlambat[] = $dayStats->where('status', 'terlambat')->sum('count');
            $izin[] = $dayStats->where('status', 'izin')->sum('count');
            $alpha[] = $dayStats->where('status', 'alpha')->sum('count');

            $current->addDay();
        }

        return [
            'dates' => $dates,
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'izin' => $izin,
            'alpha' => $alpha,
        ];
    }

    /**
     * Get attendance detail
     */
    public function detail($id)
    {
        $attendance = Attendance::with(['employee.department', 'employee.position', 'employee.workSchedule'])
            ->findOrFail($id);

        // Format attendance_date to Y-m-d string to avoid timezone issues
        $attendanceData = $attendance->toArray();
        if (isset($attendanceData['attendance_date'])) {
            $attendanceData['attendance_date'] = Carbon::parse($attendance->attendance_date)->format('Y-m-d');
        }

        return response()->json([
            'success' => true,
            'data' => $attendanceData
        ]);
    }

    /**
     * Update attendance record
     */
    public function update(Request $request, $id)
    {
        try {
            $attendance = Attendance::with('employee.workSchedule')->findOrFail($id);

            // Validation
            $validator = Validator::make($request->all(), [
                'attendance_date' => 'required|date',
                'check_in' => 'required',
                'check_out' => 'nullable',
                'status' => 'required|in:hadir,terlambat,izin,sakit,cuti,alpha',
                'late_minutes' => 'nullable|integer|min:0',
                'notes' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Calculate late minutes automatically if status is 'terlambat' or 'hadir'
            $lateMinutes = 0;
            $schedule = $attendance->employee->workSchedule;

            if (in_array($request->status, ['hadir', 'terlambat']) && $schedule && $request->check_in) {
                try {
                    // Parse check-in time
                    $checkInTime = Carbon::createFromFormat('Y-m-d H:i', $request->attendance_date . ' ' . $request->check_in);

                    // Parse schedule start time
                    $startTime = substr($schedule->start_time, 0, 5); // Get HH:MM only
                    $scheduledTime = Carbon::createFromFormat('Y-m-d H:i', $request->attendance_date . ' ' . $startTime);

                    // Calculate late minutes if check-in is after scheduled time
                    if ($checkInTime->gt($scheduledTime)) {
                        $lateMinutes = $scheduledTime->diffInMinutes($checkInTime);
                    }
                } catch (\Exception $e) {
                    // If calculation fails, use provided late_minutes or 0
                    $lateMinutes = $request->late_minutes ?? 0;
                }
            } else {
                // For other statuses (izin, sakit, cuti, alpha), use provided value or 0
                $lateMinutes = $request->late_minutes ?? 0;
            }

            // Calculate overtime minutes if check_out is provided
            $overtimeMinutes = 0;
            if (in_array($request->status, ['hadir', 'terlambat']) && $schedule && $request->check_out) {
                try {
                    // Parse check-out time
                    $checkOutTime = Carbon::createFromFormat('Y-m-d H:i', $request->attendance_date . ' ' . $request->check_out);

                    // Parse schedule end time
                    $endTime = Carbon::parse($schedule->end_time);
                    $overtimeThreshold = $schedule->overtime_threshold ?? 50;

                    // Create scheduled end time
                    $scheduledEndTime = Carbon::parse($request->attendance_date)
                        ->setTime($endTime->hour, $endTime->minute, 0);

                    // Calculate threshold time (end_time + overtime_threshold minutes)
                    $thresholdTime = Carbon::parse($request->attendance_date)
                        ->setTime($endTime->hour, $endTime->minute, 0)
                        ->addMinutes($overtimeThreshold);

                    // Only calculate overtime if checkout is after threshold time
                    // But calculate from end_time, not from threshold
                    if ($checkOutTime->greaterThan($thresholdTime)) {
                        $overtimeMinutes = $scheduledEndTime->diffInMinutes($checkOutTime);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to calculate overtime in update: ' . $e->getMessage());
                    $overtimeMinutes = 0;
                }
            }

            // Update attendance data
            $attendance->attendance_date = $request->attendance_date;
            $attendance->check_in = $request->check_in;
            $attendance->check_out = $request->check_out;
            $attendance->status = $request->status;
            $attendance->late_minutes = $lateMinutes;
            $attendance->overtime_minutes = $overtimeMinutes;
            $attendance->notes = $request->notes;

            $attendance->save();

            return response()->json([
                'success' => true,
                'message' => 'Data absensi berhasil diupdate',
                'data' => $attendance->load(['employee.department', 'employee.position'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate data absensi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete attendance record
     */
    public function destroy($id)
    {
        try {
            $attendance = Attendance::findOrFail($id);

            // Store info for response
            $employeeName = $attendance->employee->name;
            $attendanceDate = Carbon::parse($attendance->attendance_date)->locale('id')->translatedFormat('d F Y');

            // Delete photos if exist
            if ($attendance->photo_in) {
                Storage::disk('public')->delete($attendance->photo_in);
            }
            if ($attendance->photo_out) {
                Storage::disk('public')->delete($attendance->photo_out);
            }

            // Delete attendance record
            $attendance->delete();

            return response()->json([
                'success' => true,
                'message' => "Data absensi {$employeeName} tanggal {$attendanceDate} berhasil dihapus"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data absensi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete attendances
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:attendances,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $ids = $request->ids;
            $deletedCount = 0;

            // Get all attendances to be deleted
            $attendances = Attendance::whereIn('id', $ids)->get();

            foreach ($attendances as $attendance) {
                // Delete photos if exist
                if ($attendance->photo_in) {
                    Storage::disk('public')->delete($attendance->photo_in);
                }
                if ($attendance->photo_out) {
                    Storage::disk('public')->delete($attendance->photo_out);
                }

                // Delete attendance record
                $attendance->delete();
                $deletedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil menghapus {$deletedCount} data absensi"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data absensi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export attendance to Excel
     */
    public function export(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $status = $request->get('status');
        $department = $request->get('department');
        $search = $request->get('search');

        return Excel::download(
            new \App\Exports\AttendanceExport($dateFrom, $dateTo, $status, $department, $search),
            'absensi_' . $dateFrom . '_' . $dateTo . '.xlsx'
        );
    }

    /**
     * Recalculate overtime for attendance records
     */
    public function recalculateOvertime(Request $request)
    {
        try {
            $from = $request->get('from');
            $to = $request->get('to');

            // Build query
            $query = Attendance::with(['employee.workSchedule'])
                ->whereNotNull('check_out')
                ->whereIn('status', ['hadir', 'terlambat']);

            // Apply filters
            if ($from) {
                $query->whereDate('attendance_date', '>=', $from);
            }

            if ($to) {
                $query->whereDate('attendance_date', '<=', $to);
            }

            $attendances = $query->get();
            $total = $attendances->count();

            if ($total === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data attendance yang perlu dihitung ulang'
                ]);
            }

            $processed = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($attendances as $attendance) {
                $processed++;

                // Skip if no work schedule
                if (!$attendance->employee || !$attendance->employee->workSchedule) {
                    $skipped++;
                    continue;
                }

                $schedule = $attendance->employee->workSchedule;

                try {
                    // Parse check-out time
                    $checkOutTimeStr = $attendance->check_out;

                    if ($checkOutTimeStr instanceof \Carbon\Carbon) {
                        $checkOutTime = Carbon::parse($attendance->attendance_date->format('Y-m-d') . ' ' . $checkOutTimeStr->format('H:i:s'));
                    } else {
                        $checkOutTime = Carbon::parse($attendance->attendance_date->format('Y-m-d') . ' ' . $checkOutTimeStr);
                    }

                    // Parse schedule end time
                    $endTime = Carbon::parse($schedule->end_time);
                    $overtimeThreshold = $schedule->overtime_threshold ?? 50;

                    // Create scheduled end time
                    $scheduledEndTime = Carbon::parse($attendance->attendance_date->format('Y-m-d'))
                        ->setTime($endTime->hour, $endTime->minute, 0);

                    // Calculate threshold time
                    $thresholdTime = Carbon::parse($attendance->attendance_date->format('Y-m-d'))
                        ->setTime($endTime->hour, $endTime->minute, 0)
                        ->addMinutes($overtimeThreshold);

                    // Calculate overtime
                    $overtimeMinutes = 0;
                    if ($checkOutTime->greaterThan($thresholdTime)) {
                        $overtimeMinutes = $scheduledEndTime->diffInMinutes($checkOutTime);
                    }

                    // Update if different from current value
                    if ($attendance->overtime_minutes != $overtimeMinutes) {
                        $attendance->overtime_minutes = $overtimeMinutes;
                        $attendance->save();
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $skipped++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Berhasil menghitung ulang lembur',
                'data' => [
                    'total_processed' => $processed,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'no_changes' => $processed - $updated - $skipped
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghitung ulang lembur: ' . $e->getMessage()
            ], 500);
        }
    }
}
