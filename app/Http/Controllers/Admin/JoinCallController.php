<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JoinCall;
use App\Models\JoinMessageTemplate;
use App\Models\SubDepartment;
use App\Models\WhatsAppSetting;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class JoinCallController extends Controller
{
    /**
     * Display join_calls list page
     */
    public function index(Request $request)
    {
        $query = JoinCall::with('subDepartment')
            ->orderBy('join_call_date', 'desc')
            ->orderBy('join_call_time', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('join_call_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('join_call_date', '<=', $request->date_to);
        }

        // Filter by sub department
        if ($request->filled('sub_department_id')) {
            $query->where('sub_department_id', $request->sub_department_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('candidate_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $join_calls = $query->paginate($request->input('per_page', 10));

        $subDepartments = SubDepartment::orderBy('name')->get();

        // Statistics
        $stats = [
            'total' => JoinCall::count(),
            'scheduled' => JoinCall::where('status', 'scheduled')->count(),
            'confirmed' => JoinCall::where('status', 'confirmed')->count(),
            'completed' => JoinCall::where('status', 'completed')->count(),
            'cancelled' => JoinCall::where('status', 'cancelled')->count(),
        ];

        return view('admin.join_calls.index', compact('join_calls', 'subDepartments', 'stats'));
    }

    /**
     * Store new join call
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'candidate_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'sub_department_id' => 'required|exists:sub_departments,id',
            'join_call_date' => 'required|date',
            'join_call_time' => 'required',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'custom_message_template' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $joinCall = JoinCall::create([
                'candidate_name' => $request->candidate_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'sub_department_id' => $request->sub_department_id,
                'join_call_date' => $request->join_call_date,
                'join_call_time' => $request->join_call_time,
                'location' => $request->location ?? 'Kantor PT Mingda',
                'notes' => $request->notes,
                'custom_message_template' => $request->custom_message_template,
                'status' => 'scheduled',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Panggilan Join berhasil dijadwalkan',
                'data' => $joinCall->load('subDepartment')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menjadwalkan panggilan join: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get join call detail
     */
    public function show($id)
    {
        try {
            $joinCall = JoinCall::with('subDepartment')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $joinCall
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Panggilan Join tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Update join call
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'candidate_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'sub_department_id' => 'required|exists:sub_departments,id',
            'join_call_date' => 'required|date',
            'join_call_time' => 'required',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'custom_message_template' => 'nullable|string',
            'status' => 'nullable|in:scheduled,notified,confirmed,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $joinCall = JoinCall::findOrFail($id);

            $joinCall->update([
                'candidate_name' => $request->candidate_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'sub_department_id' => $request->sub_department_id,
                'join_call_date' => $request->join_call_date,
                'join_call_time' => $request->join_call_time,
                'location' => $request->location ?? $joinCall->location,
                'notes' => $request->notes,
                'custom_message_template' => $request->custom_message_template,
                'status' => $request->status ?? $joinCall->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Panggilan Join berhasil diupdate',
                'data' => $joinCall->load('subDepartment')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate panggilan join: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete join call
     */
    public function destroy($id)
    {
        try {
            $joinCall = JoinCall::findOrFail($id);
            $joinCall->delete();

            return response()->json([
                'success' => true,
                'message' => 'Panggilan Join berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus panggilan join'
            ], 500);
        }
    }

    /**
     * Bulk delete join calls
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:join_calls,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $deleted = JoinCall::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "Berhasil menghapus {$deleted} panggilan join",
                'data' => [
                    'deleted' => $deleted
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus panggilan join: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send WhatsApp notification to single candidate
     */
    public function sendNotification($id)
    {
        try {
            $joinCall = JoinCall::with('subDepartment')->findOrFail($id);

            $whatsapp = new WhatsAppService();

            // Load custom API key & sender for join_call
            $setting = WhatsAppSetting::getActive();
            $customApiKey = $setting?->join_call_api_key ?: null;
            $customSender  = $setting?->join_call_sender  ?: null;

            // Format message
            $message = $this->formatWhatsAppMessage($joinCall);

            // Get QR code image URL
            $qrImageUrl = $joinCall->qr_code_image;

            // Send WhatsApp with custom API key/sender if configured
            $sent = $whatsapp->send($joinCall->phone, $message, $qrImageUrl, $customSender, $customApiKey);

            if ($sent) {
                $joinCall->update([
                    'status'     => 'notified',
                    'wa_sent_at' => now(),
                    'wa_message' => $message,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Notifikasi WhatsApp berhasil dikirim' . ($customApiKey ? ' (API key custom)' : '')
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim notifikasi WhatsApp. Pastikan konfigurasi WhatsApp sudah benar.'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim notifikasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send WhatsApp notification to multiple candidates (Blast)
     */
    public function bulkSendNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'exists:join_calls,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $joinCalls = JoinCall::with('subDepartment')->whereIn('id', $request->ids)->get();
            $whatsapp  = new WhatsAppService();

            // Load custom API key & sender for join_call (once for the batch)
            $setting      = WhatsAppSetting::getActive();
            $customApiKey = $setting?->join_call_api_key ?: null;
            $customSender  = $setting?->join_call_sender  ?: null;

            $sent   = 0;
            $failed = 0;

            /** @var JoinCall $joinCall */
            foreach ($joinCalls as $joinCall) {
                $message    = $this->formatWhatsAppMessage($joinCall);
                $qrImageUrl = $joinCall->qr_code_image;

                if ($whatsapp->send($joinCall->phone, $message, $qrImageUrl, $customSender, $customApiKey)) {
                    $joinCall->update([
                        'status'     => 'notified',
                        'wa_sent_at' => now(),
                        'wa_message' => $message,
                    ]);
                    $sent++;
                } else {
                    $failed++;
                }

                // Delay to prevent rate limiting
                usleep(500000); // 0.5 second
            }

            return response()->json([
                'success' => true,
                'message' => "Blast WhatsApp selesai: {$sent} berhasil, {$failed} gagal" . ($customApiKey ? ' (API key custom)' : ''),
                'data'    => [
                    'sent'   => $sent,
                    'failed' => $failed,
                    'total'  => $joinCalls->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan blast WhatsApp: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format WhatsApp message template
     */
    protected function formatWhatsAppMessage(JoinCall $joinCall)
    {
        $date = Carbon::parse($joinCall->join_call_date)->locale('id')->translatedFormat('l, d F Y');
        $time = Carbon::parse($joinCall->join_call_time)->format('H:i');
        $department = $joinCall->subDepartment?->name ?? '-';

        // Use custom template if available, otherwise use default
        if ($joinCall->custom_message_template) {
            // Replace placeholders with actual values
            $message = $joinCall->custom_message_template;
            $message = str_replace('{nama}', $joinCall->candidate_name, $message);
            $message = str_replace('{departemen}', $department, $message);
            $message = str_replace('{sub_departemen}', $department, $message);
            $message = str_replace('{sub_department}', $department, $message);
            // Backward compatibility for old templates that still use {posisi}
            $message = str_replace('{posisi}', $department, $message);
            $message = str_replace('{tanggal}', $date, $message);
            $message = str_replace('{waktu}', $time, $message);
            $message = str_replace('{lokasi}', $joinCall->location, $message);
            $message = str_replace('{catatan}', $joinCall->notes ?? '', $message);
        } else {
            // Default template - use hex-encoded UTF-8 bytes to avoid encoding issues
            $message = "*Panggilan Bergabung - PT Mingda*\n\n"
                . "Kepada Yth,\n"
                . "*{$joinCall->candidate_name}*\n\n"
                . "Selamat! Berdasarkan hasil seleksi, Anda dinyatakan diterima untuk bergabung di PT Mingda pada departemen *{$department}*.\n\n"
                . "Kami mengundang Anda untuk hadir pada:\n"
                . "\xF0\x9F\x93\x85 *Tanggal:* {$date}\n"
                . "\xF0\x9F\x95\x90 *Waktu:* {$time} WIB\n"
                . "\xF0\x9F\x93\x8D *Lokasi:* {$joinCall->location}\n\n"
                . ($joinCall->notes ? "\xF0\x9F\x93\x9D *Catatan:*\n{$joinCall->notes}\n\n" : '')
                . "Mohon konfirmasi kehadiran Anda dengan membalas pesan ini.\n\n"
                . "Terima kasih dan selamat bergabung.\n\n"
                . "*HRD PT Mingda*";
        }

        // Add QR code section if token exists
        if ($joinCall->qr_code_token) {
            $qrUrl = url('/join-call/scan/' . $joinCall->qr_code_token);
            $message .= "\n\n------------------\n\n"
                . "\xF0\x9F\x93\x8B *QR Code Check-in*\n\n"
                . "Silakan tunjukkan QR Code berikut kepada petugas keamanan saat tiba di lokasi:\n\n"
                . "{$qrUrl}\n\n"
                . "Atau scan QR Code pada gambar yang kami kirimkan.";
        }

        return $message;
    }

    /**
     * Get all active message templates
     */
    public function getTemplates()
    {
        try {
            $templates = JoinMessageTemplate::getActive();

            return response()->json([
                'success' => true,
                'data' => $templates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat template'
            ], 500);
        }
    }

    /**
     * Save new template from current message
     */
    public function saveTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'message_template' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $template = JoinMessageTemplate::create([
                'name' => $request->name,
                'message_template' => $request->message_template,
                'is_default' => false,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template berhasil disimpan',
                'data' => $template
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update existing template
     */
    public function updateTemplate(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'message_template' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $template = JoinMessageTemplate::findOrFail($id);

            // Cannot edit default template name
            if ($template->is_default && $request->name !== $template->name) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nama template default tidak dapat diubah'
                ], 403);
            }

            $template->update([
                'name' => $request->name,
                'message_template' => $request->message_template,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template berhasil diupdate',
                'data' => $template
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete template
     */
    public function deleteTemplate($id)
    {
        try {
            $template = JoinMessageTemplate::findOrFail($id);

            // Cannot delete default template
            if ($template->is_default) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template default tidak dapat dihapus'
                ], 403);
            }

            $template->delete();

            return response()->json([
                'success' => true,
                'message' => 'Template berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus template'
            ], 500);
        }
    }

    /**
     * Download Excel template for import
     */
    public function downloadTemplate()
    {
        return Excel::download(new \App\Exports\JoinCallTemplateExport, 'Template_Import_Join_Call.xlsx');
    }

    /**
     * Import join calls from Excel
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls|max:5120', // Max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak valid. Pastikan file berformat Excel (.xlsx atau .xls) dan ukuran maksimal 5MB',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $import = new \App\Imports\JoinCallImport;
            
            Excel::import($import, $file);

            $failures = $import->failures();
            $errors = $import->errors();

            if (count($failures) > 0 || count($errors) > 0) {
                $errorMessages = [];
                
                // Format validation failures
                foreach ($failures as $failure) {
                    $errorMessages[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
                }
                
                // Format general errors
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Import gagal dengan ' . count($errorMessages) . ' error',
                    'errors' => $errorMessages
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data Panggilan Join berhasil diimport'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport data: ' . $e->getMessage()
            ], 500);
        }
    }
}
