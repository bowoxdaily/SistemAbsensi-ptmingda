<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Interview;
use App\Models\InterviewMessageTemplate;
use App\Models\Position;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class InterviewController extends Controller
{
    /**
     * Display interview list page
     */
    public function index(Request $request)
    {
        $query = Interview::with('position')
            ->orderBy('interview_date', 'desc')
            ->orderBy('interview_time', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('interview_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('interview_date', '<=', $request->date_to);
        }

        // Filter by position
        if ($request->filled('position_id')) {
            $query->where('position_id', $request->position_id);
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

        $interviews = $query->paginate($request->input('per_page', 10));

        $positions = Position::orderBy('name')->get();

        // Statistics
        $stats = [
            'total' => Interview::count(),
            'scheduled' => Interview::where('status', 'scheduled')->count(),
            'confirmed' => Interview::where('status', 'confirmed')->count(),
            'completed' => Interview::where('status', 'completed')->count(),
            'cancelled' => Interview::where('status', 'cancelled')->count(),
        ];

        return view('admin.interviews.index', compact('interviews', 'positions', 'stats'));
    }

    /**
     * Store new interview
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'candidate_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'position_id' => 'required|exists:positions,id',
            'interview_date' => 'required|date',
            'interview_time' => 'required',
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
            $interview = Interview::create([
                'candidate_name' => $request->candidate_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'position_id' => $request->position_id,
                'interview_date' => $request->interview_date,
                'interview_time' => $request->interview_time,
                'location' => $request->location ?? 'Kantor PT Mingda',
                'notes' => $request->notes,
                'custom_message_template' => $request->custom_message_template,
                'status' => 'scheduled',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Interview berhasil dijadwalkan',
                'data' => $interview->load('position')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menjadwalkan interview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get interview detail
     */
    public function show($id)
    {
        try {
            $interview = Interview::with('position')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $interview
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Interview tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Update interview
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'candidate_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'position_id' => 'required|exists:positions,id',
            'interview_date' => 'required|date',
            'interview_time' => 'required',
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
            $interview = Interview::findOrFail($id);

            $interview->update([
                'candidate_name' => $request->candidate_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'position_id' => $request->position_id,
                'interview_date' => $request->interview_date,
                'interview_time' => $request->interview_time,
                'location' => $request->location ?? $interview->location,
                'notes' => $request->notes,
                'custom_message_template' => $request->custom_message_template,
                'status' => $request->status ?? $interview->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Interview berhasil diupdate',
                'data' => $interview->load('position')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate interview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete interview
     */
    public function destroy($id)
    {
        try {
            $interview = Interview::findOrFail($id);
            $interview->delete();

            return response()->json([
                'success' => true,
                'message' => 'Interview berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus interview'
            ], 500);
        }
    }

    /**
     * Send WhatsApp notification to single candidate
     */
    public function sendNotification($id)
    {
        try {
            $interview = Interview::with('position')->findOrFail($id);

            $whatsapp = new WhatsAppService();

            // Format message
            $message = $this->formatWhatsAppMessage($interview);

            // Send WhatsApp
            $sent = $whatsapp->send($interview->phone, $message);

            if ($sent) {
                $interview->update([
                    'status' => 'notified',
                    'wa_sent_at' => now(),
                    'wa_message' => $message,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Notifikasi WhatsApp berhasil dikirim'
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
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:interviews,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $interviews = Interview::with('position')->whereIn('id', $request->ids)->get();
            $whatsapp = new WhatsAppService();

            $sent = 0;
            $failed = 0;

            foreach ($interviews as $interview) {
                $message = $this->formatWhatsAppMessage($interview);

                if ($whatsapp->send($interview->phone, $message)) {
                    $interview->update([
                        'status' => 'notified',
                        'wa_sent_at' => now(),
                        'wa_message' => $message,
                    ]);
                    $sent++;
                } else {
                    $failed++;
                }

                // Delay to prevent rate limiting
                usleep(500000); // 0.5 second delay
            }

            return response()->json([
                'success' => true,
                'message' => "Blast WhatsApp selesai: {$sent} berhasil, {$failed} gagal",
                'data' => [
                    'sent' => $sent,
                    'failed' => $failed,
                    'total' => $interviews->count()
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
    protected function formatWhatsAppMessage(Interview $interview)
    {
        $date = Carbon::parse($interview->interview_date)->locale('id')->translatedFormat('l, d F Y');
        $time = Carbon::parse($interview->interview_time)->format('H:i');

        // Use custom template if available, otherwise use default
        if ($interview->custom_message_template) {
            // Replace placeholders with actual values
            $message = $interview->custom_message_template;
            $message = str_replace('{nama}', $interview->candidate_name, $message);
            $message = str_replace('{posisi}', $interview->position->name, $message);
            $message = str_replace('{tanggal}', $date, $message);
            $message = str_replace('{waktu}', $time, $message);
            $message = str_replace('{lokasi}', $interview->location, $message);
            $message = str_replace('{catatan}', $interview->notes ?? '', $message);
            return $message;
        }

        // Default template
        return "*Undangan Interview - PT Mingda*\n\n"
            . "Kepada Yth,\n"
            . "*{$interview->candidate_name}*\n\n"
            . "Berdasarkan hasil seleksi berkas Anda, kami mengundang Anda untuk mengikuti sesi interview untuk posisi *{$interview->position->name}*.\n\n"
            . "📅 *Tanggal:* {$date}\n"
            . "🕐 *Waktu:* {$time} WIB\n"
            . "📍 *Lokasi:* {$interview->location}\n\n"
            . ($interview->notes ? "📝 *Catatan:*\n{$interview->notes}\n\n" : '')
            . "Mohon konfirmasi kehadiran Anda dengan membalas pesan ini.\n\n"
            . "Terima kasih dan sampai jumpa di hari interview.\n\n"
            . "*HRD PT Mingda*";
    }

    /**
     * Get all active message templates
     */
    public function getTemplates()
    {
        try {
            $templates = InterviewMessageTemplate::getActive();

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
            $template = InterviewMessageTemplate::create([
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
            $template = InterviewMessageTemplate::findOrFail($id);

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
            $template = InterviewMessageTemplate::findOrFail($id);

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
}
