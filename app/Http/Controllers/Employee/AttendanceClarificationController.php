<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceClarification;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AttendanceClarificationController extends Controller
{
    // ─────────────────────────────────────────────────────────────────
    // WEB VIEW
    // ─────────────────────────────────────────────────────────────────

    /**
     * Halaman daftar klarifikasi karyawan (read-only status)
     */
    public function index()
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        // Tandai semua klarifikasi approved/rejected milik karyawan ini sebagai sudah dibaca
        AttendanceClarification::where('employee_id', $employee->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->where('is_read_by_employee', false)
            ->update(['is_read_by_employee' => true]);

        return view('employee.attendance.clarifications', compact('employee'));
    }

    // ─────────────────────────────────────────────────────────────────
    // API – SUBMIT (karyawan)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Karyawan submit klarifikasi absen + upload scan formulir fisik
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attendance_id' => 'required|exists:attendances,id',
            'reason'        => 'required|string|max:1000',
            'new_status'    => 'required|in:hadir,terlambat,izin,sakit,cuti,alpha,libur,lembur,off,cuti_khusus',
            'new_check_in'  => 'nullable|date_format:H:i',
            'new_check_out' => 'nullable|date_format:H:i',
            'attachment'    => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'attachment.required' => 'Scan/foto formulir klarifikasi fisik wajib dilampirkan.',
            'attachment.mimes'    => 'Format file harus JPG, PNG, atau PDF.',
            'attachment.max'      => 'Ukuran file maksimal 5 MB.',
            'reason.required'     => 'Alasan klarifikasi wajib diisi.',
            'new_status.required' => 'Status absensi wajib dipilih.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        // Pastikan absensi milik karyawan ini
        $attendance = Attendance::where('id', $request->attendance_id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        // Cegah duplikat pending untuk absensi yang sama
        $alreadyPending = AttendanceClarification::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return response()->json([
                'success' => false,
                'message' => 'Sudah ada klarifikasi yang sedang menunggu persetujuan untuk absensi ini.',
            ], 422);
        }

        // Simpan file ke storage (menggunakan default storage disk: local / r2)
        $file         = $request->file('attachment');
        $originalName = $file->getClientOriginalName();
        $ext          = $file->getClientOriginalExtension();
        $filename     = Carbon::now()->format('Y-m-d') . '_' . Str::random(8) . '.' . $ext;
        $folder       = 'clarifications/' . $employee->id;
        $disk         = config('filesystems.default', 'r2');

        try {
            $path = $file->storeAs($folder, $filename, $disk);

            if (!$path || $path === '0') {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengunggah berkas ke Cloudflare R2 Storage. Silakan coba lagi.',
                ], 500);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error upload ke R2: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah berkas ke R2 storage: ' . $e->getMessage(),
            ], 500);
        }

        AttendanceClarification::create([
            'attendance_id'            => $attendance->id,
            'employee_id'              => $employee->id,
            'reason'                   => $request->reason,
            'new_status'               => $request->new_status,
            'new_check_in'             => $request->new_check_in  ? $request->new_check_in  . ':00' : null,
            'new_check_out'            => $request->new_check_out ? $request->new_check_out . ':00' : null,
            'attachment_path'          => $path,
            'attachment_original_name' => $originalName,
            'status'                   => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Klarifikasi berhasil dikirim. Menunggu verifikasi admin.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // API – READ (karyawan lihat status miliknya)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Daftar klarifikasi milik karyawan ini (AJAX/paginated)
     */
    public function list(Request $request)
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $query = AttendanceClarification::with(['attendance', 'reviewer'])
            ->where('employee_id', $employee->id);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
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

    /**
     * Statistik klarifikasi milik karyawan
     */
    public function stats()
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return response()->json(['success' => true, 'data' => [
                'pending' => 0, 'approved' => 0, 'rejected' => 0,
            ]]);
        }

        return response()->json(['success' => true, 'data' => [
            'pending'  => AttendanceClarification::where('employee_id', $employee->id)->where('status', 'pending')->count(),
            'approved' => AttendanceClarification::where('employee_id', $employee->id)->where('status', 'approved')->count(),
            'rejected' => AttendanceClarification::where('employee_id', $employee->id)->where('status', 'rejected')->count(),
        ]]);
    }

    /**
     * Detail satu klarifikasi (karyawan hanya bisa lihat miliknya)
     */
    public function detail($id)
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $item = AttendanceClarification::with(['attendance', 'reviewer'])
            ->where('employee_id', $employee->id)
            ->findOrFail($id);

        $data                  = $item->toArray();
        $data['attachment_url'] = $item->attachment_url;
        $data['is_image']      = $item->is_image;
        $data['new_check_in']  = $item->new_check_in  ? substr($item->new_check_in, 0, 5)  : null;
        $data['new_check_out'] = $item->new_check_out ? substr($item->new_check_out, 0, 5) : null;

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

    /**
     * Jumlah klarifikasi pending (untuk badge)
     */
    public function pendingCount()
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        $count = $employee
            ? AttendanceClarification::where('employee_id', $employee->id)->where('status', 'pending')->count()
            : 0;

        return response()->json(['count' => $count]);
    }
}

