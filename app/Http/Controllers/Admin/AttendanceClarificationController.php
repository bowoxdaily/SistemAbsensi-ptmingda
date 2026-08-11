<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceClarification;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AttendanceClarificationController extends Controller
{
    private function ensureAdminAccess(?Request $request = null)
    {
        if (Auth::check() && !in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            if ($request && ($request->expectsJson() || $request->is('api/*'))) {
                abort(response()->json(['success' => false, 'message' => 'Akses ditolak. Fitur klarifikasi fisik hanya untuk Administrator.'], 403));
            }
            abort(403, 'Akses ditolak. Fitur klarifikasi fisik hanya untuk Administrator.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdminAccess($request);
        return view('admin.attendance.clarifications');
    }

    public function pendingCount(Request $request)
    {
        $this->ensureAdminAccess($request);
        return response()->json(['count' => AttendanceClarification::where('status', 'pending')->count()]);
    }

    public function stats(Request $request)
    {
        $this->ensureAdminAccess($request);
        return response()->json(['success' => true, 'data' => [
            'pending'  => AttendanceClarification::where('status', 'pending')->count(),
            'approved' => AttendanceClarification::where('status', 'approved')->count(),
            'rejected' => AttendanceClarification::where('status', 'rejected')->count(),
        ]]);
    }

    public function list(Request $request)
    {
        $this->ensureAdminAccess($request);
        $query = AttendanceClarification::with([
            'employee.department', 'employee.subDepartment', 'attendance', 'reviewer',
        ]);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->get('search')) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        $items = $query->latest()->paginate($request->get('per_page', 15));
        $items->getCollection()->transform(function ($item) {
            $item->attachment_url = $item->attachment_url;
            $item->is_image       = $item->is_image;
            $item->new_check_in   = $item->new_check_in  ? substr($item->new_check_in, 0, 5)  : null;
            $item->new_check_out  = $item->new_check_out ? substr($item->new_check_out, 0, 5) : null;

            if ($item->attendance && $item->attendance->attendance_date) {
                $attDateStr = is_string($item->attendance->attendance_date) ? explode('T', $item->attendance->attendance_date)[0] : $item->attendance->attendance_date;
                $item->formatted_attendance_date = Carbon::parse($attDateStr)->locale('id')->isoFormat('D MMMM YYYY');
                $item->attendance_date_raw       = Carbon::parse($attDateStr)->format('Y-m-d');
            }
            if ($item->created_at) {
                $item->formatted_created_at = Carbon::parse($item->created_at)->locale('id')->isoFormat('D MMM YYYY, HH:mm');
            }
            return $item;
        });

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function detail(Request $request, $id)
    {
        $this->ensureAdminAccess($request);
        $item = AttendanceClarification::with([
            'employee.department', 'employee.subDepartment', 'attendance', 'reviewer',
        ])->findOrFail($id);

        $data                   = $item->toArray();
        $data['attachment_url'] = $item->attachment_url;
        $data['is_image']       = $item->is_image;
        $data['new_check_in']   = $item->new_check_in  ? substr($item->new_check_in, 0, 5)  : null;
        $data['new_check_out']  = $item->new_check_out ? substr($item->new_check_out, 0, 5) : null;

        if ($item->attendance && $item->attendance->attendance_date) {
            $attDateStr = is_string($item->attendance->attendance_date) ? explode('T', $item->attendance->attendance_date)[0] : $item->attendance->attendance_date;
            $data['formatted_attendance_date'] = Carbon::parse($attDateStr)->locale('id')->isoFormat('D MMMM YYYY');
            $data['attendance_date_raw']       = Carbon::parse($attDateStr)->format('Y-m-d');
        }
        if ($item->created_at) {
            $data['formatted_created_at'] = Carbon::parse($item->created_at)->locale('id')->isoFormat('D MMMM YYYY, HH:mm');
        }
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function approve(Request $request, $id)
    {
        $this->ensureAdminAccess($request);
        $validator = Validator::make($request->all(), ['review_notes' => 'nullable|string|max:500']);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $clarification = AttendanceClarification::with(['attendance.employee.workSchedule', 'employee'])->findOrFail($id);
        if ($clarification->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Klarifikasi ini sudah diproses.'], 422);
        }

        $attendance  = $clarification->attendance;
        $newCheckIn  = $clarification->new_check_in  ? substr($clarification->new_check_in, 0, 5)  : null;
        $newCheckOut = $clarification->new_check_out ? substr($clarification->new_check_out, 0, 5) : null;
        $newStatus   = $clarification->new_status;

        // Hitung keterlambatan
        $lateMinutes = 0;
        if (in_array($newStatus, ['hadir', 'terlambat', 'lembur']) && $newCheckIn) {
            try {
                $schedule = $attendance->employee->workSchedule ?? null;
                if ($schedule) {
                    $startStr      = substr((string) $schedule->start_time, 0, 5);
                    $checkInTime   = Carbon::createFromFormat('H:i', $newCheckIn);
                    $scheduledTime = Carbon::createFromFormat('H:i', $startStr);
                    if ($checkInTime->gt($scheduledTime)) {
                        $lateMinutes = $scheduledTime->diffInMinutes($checkInTime);
                    }
                }
            } catch (\Exception $e) { $lateMinutes = 0; }
        }

        $attendance->status       = $newStatus;
        $attendance->late_minutes = $lateMinutes;
        if ($newCheckIn)  { $attendance->check_in  = $newCheckIn  . ':00'; }
        if ($newCheckOut) { $attendance->check_out = $newCheckOut . ':00'; $attendance->overtime_minutes = 0; }
        $attendance->save();

        $clarification->update([
            'status' => 'approved', 'reviewed_by' => Auth::id(),
            'reviewed_at' => now(), 'review_notes' => $request->review_notes,
        ]);

        // Kirim Notifikasi WhatsApp
        try {
            $employee = $clarification->employee ?? ($attendance ? $attendance->employee : null);
            if ($employee && !empty($employee->phone)) {
                $attDateStr = $attendance && $attendance->attendance_date
                    ? (is_string($attendance->attendance_date) ? explode('T', $attendance->attendance_date)[0] : $attendance->attendance_date)
                    : null;
                $formattedDate = $attDateStr ? Carbon::parse($attDateStr)->locale('id')->isoFormat('D MMMM YYYY') : '-';

                $details = [];
                $details[] = "Status: " . ucfirst($newStatus);
                if ($newCheckIn) {
                    $details[] = "Jam Masuk: " . $newCheckIn;
                }
                if ($newCheckOut) {
                    $details[] = "Jam Keluar: " . $newCheckOut;
                }
                $detailsStr = implode("\n", $details);

                $notesStr = $request->review_notes ? "\nCatatan Admin: " . $request->review_notes : "";

                $message = "Halo {$employee->name},\n\n"
                    . "Pengajuan Klarifikasi Absensi Fisik Anda untuk tanggal *{$formattedDate}* telah *DISETUJUI*.\n\n"
                    . "Rincian Absensi:\n"
                    . "{$detailsStr}"
                    . "{$notesStr}\n\n"
                    . "Terima kasih.";

                $waService = new WhatsAppService();
                $waService->send($employee->phone, $message);
            }
        } catch (\Exception $e) {
            Log::error('Gagal mengirim WhatsApp Notifikasi Klarifikasi Physical (Approve): ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Klarifikasi disetujui dan data absensi diperbarui.']);
    }

    public function reject(Request $request, $id)
    {
        $this->ensureAdminAccess($request);
        $v = Validator::make($request->all(), ['review_notes' => 'required|string|max:500']);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Alasan penolakan wajib diisi.', 'errors' => $v->errors()], 422);
        }

        $clarification = AttendanceClarification::with(['attendance', 'employee'])->findOrFail($id);
        if ($clarification->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Klarifikasi ini sudah diproses.'], 422);
        }

        $clarification->update([
            'status' => 'rejected', 'reviewed_by' => Auth::id(),
            'reviewed_at' => now(), 'review_notes' => $request->review_notes,
        ]);

        // Kirim Notifikasi WhatsApp
        try {
            $employee = $clarification->employee ?? ($clarification->attendance ? $clarification->attendance->employee : null);
            if ($employee && !empty($employee->phone)) {
                $attDateStr = $clarification->attendance && $clarification->attendance->attendance_date
                    ? (is_string($clarification->attendance->attendance_date) ? explode('T', $clarification->attendance->attendance_date)[0] : $clarification->attendance->attendance_date)
                    : null;
                $formattedDate = $attDateStr ? Carbon::parse($attDateStr)->locale('id')->isoFormat('D MMMM YYYY') : '-';

                $message = "Halo {$employee->name},\n\n"
                    . "Pengajuan Klarifikasi Absensi Fisik Anda untuk tanggal *{$formattedDate}* telah *DITOLAK*.\n\n"
                    . "Alasan Penolakan: {$request->review_notes}\n\n"
                    . "Silakan hubungi HRD / Administrator jika membutuhkan informasi lebih lanjut.";

                $waService = new WhatsAppService();
                $waService->send($employee->phone, $message);
            }
        } catch (\Exception $e) {
            Log::error('Gagal mengirim WhatsApp Notifikasi Klarifikasi Physical (Reject): ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Klarifikasi ditolak.']);
    }
}
