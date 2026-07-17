<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailSmtpSetting;
use App\Services\EmailSmtpSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class EmailSmtpSettingController extends Controller
{
    public function __construct(private readonly EmailSmtpSettingService $service)
    {
    }

    public function index()
    {
        $settings = $this->service->getUiSettings();
        $storageReady = $this->service->isStorageReady();
        return view('admin.settings.email-smtp', compact('settings', 'storageReady'));
    }

    public function show(): JsonResponse
    {
        if (!$this->service->isStorageReady()) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel email_smtp_settings belum tersedia. Jalankan migration terlebih dahulu.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'data' => $this->service->getUiSettings(),
        ]);
    }

    public function update(Request $request, string $context): JsonResponse
    {
        if (!$this->service->isStorageReady()) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel email_smtp_settings belum tersedia. Jalankan migration terlebih dahulu.',
            ], 503);
        }

        if (!in_array($context, EmailSmtpSettingService::contexts(), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Konteks email tidak valid',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'smtp_host' => 'required|string|max:191',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_encryption' => 'required|in:tls,ssl,none',
            'smtp_username' => 'required|string|max:191',
            'smtp_password' => 'nullable|string|max:255',
            'from_address' => 'required|email|max:191',
            'from_name' => 'required|string|max:191',
            'interview_subject_template' => 'nullable|string|max:191',
            'interview_body_template' => 'nullable|string',
            'join_call_subject_template' => 'nullable|string|max:191',
            'join_call_body_template' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = [
            'smtp_host' => $request->string('smtp_host')->toString(),
            'smtp_port' => (int) $request->input('smtp_port'),
            'smtp_encryption' => $request->string('smtp_encryption')->toString(),
            'smtp_username' => $request->string('smtp_username')->toString(),
            'from_address' => $request->string('from_address')->toString(),
            'from_name' => $request->string('from_name')->toString(),
            'is_active' => $request->boolean('is_active', true),
        ];

        $setting = EmailSmtpSetting::firstOrNew(['context' => $context]);
        $setting->fill($payload);

        if ($request->filled('smtp_password')) {
            $setting->fill([
                'smtp_password' => $request->string('smtp_password')->toString(),
            ]);
        }

        $hasInvitationTemplateColumns = Schema::hasColumn('email_smtp_settings', 'interview_subject_template')
            && Schema::hasColumn('email_smtp_settings', 'interview_body_template')
            && Schema::hasColumn('email_smtp_settings', 'join_call_subject_template')
            && Schema::hasColumn('email_smtp_settings', 'join_call_body_template');

        if ($context === EmailSmtpSettingService::CONTEXT_INTERVIEW && $hasInvitationTemplateColumns) {
            $setting->fill([
                'interview_subject_template' => $request->filled('interview_subject_template')
                    ? $request->string('interview_subject_template')->toString()
                    : EmailSmtpSettingService::getDefaultInterviewSubjectTemplate(),
                'interview_body_template' => $request->filled('interview_body_template')
                    ? $request->string('interview_body_template')->toString()
                    : EmailSmtpSettingService::getDefaultInterviewBodyTemplate(),
                'join_call_subject_template' => $request->filled('join_call_subject_template')
                    ? $request->string('join_call_subject_template')->toString()
                    : EmailSmtpSettingService::getDefaultJoinCallSubjectTemplate(),
                'join_call_body_template' => $request->filled('join_call_body_template')
                    ? $request->string('join_call_body_template')->toString()
                    : EmailSmtpSettingService::getDefaultJoinCallBodyTemplate(),
            ]);
        }

        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan SMTP email berhasil disimpan',
            'data' => $this->service->getUiSettings()[$context] ?? null,
        ]);
    }

    public function test(Request $request, string $context): JsonResponse
    {
        if (!$this->service->isStorageReady()) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel email_smtp_settings belum tersedia. Jalankan migration terlebih dahulu.',
            ], 503);
        }

        if (!in_array($context, EmailSmtpSettingService::contexts(), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Konteks email tidak valid',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'to_email' => 'required|email|max:191',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $mailerName = "smtp_test_{$context}";
            $config = $this->service->applyMailer($context, $mailerName);

            Mail::mailer($mailerName)->send([], [], function ($message) use ($request, $config, $context) {
                $message->to($request->input('to_email'));
                $message->subject('Test SMTP - ' . strtoupper($context));
                $message->from($config['from_address'], $config['from_name']);
                $message->setBody('Ini adalah email test koneksi SMTP dari Sistem Absensi PT Mingda.', 'text/plain');
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email berhasil dikirim',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal kirim test email: ' . $e->getMessage(),
            ], 500);
        }
    }
}
