<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WarningLetter;
use App\Models\Employee;
use App\Mail\WarningLetterMail;
use App\Services\EmailSmtpSettingService;
use Illuminate\Support\Facades\Mail;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\WarningLetterExport;

class WarningLetterController extends Controller
{
    /**
     * Display list of warning letters (web view)
     */
    public function index(Request $request)
    {
        // Security: Ensure only admin can access
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'manager', 'viewer', 'superadmin'])) {
            abort(403, 'Unauthorized action.');
        }

        return view('admin.warning-letters.index');
    }

    /**
     * Get list of warning letters (API for DataTables)
     */
    public function list(Request $request)
    {
        // Security check
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'manager', 'viewer', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $query = WarningLetter::with(['employee.department', 'employee.position', 'issuer'])
            ->orderBy('issue_date', 'desc');

        // Filter by employee
        if ($request->has('employee_id') && $request->employee_id != '') {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by SP type
        if ($request->has('sp_type') && $request->sp_type != '') {
            $query->where('sp_type', $request->sp_type);
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by date range (issue_date)
        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('issue_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('issue_date', '<=', $request->end_date);
        }

        // Search by SP number or violation
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sp_number', 'like', "%{$search}%")
                    ->orWhere('violation', 'like', "%{$search}%");
            });
        }

        $warningLetters = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $warningLetters
        ]);
    }

    /**
     * Get statistics for dashboard cards (API)
     */
    public function statistics()
    {
        // Security check
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'manager', 'viewer', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $activeLetters = fn () => WarningLetter::where('status', 'aktif')
            ->where(fn ($query) => $query->whereNull('completion_date')->orWhereDate('completion_date', '>', today()));

        $stats = [
            'total_aktif'      => $activeLetters()->count(),
            'st_aktif'         => $activeLetters()->where('sp_type', 'ST')->count(),
            'sp1_aktif'        => $activeLetters()->where('sp_type', 'SP1')->count(),
            'sp2_aktif'        => $activeLetters()->where('sp_type', 'SP2')->count(),
            'sp3_aktif'        => $activeLetters()->where('sp_type', 'SP3')->count(),
            'total_selesai'    => WarningLetter::where('status', 'selesai')->count(),
            'total_dibatalkan' => WarningLetter::where('status', 'dibatalkan')->count(),
            'total_bulan_ini'  => WarningLetter::whereMonth('issue_date', now()->month)
                ->whereYear('issue_date', now()->year)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Show detail of warning letter (API)
     */
    public function show($id)
    {
        // Security check
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'manager', 'viewer', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        try {
            $sp = WarningLetter::with([
                'employee.department',
                'employee.position',
                'issuer',
                'updater'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $sp
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data SP tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Store new warning letter (API)
     */
    public function store(Request $request)
    {
        // Security check
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'manager', 'viewer', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        // Validation
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'sp_type' => 'required|in:ST,SP1,SP2,SP3,SP_GABUNGAN',
            'sp_number' => 'required|string|max:100|unique:warning_letters,sp_number',
            'issue_date' => 'required|date',
            'effective_date' => 'required|date|after_or_equal:issue_date',
            'violation' => 'required|string|max:1000',
            'description' => 'nullable|string|max:2000',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // Document now optional for draft
            'completion_date' => 'nullable|date|after:effective_date',
            'send_notification' => 'nullable|boolean',
        ]);

        // Business logic validation: Check for duplicate active/draft SP type
        $existingActiveSP = WarningLetter::where('employee_id', $request->employee_id)
            ->where('sp_type', $request->sp_type)
            ->whereIn('status', ['draft', 'aktif']) // Check both draft and aktif
            ->first();

        if ($existingActiveSP) {
            $statusLabel = $existingActiveSP->status === 'draft' ? 'draft' : 'aktif';
            return response()->json([
                'success' => false,
                'message' => "Karyawan sudah memiliki {$request->sp_type} dengan status {$statusLabel} (Nomor: {$existingActiveSP->sp_number}). Selesaikan atau batalkan SP yang ada terlebih dahulu."
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Use SP number from request (manual input or generated)
            $spNumber = $request->sp_number;

            // Handle file upload
            $documentPath = null;
            $status = 'draft'; // Default status is draft

            if ($request->hasFile('document')) {
                $file = $request->file('document');
                $filename = 'sp_' . $request->sp_type . '_' . $request->employee_id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $documentPath = $file->storeAs('warning_letters', $filename, config('filesystems.default'));

                // Auto-activate if document is uploaded
                $status = 'aktif';
            }

            // Create warning letter
            $sp = WarningLetter::create([
                'employee_id' => $request->employee_id,
                'sp_type' => $request->sp_type,
                'sp_number' => $spNumber,
                'issue_date' => $request->issue_date,
                'effective_date' => $request->effective_date,
                'violation' => $request->violation,
                'description' => $request->description,
                'document_path' => $documentPath,
                'status' => $status,
                'completion_date' => $request->completion_date,
                'issued_by' => Auth::id(),
                'issued_at' => now(),
            ]);

            DB::commit();

            $message = $status === 'draft'
                ? 'SP berhasil dibuat sebagai draft. Upload dokumen untuk mengaktifkan.'
                : 'Surat peringatan berhasil dibuat dan diaktifkan';

            if ($status === 'aktif' && $request->send_notification) {
                try {
                    $this->sendWarningLetterEmail($sp);
                    $message .= '. Notifikasi email berhasil dikirim.';
                } catch (Throwable $e) {
                    Log::warning('SP created but email notification failed', [
                        'sp_id' => $sp->id,
                        'error' => $e->getMessage(),
                    ]);
                    $message .= ', tetapi notifikasi email gagal dikirim. Periksa pengaturan SMTP.';
                }
            }

            Log::info('Warning letter created', [
                'sp_id' => $sp->id,
                'sp_number' => $sp->sp_number,
                'employee_id' => $sp->employee_id,
                'status' => $status,
                'issued_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $sp->load(['employee', 'issuer'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error creating warning letter', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat surat peringatan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update warning letter (API)
     */
    public function update(Request $request, $id)
    {
        // Security check
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'manager', 'viewer', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        // Validation
        $request->validate([
            'status' => 'nullable|in:aktif,selesai,dibatalkan',
            'completion_date' => 'nullable|date',
            'description' => 'nullable|string|max:2000',
        ]);

        try {
            $sp = WarningLetter::findOrFail($id);

            $sp->update([
                'status' => $request->status ?? $sp->status,
                'completion_date' => $request->completion_date ?? $sp->completion_date,
                'description' => $request->description ?? $sp->description,
                'updated_by' => Auth::id(),
            ]);

            Log::info('Warning letter updated', [
                'sp_id' => $sp->id,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Surat peringatan berhasil diupdate',
                'data' => $sp->load(['employee', 'issuer', 'updater'])
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating warning letter', [
                'sp_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate surat peringatan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete warning letter (API)
     */
    public function destroy($id)
    {
        // Security check
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'manager', 'viewer', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        try {
            $sp = WarningLetter::findOrFail($id);

            // Soft delete
            $sp->delete();

            Log::info('Warning letter deleted', [
                'sp_id' => $sp->id,
                'deleted_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Surat peringatan berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting warning letter', [
                'sp_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus surat peringatan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel warning letter with reason (API)
     */
    public function cancel(Request $request, $id)
    {
        // Security check
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'manager', 'viewer', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        // Validation
        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        try {
            $sp = WarningLetter::findOrFail($id);

            $sp->update([
                'status' => 'dibatalkan',
                'cancellation_reason' => $request->cancellation_reason,
                'updated_by' => Auth::id(),
            ]);

            Log::info('Warning letter cancelled', [
                'sp_id' => $sp->id,
                'cancelled_by' => Auth::id(),
                'reason' => $request->cancellation_reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Surat peringatan berhasil dibatalkan',
                'data' => $sp->load(['employee', 'issuer', 'updater'])
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling warning letter', [
                'sp_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan surat peringatan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send or resend email notification (API)
     */
    public function sendNotification($id)
    {
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'manager', 'viewer', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        try {
            $sp = WarningLetter::with(['employee', 'issuer'])->findOrFail($id);
            $this->sendWarningLetterEmail($sp);

            return response()->json([
                'success' => true,
                'message' => 'Notifikasi email berhasil dikirim ke ' . $sp->employee->name,
            ]);
        } catch (Throwable $e) {
            Log::error('Error sending SP email notification', ['sp_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk send email notifications (API)
     */
    public function bulkSendNotification(Request $request)
    {
        // Security check
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'manager', 'viewer', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        // Validation
        $request->validate([
            'sp_ids' => 'required|array',
            'sp_ids.*' => 'exists:warning_letters,id',
        ]);

        try {
            $spIds = $request->sp_ids;
            $sps = WarningLetter::with('employee')->whereIn('id', $spIds)->get();

            $sent = 0;
            $failed = 0;

            foreach ($sps as $sp) {
                try {
                    $this->sendWarningLetterEmail($sp);
                    $sent++;
                } catch (Throwable $e) {
                    $failed++;
                    Log::warning('Bulk SP email failed', ['sp_id' => $sp->id, 'error' => $e->getMessage()]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Notifikasi berhasil dikirim: {$sent}, gagal: {$failed}",
                'data' => [
                    'sent' => $sent,
                    'failed' => $failed,
                    'total' => count($spIds)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk sending SP notifications', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim notifikasi bulk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check employee's active SP types (API)
     */
    public function checkEmployeeSP(Request $request)
    {
        // Security check
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'manager', 'viewer', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        // Validation
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        try {
            $activeSPs = WarningLetter::where('employee_id', $request->employee_id)
                ->whereIn('status', ['draft', 'aktif']) // Check both draft and aktif
                ->select('sp_type', 'sp_number', 'issue_date', 'status')
                ->get();

            $activeTypes = $activeSPs->pluck('sp_type')->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'active_sps' => $activeSPs,
                    'active_types' => $activeTypes,
                    'can_create_st'  => !in_array('ST', $activeTypes),
                    'can_create_sp1' => !in_array('SP1', $activeTypes),
                    'can_create_sp2' => !in_array('SP2', $activeTypes),
                    'can_create_sp3' => !in_array('SP3', $activeTypes),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking employee SP', [
                'error' => $e->getMessage(),
                'employee_id' => $request->employee_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengecek SP karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate SP number preview (API)
     */
    public function generateNumber(Request $request)
    {
        // Security check
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'manager', 'viewer', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        // Validation
        $request->validate([
            'sp_type' => 'required|in:ST,SP1,SP2,SP3,SP_GABUNGAN',
            'issue_date' => 'required|date',
        ]);

        try {
            // Generate SP number based on issue_date
            $spNumber = WarningLetter::generateSpNumber($request->sp_type, $request->issue_date);

            return response()->json([
                'success' => true,
                'data' => [
                    'sp_number' => $spNumber,
                    'sp_type' => $request->sp_type,
                    'issue_date' => $request->issue_date
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating SP number', [
                'error' => $e->getMessage(),
                'sp_type' => $request->sp_type,
                'issue_date' => $request->issue_date
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate nomor SP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload document for draft SP and auto-activate (API)
     */
    public function uploadDocument(Request $request, $id)
    {
        // Security check
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'manager', 'viewer', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        // Validation
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
            'send_notification' => 'nullable|boolean',
        ]);

        try {
            $sp = WarningLetter::findOrFail($id);

            // Only draft SP can upload document to activate
            if ($sp->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya SP dengan status draft yang bisa di-upload dokumen untuk aktivasi.'
                ], 422);
            }

            DB::beginTransaction();

            // Delete old document if exists
            if ($sp->document_path && Storage::disk()->exists($sp->document_path)) {
                Storage::disk()->delete($sp->document_path);
            }

            // Upload new document
            $file = $request->file('document');
            $filename = 'sp_' . $sp->sp_type . '_' . $sp->employee_id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $documentPath = $file->storeAs('warning_letters', $filename, config('filesystems.default'));

            // Update SP: upload document and auto-activate
            $sp->update([
                'document_path' => $documentPath,
                'status' => 'aktif',
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            $emailFailed = false;
            if ($request->send_notification) {
                try {
                    $this->sendWarningLetterEmail($sp);
                } catch (Throwable $e) {
                    $emailFailed = true;
                    Log::warning('SP document activated but email notification failed', [
                        'sp_id' => $sp->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Warning letter document uploaded and activated', [
                'sp_id' => $sp->id,
                'sp_number' => $sp->sp_number,
                'activated_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $emailFailed
                    ? 'Dokumen berhasil di-upload dan SP aktif, tetapi email gagal dikirim. Periksa pengaturan SMTP.'
                    : 'Dokumen berhasil di-upload. SP telah diaktifkan.',
                'data' => $sp->load(['employee', 'issuer', 'updater'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error uploading SP document', [
                'sp_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal upload dokumen: ' . $e->getMessage()
            ], 500);
        }
    }

    private function sendWarningLetterEmail(WarningLetter $warningLetter): void
    {
        $email = trim((string) ($warningLetter->employee->email ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Email karyawan tidak tersedia atau tidak valid.');
        }

        $smtpService = new EmailSmtpSettingService();
        $smtpService->applyMailer(EmailSmtpSettingService::CONTEXT_NOTIFICATIONS, 'smtp_notifications');

        Mail::mailer('smtp_notifications')->to($email)->send(new WarningLetterMail($warningLetter));
    }

    /**
     * Download SP document (API)
     */
    public function downloadDocument($id)
    {
        // Security check
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'manager', 'viewer', 'superadmin'])) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $sp = WarningLetter::findOrFail($id);

            if (!$sp->document_path || !Storage::disk()->exists($sp->document_path)) {
                abort(404, 'Dokumen tidak ditemukan');
            }

            return Storage::disk()->download($sp->document_path, basename($sp->document_path));
        } catch (\Exception $e) {
            Log::error('Error downloading SP document', [
                'sp_id' => $id,
                'error' => $e->getMessage()
            ]);

            abort(500, 'Gagal mengunduh dokumen');
        }
    }
    /**
     * Export warning letters to Excel
     */
    public function export(Request $request)
    {
        // Security check
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'manager', 'viewer', 'superadmin'])) {
            abort(403, 'Unauthorized action.');
        }

        $filters = $request->only([
            'employee_id', 'sp_type', 'status', 'start_date', 'end_date', 'search'
        ]);

        $fileName = 'Data_Surat_Peringatan_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new WarningLetterExport($filters), $fileName);
    }
}

