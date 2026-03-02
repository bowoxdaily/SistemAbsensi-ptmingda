<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceEditRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AttendanceEditRequestController extends Controller
{
    // ─────────────────────────────────────────────────────────────────
    // WEB VIEW
    // ─────────────────────────────────────────────────────────────────

    /**
     * Halaman daftar edit request (manager)
     */
    public function index()
    {
        return view('admin.attendance.edit-requests');
    }

    // ─────────────────────────────────────────────────────────────────
    // API – READ
    // ─────────────────────────────────────────────────────────────────

    /**
     * Jumlah request pending (untuk badge sidebar)
     */
    public function pendingCount()
    {
        $count = AttendanceEditRequest::pending()->count();
        return response()->json(['count' => $count]);
    }

    /**
     * Statistik jumlah per status (untuk dashboard cards)
     */
    public function stats()
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'pending'  => AttendanceEditRequest::where('status', 'pending')->count(),
                'approved' => AttendanceEditRequest::where('status', 'approved')->count(),
                'rejected' => AttendanceEditRequest::where('status', 'rejected')->count(),
            ],
        ]);
    }

    /**
     * Daftar request (API, untuk DataTable AJAX)
     */
    public function list(Request $request)
    {
        $query = AttendanceEditRequest::with([
            'attendance.employee.department',
            'requester',
            'reviewer',
        ]);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $requests = $query->latest()->paginate($request->get('per_page', 15));

        // Explicitly format date fields to avoid timezone shift in JS
        $requests->getCollection()->transform(function ($item) {
            $item->old_attendance_date = $item->old_attendance_date
                ? Carbon::parse($item->old_attendance_date)->format('Y-m-d')
                : null;
            $item->new_attendance_date = $item->new_attendance_date
                ? Carbon::parse($item->new_attendance_date)->format('Y-m-d')
                : null;
            return $item;
        });

        return response()->json([
            'success' => true,
            'data'    => $requests,
        ]);
    }

    /**
     * Detail satu request
     */
    public function detail($id)
    {
        $editRequest = AttendanceEditRequest::with([
            'attendance.employee.department',
            'requester',
            'reviewer',
        ])->findOrFail($id);

        // Format dates as plain Y-m-d strings (avoid timezone shift in JS)
        $data = $editRequest->toArray();
        foreach (['old_attendance_date', 'new_attendance_date'] as $field) {
            if (!empty($data[$field])) {
                $data[$field] = Carbon::parse($editRequest->$field)->format('Y-m-d');
            }
        }

        return response()->json(['success' => true, 'data' => $data]);
    }

    // ─────────────────────────────────────────────────────────────────
    // API – WRITE (admin)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Admin mengajukan request edit absensi
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attendance_id'      => 'required|exists:attendances,id',
            'new_attendance_date' => 'required|date',
            'new_check_in'       => 'nullable|date_format:H:i',
            'new_check_out'      => 'nullable|date_format:H:i',
            'new_status'         => 'required|in:hadir,terlambat,izin,sakit,cuti,alpha,libur,cuti_bersama,lembur,off,cuti_khusus',
            'reason'             => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Tidak boleh ada request pending untuk absensi yang sama
        $exists = AttendanceEditRequest::where('attendance_id', $request->attendance_id)
            ->where('status', 'pending')
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Sudah ada request edit yang sedang menunggu persetujuan untuk absensi ini.',
            ], 422);
        }

        $attendance = Attendance::findOrFail($request->attendance_id);

        AttendanceEditRequest::create([
            'attendance_id'      => $attendance->id,
            'requested_by'       => Auth::id(),
            // Snapshot nilai lama
            'old_attendance_date' => Carbon::parse($attendance->attendance_date)->format('Y-m-d'),
            'old_check_in'       => $attendance->check_in
                ? Carbon::parse($attendance->check_in)->format('H:i:s')
                : null,
            'old_check_out'      => $attendance->check_out
                ? Carbon::parse($attendance->check_out)->format('H:i:s')
                : null,
            'old_status'         => $attendance->status,
            // Nilai baru
            'new_attendance_date' => $request->new_attendance_date,
            'new_check_in'       => $request->new_check_in
                ? $request->new_check_in . ':00'
                : null,
            'new_check_out'      => $request->new_check_out
                ? $request->new_check_out . ':00'
                : null,
            'new_status'         => $request->new_status,
            'reason'             => $request->reason,
            'status'             => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Request edit absensi berhasil dikirim dan menunggu persetujuan manager.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // API – WRITE (manager)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Manager menyetujui request → terapkan perubahan ke tabel attendances
     */
    public function approve(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'review_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal'], 422);
        }

        $editRequest = AttendanceEditRequest::with('attendance.employee.workSchedule')->findOrFail($id);

        if ($editRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request ini sudah diproses sebelumnya.',
            ], 422);
        }

        $attendance = $editRequest->attendance;
        $schedule   = $attendance->employee->workSchedule;

        $newDate    = $editRequest->new_attendance_date
            ? Carbon::parse($editRequest->new_attendance_date)->format('Y-m-d')
            : null;
        $newCheckIn  = $editRequest->new_check_in
            ? substr($editRequest->new_check_in, 0, 5)
            : null;
        $newCheckOut = $editRequest->new_check_out
            ? substr($editRequest->new_check_out, 0, 5)
            : null;
        $newStatus   = $editRequest->new_status;

        // Hitung late_minutes
        $lateMinutes = 0;
        if (in_array($newStatus, ['hadir', 'terlambat', 'lembur']) && $schedule && $newCheckIn) {
            try {
                $checkInTime = Carbon::createFromFormat('Y-m-d H:i', $newDate . ' ' . $newCheckIn);

                if ($schedule->start_time instanceof Carbon) {
                    $startStr = $schedule->start_time->format('H:i');
                } else {
                    preg_match('/(\d{1,2}):(\d{2})/', (string) $schedule->start_time, $m);
                    $startStr = $m ? $m[1] . ':' . $m[2] : '08:00';
                }

                $scheduledTime = Carbon::createFromFormat('Y-m-d H:i', $newDate . ' ' . $startStr);
                if ($checkInTime->gt($scheduledTime)) {
                    $lateMinutes = $scheduledTime->diffInMinutes($checkInTime);
                }
            } catch (\Exception $e) {
                $lateMinutes = 0;
            }
        }

        // Hitung overtime_minutes
        $overtimeMinutes = 0;
        if (in_array($newStatus, ['hadir', 'terlambat']) && $schedule && $newCheckOut) {
            try {
                $checkOutTime = Carbon::createFromFormat('Y-m-d H:i', $newDate . ' ' . $newCheckOut);
                $endTime      = $schedule->end_time;

                if ($endTime instanceof Carbon) {
                    $endHour   = $endTime->hour;
                    $endMinute = $endTime->minute;
                } else {
                    preg_match('/(\d{1,2}):(\d{2})/', (string) $endTime, $match);
                    $endHour   = $match ? (int) $match[1] : 17;
                    $endMinute = $match ? (int) $match[2] : 0;
                }

                $overtimeThreshold = $schedule->overtime_threshold ?? 50;
                $scheduledEndTime  = Carbon::parse($newDate)->setTime($endHour, $endMinute, 0);
                $thresholdTime     = Carbon::parse($newDate)->setTime($endHour, $endMinute, 0)->addMinutes($overtimeThreshold);

                if ($checkOutTime->greaterThan($thresholdTime)) {
                    $overtimeMinutes = $scheduledEndTime->diffInMinutes($checkOutTime);
                }
            } catch (\Exception $e) {
                $overtimeMinutes = 0;
            }
        }

        // Terapkan perubahan ke attendance
        $attendance->attendance_date   = $newDate;
        $attendance->check_in          = $newCheckIn;
        $attendance->check_out         = $newCheckOut;
        $attendance->status            = $newStatus;
        $attendance->late_minutes      = $lateMinutes;
        $attendance->overtime_minutes  = $overtimeMinutes;
        $attendance->save();

        // Update status request
        $editRequest->update([
            'status'       => 'approved',
            'reviewed_by'  => Auth::id(),
            'reviewed_at'  => now(),
            'review_notes' => $request->review_notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Request disetujui dan data absensi berhasil diperbarui.',
        ]);
    }

    /**
     * Manager menolak request
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'review_notes' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Catatan penolakan wajib diisi.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $editRequest = AttendanceEditRequest::findOrFail($id);

        if ($editRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request ini sudah diproses sebelumnya.',
            ], 422);
        }

        $editRequest->update([
            'status'       => 'rejected',
            'reviewed_by'  => Auth::id(),
            'reviewed_at'  => now(),
            'review_notes' => $request->review_notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Request ditolak.',
        ]);
    }
}
