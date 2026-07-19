<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailSmtpSetting;
use App\Services\EmailSmtpSettingService;
use App\Services\MailgunLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class MailgunLogController extends Controller
{
    public function __construct(private readonly MailgunLogService $service)
    {
    }

    /**
     * Render the Mailgun log viewer page.
     */
    public function index()
    {
        $storageReady = Schema::hasTable('email_smtp_settings')
            && Schema::hasColumn('email_smtp_settings', 'mailgun_api_key');

        $configs = [];
        if ($storageReady) {
            foreach (EmailSmtpSettingService::contexts() as $context) {
                $record = EmailSmtpSetting::query()->where('context', $context)->first();
                $configs[$context] = [
                    'has_api_key' => $record && !empty($record->attributes['mailgun_api_key'] ?? null),
                    'domain'      => $record?->mailgun_domain ?? '',
                ];
            }
        }

        return view('admin.settings.email-logs', compact('storageReady', 'configs'));
    }

    /**
     * GET /api/settings/mailgun-logs/events
     * Fetch events from Mailgun API.
     */
    public function events(Request $request): JsonResponse
    {
        if (!Schema::hasColumn('email_smtp_settings', 'mailgun_api_key')) {
            return response()->json([
                'success' => false,
                'message' => 'Kolom mailgun_api_key belum tersedia. Jalankan migration terlebih dahulu.',
            ], 503);
        }

        $validator = Validator::make($request->all(), [
            'context'          => 'nullable|in:notifications,interview,all',
            'event'            => 'nullable|string|in:accepted,delivered,failed,bounced,complained,unsubscribed,opened,clicked',
            'recipient'        => 'nullable|email',
            'begin'            => 'nullable|date',
            'end'              => 'nullable|date',
            'limit'            => 'nullable|integer|min:1|max:300',
            'cursor'           => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tidak valid',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $context = $request->input('context', 'all');
        $filters = array_filter([
            'event'            => $request->input('event'),
            'recipient'        => $request->input('recipient'),
            'begin'            => $request->input('begin'),
            'end'              => $request->input('end'),
            'limit'            => $request->input('limit', 50),
        ], fn($v) => $v !== null && $v !== '');

        // Cursor-based pagination: decode JSON cursor token
        $cursorRaw = $request->input('cursor');
        if ($cursorRaw) {
            $decoded = json_decode($cursorRaw, true);
            if (is_array($decoded) && !empty($decoded['p'])) {
                $filters['cursor']           = $decoded['p'];
                $filters['cursor_direction'] = $decoded['page'] ?? 'next';
            }
        }

        if ($context === 'all') {
            $result = $this->service->fetchAllContextsEvents($filters);
        } else {
            $result = $this->service->fetchEvents($context, $filters);
            foreach ($result['data'] as &$item) {
                $item['_context'] = $context;
            }
        }

        if (!$result['success'] && empty($result['data'])) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Gagal mengambil data dari Mailgun',
                'data'    => [],
                'next_cursor' => null,
                'prev_cursor' => null,
            ], 422);
        }

        return response()->json([
            'success'     => true,
            'data'        => $result['data'],
            'next_cursor' => $result['next_cursor'] ?? null,
            'prev_cursor' => $result['prev_cursor'] ?? null,
            'warning'     => $result['error'],
            'total'       => count($result['data']),
        ]);
    }

    /**
     * POST /api/settings/mailgun-logs/config/{context}
     * Save Mailgun API Key + Domain for a context.
     */
    public function saveConfig(Request $request, string $context): JsonResponse
    {
        if (!Schema::hasTable('email_smtp_settings')
            || !Schema::hasColumn('email_smtp_settings', 'mailgun_api_key')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel email_smtp_settings belum tersedia. Jalankan migration.',
            ], 503);
        }

        if (!in_array($context, EmailSmtpSettingService::contexts(), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Konteks email tidak valid',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'mailgun_api_key' => 'nullable|string|max:255',
            'mailgun_domain'  => 'nullable|string|max:191',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $setting = EmailSmtpSetting::firstOrNew(['context' => $context]);

        if ($request->filled('mailgun_domain')) {
            $setting->mailgun_domain = $request->string('mailgun_domain')->toString();
        }

        if ($request->filled('mailgun_api_key')) {
            $setting->mailgun_api_key = $request->string('mailgun_api_key')->toString();
        } elseif ($request->has('mailgun_api_key') && $request->input('mailgun_api_key') === '') {
            // Explicit clear
            $setting->attributes['mailgun_api_key'] = null;
        }

        // Ensure the record exists (set default context if new)
        if (!$setting->exists && !$setting->context) {
            $setting->context = $context;
        }

        $setting->save();

        return response()->json([
            'success' => true,
            'message' => "Konfigurasi Mailgun untuk konteks [{$context}] berhasil disimpan.",
            'data'    => [
                'context'         => $context,
                'has_api_key'     => !empty($setting->attributes['mailgun_api_key'] ?? null),
                'mailgun_domain'  => $setting->mailgun_domain,
            ],
        ]);
    }

    /**
     * POST /api/settings/mailgun-logs/test/{context}
     * Test Mailgun API Key connectivity.
     */
    public function testConnection(string $context): JsonResponse
    {
        if (!in_array($context, EmailSmtpSettingService::contexts(), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Konteks email tidak valid',
            ], 422);
        }

        $result = $this->service->testConnection($context);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['success'] ? 200 : 422);
    }
}
