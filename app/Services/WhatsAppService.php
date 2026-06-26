<?php

namespace App\Services;

use App\Models\WhatsAppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $setting;
    protected $lastError;

    public function __construct()
    {
        $this->setting = WhatsAppSetting::getActive();
        $this->lastError = null;
    }

    /**
     * Get last error detail from send operation
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Send WhatsApp message
     */
    public function send($phoneNumber, $message, $image = null, $customSender = null, $apiKey = null, $delay = '2')
    {
        $this->lastError = null;

        if (!$this->setting || !$this->setting->is_enabled) {
            Log::info('WhatsApp notification disabled or not configured');
            $this->lastError = 'WhatsApp belum diaktifkan atau belum dikonfigurasi.';
            return false;
        }

        try {
            if ($this->setting->isFonnte()) {
                return $this->sendViaFonnte($phoneNumber, $message, $image, $customSender, $apiKey, $delay);
            } elseif ($this->setting->isKirimDev()) {
                return $this->sendViaKirimDev($phoneNumber, $message, $image, $customSender, $apiKey);
            } elseif ($this->setting->isBaileys()) {
                return $this->sendViaBaileys($phoneNumber, $message, $image, $customSender);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp Send Error: ' . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Send WhatsApp template message
     */
    public function sendTemplate($phoneNumber, $templateName, $languageCode = 'id', array $parameters = [], $customSender = null, $apiKey = null)
    {
        $this->lastError = null;

        if (!$this->setting || !$this->setting->is_enabled) {
            $this->lastError = 'WhatsApp belum diaktifkan atau belum dikonfigurasi.';
            return false;
        }

        try {
            if ($this->setting->isKirimDev()) {
                return $this->sendTemplateViaKirimDev($phoneNumber, $templateName, $languageCode, $parameters, $customSender, $apiKey);
            }

            $this->lastError = 'Kirim template saat ini hanya didukung untuk provider Kirim.dev.';
            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp Send Template Error: ' . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Get sender number for specific notification type
     */
    protected function getSenderFor($notificationType)
    {
        $senderField = $notificationType . '_sender';

        // Use custom sender if set, otherwise use default sender
        if (isset($this->setting->$senderField) && !empty($this->setting->$senderField)) {
            return $this->setting->$senderField;
        }

        return $this->setting->sender;
    }

    /**
     * Get API key for specific notification type
     */
    protected function getApiKeyFor($notificationType)
    {
        $apiKeyField = $notificationType . '_api_key';

        // Use custom API key if set, otherwise use default api_key
        if (isset($this->setting->$apiKeyField) && !empty($this->setting->$apiKeyField)) {
            Log::info('Using custom API key for ' . $notificationType);
            return $this->setting->$apiKeyField;
        }

        Log::info('Using default API key for ' . $notificationType);
        return $this->setting->api_key;
    }

    /**
     * Send via Fonnte API
     */
    protected function sendViaFonnte($phoneNumber, $message, $image = null, $customSender = null, $apiKey = null, $delay = '2')
    {
        $url = 'https://api.fonnte.com/send';

        $data = [
            'target' => $this->formatPhoneNumber($phoneNumber),
            'message' => $message,
            'countryCode' => '62', // Indonesia
            'delay' => (string) $delay
        ];

        if ($image) {
            $data['url'] = $image;
        }

        // Use provided API key or fallback to default
        $useApiKey = $apiKey ?: $this->setting->api_key;

        $attemptSend = function ($keyToUse, $usingCustomKey) use ($url, $data, $phoneNumber, $customSender) {
            $response = Http::withHeaders([
                'Authorization' => $keyToUse,
            ])->post($url, $data);

            $json = $response->json();
            $status = is_array($json) ? (bool) ($json['status'] ?? false) : false;

            if ($response->successful() && $status) {
                Log::info('WhatsApp sent via Fonnte', [
                    'phone' => $phoneNumber,
                    'custom_sender' => $customSender,
                    'using_custom_api_key' => $usingCustomKey,
                    'response' => $json,
                ]);
                return [true, $json, null, $response->status()];
            }

            $errorReason = is_array($json)
                ? ($json['reason'] ?? $json['message'] ?? 'Unknown Fonnte error')
                : ('HTTP ' . $response->status());

            Log::error('Fonnte API Error', [
                'phone' => $phoneNumber,
                'using_custom_api_key' => $usingCustomKey,
                'http_status' => $response->status(),
                'error_reason' => $errorReason,
                'response' => $response->body(),
            ]);

            return [false, $json, $errorReason, $response->status()];
        };

        [$ok, $json, $errorReason] = $attemptSend($useApiKey, !is_null($apiKey));
        if ($ok) {
            $this->lastError = null;
            return true;
        }

        // If custom key fails due auth/token issues, retry once with default key.
        if (!is_null($apiKey) && !empty($this->setting->api_key) && $this->setting->api_key !== $apiKey) {
            $reasonLower = strtolower((string) $errorReason);
            $shouldRetryWithDefault = str_contains($reasonLower, 'token')
                || str_contains($reasonLower, 'auth')
                || str_contains($reasonLower, 'unauthorized');

            if ($shouldRetryWithDefault) {
                Log::warning('Retrying Fonnte send with default API key after custom key failure', [
                    'phone' => $phoneNumber,
                    'failure_reason' => $errorReason,
                ]);

                [$fallbackOk] = $attemptSend($this->setting->api_key, false);
                if (!$fallbackOk) {
                    $this->lastError = (string) $errorReason;
                }
                return $fallbackOk;
            }
        }

        $this->lastError = (string) $errorReason;

        return false;
    }

    /**
     * Send via Baileys (self-hosted)
     *
     * Note: $customSender can be used if your Baileys server supports multiple devices
     */
    protected function sendViaBaileys($phoneNumber, $message, $image = null, $customSender = null)
    {
        $url = rtrim($this->setting->api_url, '/') . '/send-message';

        $data = [
            'phone' => $this->formatPhoneNumber($phoneNumber),
            'message' => $message,
        ];

        // If your Baileys server supports sender selection, uncomment below:
        // if ($customSender) {
        //     $data['sender'] = $customSender;
        // }

        if ($image) {
            $data['image'] = $image;
        }

        $response = Http::timeout(10)->post($url, $data);

        if ($response->successful()) {
            Log::info('WhatsApp sent via Baileys', [
                'phone' => $phoneNumber,
                'response' => $response->json(),
            ]);
            $this->lastError = null;
            return true;
        }

        Log::error('Baileys API Error', [
            'phone' => $phoneNumber,
            'response' => $response->body(),
        ]);
        $this->lastError = 'Baileys API error: HTTP ' . $response->status();
        return false;
    }

    /**
     * Send via Kirimdev API
     */
    protected function sendViaKirimDev($phoneNumber, $message, $image = null, $customSender = null, $apiKey = null)
    {
        $useApiKey = $apiKey ?: $this->setting->api_key;
        // For Kirimdev, sender number is not used as path. Always use Meta phone_number_id.
        $phoneNumberId = $this->setting->kirim_phone_number_id;

        if (!$useApiKey) {
            Log::error('Kirimdev send failed: API key is empty');
            $this->lastError = 'API key Kirim.dev kosong.';
            return false;
        }

        if (!$phoneNumberId) {
            Log::error('Kirimdev send failed: kirim_phone_number_id is empty');
            $this->lastError = 'Phone Number ID Kirim.dev kosong.';
            return false;
        }

        if (!empty($customSender)) {
            Log::info('Kirimdev ignoring custom sender; using kirim_phone_number_id from settings', [
                'custom_sender' => $customSender,
                'phone_number_id' => $phoneNumberId,
            ]);
        }

        $to = '+' . $this->formatPhoneNumber($phoneNumber);
        $url = 'https://api.kirimdev.com/v1/' . trim((string) $phoneNumberId) . '/messages';

        if ($image) {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'image',
                'image' => [
                    'link' => $image,
                ],
            ];

            // Kirimdev/Meta media messages can carry text in image.caption.
            // This keeps interview/join-call details together with the QR barcode.
            if (!empty($message)) {
                $payload['image']['caption'] = $message;
            }
        } else {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'body' => $message,
                ],
            ];
        }

        $response = Http::timeout(20)
            ->withToken($useApiKey)
            ->acceptJson()
            ->post($url, $payload);

        $json = $response->json();
        $hasData = is_array($json) && isset($json['data']);

        if ($response->successful() && $hasData) {
            Log::info('WhatsApp sent via Kirimdev', [
                'phone' => $to,
                'phone_number_id' => $phoneNumberId,
                'message_id' => $json['data']['id'] ?? null,
            ]);
            $this->lastError = null;
            return true;
        }

        $providerCode = is_array($json) ? ($json['code'] ?? null) : null;
        $providerMessage = is_array($json) ? ($json['message'] ?? null) : null;
        $metaCode = is_array($json) && isset($json['meta']) && is_array($json['meta'])
            ? ($json['meta']['code'] ?? null)
            : null;

        if ((string) $providerCode === 'undeliverable' && (string) $metaCode === '131047') {
            $this->lastError = 'Pelanggan tidak membalas dalam 24 jam terakhir. Kirim pesan template (approved) untuk membuka kembali percakapan.';
        } else {
            $parts = [];
            if ($providerCode) {
                $parts[] = 'code: ' . $providerCode;
            }
            if ($metaCode) {
                $parts[] = 'meta: ' . $metaCode;
            }
            if ($providerMessage) {
                $parts[] = $providerMessage;
            }
            $this->lastError = !empty($parts)
                ? implode(' | ', $parts)
                : ('Kirim.dev API error HTTP ' . $response->status());
        }

        Log::error('Kirimdev API Error', [
            'phone' => $to,
            'phone_number_id' => $phoneNumberId,
            'http_status' => $response->status(),
            'response' => $response->body(),
            'parsed_error' => $this->lastError,
        ]);

        return false;
    }

    /**
     * Send approved WhatsApp template via Kirimdev API
     */
    protected function sendTemplateViaKirimDev($phoneNumber, $templateName, $languageCode = 'id', array $parameters = [], $customSender = null, $apiKey = null)
    {
        $useApiKey = $apiKey ?: $this->setting->api_key;
        $phoneNumberId = $this->setting->kirim_phone_number_id;

        if (!$useApiKey) {
            $this->lastError = 'API key Kirim.dev kosong.';
            return false;
        }

        if (!$phoneNumberId) {
            $this->lastError = 'Phone Number ID Kirim.dev kosong.';
            return false;
        }

        $to = '+' . $this->formatPhoneNumber($phoneNumber);
        $url = 'https://api.kirimdev.com/v1/' . trim((string) $phoneNumberId) . '/messages';

        $components = [];
        if (!empty($parameters)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(function ($value) {
                    return [
                        'type' => 'text',
                        'text' => (string) $value,
                    ];
                }, $parameters),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        $response = Http::timeout(20)
            ->withToken($useApiKey)
            ->acceptJson()
            ->post($url, $payload);

        $json = $response->json();
        $hasData = is_array($json) && isset($json['data']);

        if ($response->successful() && $hasData) {
            Log::info('WhatsApp template sent via Kirimdev', [
                'phone' => $to,
                'phone_number_id' => $phoneNumberId,
                'template_name' => $templateName,
                'message_id' => $json['data']['id'] ?? null,
            ]);
            $this->lastError = null;
            return true;
        }

        $providerCode = is_array($json) ? ($json['code'] ?? null) : null;
        $providerMessage = is_array($json) ? ($json['message'] ?? null) : null;
        $metaCode = is_array($json) && isset($json['meta']) && is_array($json['meta'])
            ? ($json['meta']['code'] ?? null)
            : null;

        $parts = [];
        if ($providerCode) {
            $parts[] = 'code: ' . $providerCode;
        }
        if ($metaCode) {
            $parts[] = 'meta: ' . $metaCode;
        }
        if ($providerMessage) {
            $parts[] = $providerMessage;
        }
        $this->lastError = !empty($parts)
            ? implode(' | ', $parts)
            : ('Kirim.dev API error HTTP ' . $response->status());

        Log::error('Kirimdev Template API Error', [
            'phone' => $to,
            'phone_number_id' => $phoneNumberId,
            'template_name' => $templateName,
            'http_status' => $response->status(),
            'response' => $response->body(),
            'parsed_error' => $this->lastError,
        ]);

        return false;
    }

    /**
     * Send check-in notification
     */
    public function sendCheckinNotification($attendance)
    {
        if (!$this->setting || !$this->setting->notify_checkin) {
            return false;
        }

        $employee = $attendance->employee;
        $template = $this->setting->checkin_template ?? WhatsAppSetting::getDefaultCheckinTemplate();

        $message = $this->replaceVariables($template, [
            'name' => $employee->name,
            'time' => $attendance->check_in ?? '-',
            'status' => $this->getStatusLabel($attendance->status),
            'location' => $attendance->location_in ?? 'Tidak ada data lokasi',
        ]);

        $phone = $employee->phone;
        // Send photo only if admin enabled the option
        $image = null;
        if ($this->setting->send_checkin_photo && $attendance->photo_in) {
            $image = asset('storage/' . $attendance->photo_in);
        }

        return $this->send($phone, $message, $image);
    }

    /**
     * Send check-out notification
     */
    public function sendCheckoutNotification($attendance)
    {
        if (!$this->setting || !$this->setting->notify_checkout) {
            return false;
        }

        $employee = $attendance->employee;
        $template = $this->setting->checkout_template ?? WhatsAppSetting::getDefaultCheckoutTemplate();

        $duration = $this->calculateDuration(
            $attendance->check_in,
            $attendance->check_out
        );

        $message = $this->replaceVariables($template, [
            'name' => $employee->name,
            'time' => $attendance->check_out ?? '-',
            'duration' => $duration,
            'status' => $this->getStatusLabel($attendance->status),
            'location' => $attendance->location_out ?? 'Tidak ada data lokasi',
        ]);

        $phone = $employee->phone;
        // Send photo only if admin enabled the option
        $image = null;
        if ($this->setting->send_checkout_photo && $attendance->photo_out) {
            $image = asset('storage/' . $attendance->photo_out);
        }

        return $this->send($phone, $message, $image);
    }

    /**
     * Replace template variables
     */
    protected function replaceVariables($template, $variables)
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }

    /**
     * Format phone number for WhatsApp (remove leading 0, add 62)
     */
    protected function formatPhoneNumber($phone)
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove leading 0
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }

        // Add 62 if not present
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }

        return $phone;
    }

    /**
     * Get status label in Indonesian
     */
    protected function getStatusLabel($status)
    {
        $labels = [
            'hadir' => '✅ Hadir',
            'terlambat' => '⚠️ Terlambat',
            'cuti' => '📅 Cuti',
            'izin' => '📝 Izin',
            'sakit' => '🏥 Sakit',
            'alpha' => '❌ Alpha',
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Calculate work duration
     */
    protected function calculateDuration($checkIn, $checkOut)
    {
        if (!$checkIn || !$checkOut) {
            return '-';
        }

        $start = strtotime($checkIn);
        $end = strtotime($checkOut);
        $diff = $end - $start;

        $hours = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);

        return sprintf('%d jam %d menit', $hours, $minutes);
    }

    /**
     * Test connection
     */
    public function testConnection()
    {
        if (!$this->setting) {
            return [
                'success' => false,
                'message' => 'WhatsApp settings not configured',
            ];
        }

        if (!$this->setting->api_key) {
            return [
                'success' => false,
                'message' => 'API Key belum diisi. Silakan isi API Key terlebih dahulu.',
            ];
        }

        try {
            if ($this->setting->isKirimDev()) {
                $meResponse = Http::timeout(15)
                    ->withToken($this->setting->api_key)
                    ->acceptJson()
                    ->get('https://api.kirimdev.com/v1/me');

                if (!$meResponse->successful()) {
                    return [
                        'success' => false,
                        'message' => 'Koneksi Kirimdev gagal: ' . $meResponse->body(),
                    ];
                }

                $accountsResponse = Http::timeout(15)
                    ->withToken($this->setting->api_key)
                    ->acceptJson()
                    ->get('https://api.kirimdev.com/v1/accounts');

                $accounts = [];
                if ($accountsResponse->successful()) {
                    $accounts = $accountsResponse->json('data') ?? [];
                }

                $orgName = $meResponse->json('data.organization.name') ?? 'Unknown Org';
                $totalAccounts = is_array($accounts) ? count($accounts) : 0;
                $configuredPhoneId = $this->setting->kirim_phone_number_id ?: '-';

                return [
                    'success' => true,
                    'message' => "✅ Koneksi Kirimdev berhasil!\nOrg: {$orgName}\nAccounts: {$totalAccounts}\nConfigured Phone Number ID: {$configuredPhoneId}",
                    'data' => [
                        'me' => $meResponse->json('data'),
                        'accounts' => $accounts,
                    ],
                ];
            }

            // Test Fonnte connection using /device endpoint
            $response = Http::withHeaders([
                'Authorization' => $this->setting->api_key,
            ])->post('https://api.fonnte.com/device');

            if ($response->successful()) {
                $data = $response->json();

                // Check if status is true
                if (isset($data['status']) && $data['status'] === true) {
                    $deviceStatus = $data['device_status'] ?? 'unknown';
                    $device = $data['device'] ?? 'N/A';
                    $quota = $data['quota'] ?? 'N/A';

                    return [
                        'success' => true,
                        'message' => "✅ Koneksi berhasil!\nDevice: {$device}\nStatus: {$deviceStatus}\nQuota: {$quota} pesan",
                        'data' => $data,
                    ];
                }

                // If status is false
                return [
                    'success' => false,
                    'message' => 'Device tidak terhubung. Silakan scan QR Code di dashboard Fonnte.',
                ];
            }

            // If not successful, return error with details
            $errorData = $response->json();
            return [
                'success' => false,
                'message' => 'Koneksi gagal: ' . ($errorData['reason'] ?? $response->body()),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error koneksi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send leave request notification to admin
     */
    public function sendLeaveRequestNotification($leave)
    {
        if (!$this->setting || !$this->setting->is_enabled || !$this->setting->notify_leave_request) {
            Log::info('Leave request notification is disabled');
            return false;
        }

        // Check if admin phone is configured
        if (!$this->setting->admin_phone) {
            Log::warning('Admin phone number not configured for leave notifications');
            return false;
        }

        // Load employee relation if not loaded
        if (!$leave->relationLoaded('employee')) {
            $leave->load('employee');
        }

        $employee = $leave->employee;

        // Check if employee has phone number
        if (!$employee || !$employee->phone) {
            Log::warning('Employee phone number not found for leave notification', [
                'employee_id' => $leave->employee_id,
            ]);
            return false;
        }

        // Get template
        $template = $this->setting->leave_request_template ?: WhatsAppSetting::getDefaultLeaveRequestTemplate();

        // Replace variables
        $leaveTypeLabel = [
            'cuti' => 'Cuti',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
        ];

        $message = str_replace(
            [
                '{employee_name}',
                '{employee_nip}',
                '{leave_type}',
                '{start_date}',
                '{end_date}',
                '{total_days}',
                '{reason}',
            ],
            [
                $employee->name ?? 'N/A',
                $employee->employee_code ?? 'N/A',
                $leaveTypeLabel[$leave->leave_type] ?? $leave->leave_type,
                $leave->start_date->format('d/m/Y'),
                $leave->end_date->format('d/m/Y'),
                $leave->total_days,
                $leave->reason,
            ],
            $template
        );

        // Send to admin
        $result = $this->send($this->setting->admin_phone, $message);

        if ($result) {
            Log::info('Leave request notification sent to admin', [
                'leave_id' => $leave->id,
                'employee_name' => $employee->name,
            ]);
        } else {
            Log::warning('Failed to send leave request notification to admin', [
                'leave_id' => $leave->id,
            ]);
        }

        return $result;
    }

    /**
     * Send leave approved notification to employee
     */
    public function sendLeaveApprovedNotification($leave)
    {
        if (!$this->setting || !$this->setting->is_enabled || !$this->setting->notify_leave_approved) {
            Log::info('Leave approved notification is disabled');
            return false;
        }

        // Load relations if not loaded
        if (!$leave->relationLoaded('employee')) {
            $leave->load('employee');
        }
        if (!$leave->relationLoaded('approver')) {
            $leave->load('approver');
        }

        $employee = $leave->employee;

        // Check if employee has phone number
        if (!$employee || !$employee->phone) {
            Log::warning('Employee phone number not found for leave approved notification', [
                'employee_id' => $leave->employee_id,
            ]);
            return false;
        }

        // Get template
        $template = $this->setting->leave_approved_template ?: WhatsAppSetting::getDefaultLeaveApprovedTemplate();

        // Replace variables
        $leaveTypeLabel = [
            'cuti' => 'Cuti',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
        ];

        $message = str_replace(
            [
                '{employee_name}',
                '{leave_type}',
                '{start_date}',
                '{end_date}',
                '{total_days}',
                '{approved_by}',
                '{approved_at}',
            ],
            [
                $employee->name ?? 'N/A',
                $leaveTypeLabel[$leave->leave_type] ?? $leave->leave_type,
                $leave->start_date->format('d/m/Y'),
                $leave->end_date->format('d/m/Y'),
                $leave->total_days,
                $leave->approver->name ?? 'Admin',
                $leave->approved_at ? $leave->approved_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i'),
            ],
            $template
        );

        // Send to employee
        $result = $this->send($employee->phone, $message);

        if ($result) {
            Log::info('Leave approved notification sent to employee', [
                'leave_id' => $leave->id,
                'employee_name' => $employee->name,
            ]);
        } else {
            Log::warning('Failed to send leave approved notification', [
                'leave_id' => $leave->id,
            ]);
        }

        return $result;
    }

    /**
     * Send leave rejected notification to employee
     */
    public function sendLeaveRejectedNotification($leave)
    {
        if (!$this->setting || !$this->setting->is_enabled || !$this->setting->notify_leave_rejected) {
            Log::info('Leave rejected notification is disabled');
            return false;
        }

        // Load relations if not loaded
        if (!$leave->relationLoaded('employee')) {
            $leave->load('employee');
        }
        if (!$leave->relationLoaded('approver')) {
            $leave->load('approver');
        }

        $employee = $leave->employee;

        // Check if employee has phone number
        if (!$employee || !$employee->phone) {
            Log::warning('Employee phone number not found for leave rejected notification', [
                'employee_id' => $leave->employee_id,
            ]);
            return false;
        }

        // Get template
        $template = $this->setting->leave_rejected_template ?: WhatsAppSetting::getDefaultLeaveRejectedTemplate();

        // Replace variables
        $leaveTypeLabel = [
            'cuti' => 'Cuti',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
        ];

        $message = str_replace(
            [
                '{employee_name}',
                '{leave_type}',
                '{start_date}',
                '{end_date}',
                '{total_days}',
                '{rejection_reason}',
                '{approved_by}',
            ],
            [
                $employee->name ?? 'N/A',
                $leaveTypeLabel[$leave->leave_type] ?? $leave->leave_type,
                $leave->start_date->format('d/m/Y'),
                $leave->end_date->format('d/m/Y'),
                $leave->total_days,
                $leave->rejection_reason ?? 'Tidak ada alasan',
                $leave->approver->name ?? 'Admin',
            ],
            $template
        );

        // Send to employee
        $result = $this->send($employee->phone, $message);

        if ($result) {
            Log::info('Leave rejected notification sent to employee', [
                'leave_id' => $leave->id,
                'employee_name' => $employee->name,
            ]);
        } else {
            Log::warning('Failed to send leave rejected notification', [
                'leave_id' => $leave->id,
            ]);
        }

        return $result;
    }

    /**
     * Send payroll notification to employee
     */
    public function sendPayrollNotification($payroll)
    {
        if (!$this->setting || !$this->setting->is_enabled) {
            Log::info('WhatsApp notification is disabled');
            return false;
        }

        // Load employee relation if not loaded
        if (!$payroll->relationLoaded('employee')) {
            $payroll->load('employee');
        }

        $employee = $payroll->employee;

        // Check if employee has phone number
        if (!$employee || !$employee->phone) {
            Log::warning('Employee phone number not found for payroll notification', [
                'employee_id' => $payroll->employee_id,
            ]);
            return false;
        }

        // Get template from settings or use default
        $template = $this->setting->payroll_template ?? $this->getDefaultPayrollTemplate();

        // Format currency
        $formatCurrency = function ($amount) {
            return 'Rp ' . number_format($amount, 0, ',', '.');
        };

        // Calculate total allowances
        $totalAllowances = $payroll->allowance_transport
            + $payroll->allowance_meal
            + $payroll->allowance_position
            + $payroll->allowance_others;

        // Calculate other deductions
        $otherDeductions = $payroll->deduction_loan + $payroll->deduction_others;

        // Replace variables
        $message = str_replace(
            [
                '{employee_name}',
                '{period}',
                '{formatted_period}',
                '{basic_salary}',
                '{total_allowances}',
                '{overtime}',
                '{bonus}',
                '{total_earnings}',
                '{deduction_late}',
                '{deduction_absent}',
                '{deduction_bpjs}',
                '{deduction_tax}',
                '{other_deductions}',
                '{total_deductions}',
                '{net_salary}',
                '{payment_date}',
            ],
            [
                $employee->name,
                $payroll->period_month,
                $payroll->formatted_period,
                $formatCurrency($payroll->basic_salary),
                $formatCurrency($totalAllowances),
                $formatCurrency($payroll->overtime_pay),
                $formatCurrency($payroll->bonus),
                $formatCurrency($payroll->total_earnings),
                $formatCurrency($payroll->deduction_late),
                $formatCurrency($payroll->deduction_absent),
                $formatCurrency($payroll->deduction_bpjs),
                $formatCurrency($payroll->deduction_tax),
                $formatCurrency($otherDeductions),
                $formatCurrency($payroll->total_deductions),
                $formatCurrency($payroll->net_salary),
                date('d/m/Y', strtotime($payroll->payment_date)),
            ],
            $template
        );

        // Send notification
        $result = $this->send($employee->phone, $message);

        if ($result) {
            Log::info('Payroll notification sent to employee', [
                'payroll_id' => $payroll->id,
                'employee_name' => $employee->name,
                'net_salary' => $payroll->net_salary,
            ]);
        } else {
            Log::warning('Failed to send payroll notification', [
                'payroll_id' => $payroll->id,
            ]);
        }

        return $result;
    }

    /**
     * Get default payroll template
     */
    protected function getDefaultPayrollTemplate()
    {
        return "🧾 *SLIP GAJI - {period}*\n\n" .
            "Kepada Yth.\n" .
            "*{employee_name}*\n\n" .
            "Berikut rincian gaji untuk periode *{formatted_period}*:\n\n" .
            "💰 *PENDAPATAN*\n" .
            "• Gaji Pokok: {basic_salary}\n" .
            "• Tunjangan: {total_allowances}\n" .
            "• Lembur: {overtime}\n" .
            "• Bonus: {bonus}\n" .
            "─────────────\n" .
            "Total: {total_earnings}\n\n" .
            "➖ *POTONGAN*\n" .
            "• Keterlambatan: {deduction_late}\n" .
            "• Ketidakhadiran: {deduction_absent}\n" .
            "• BPJS: {deduction_bpjs}\n" .
            "• Pajak: {deduction_tax}\n" .
            "• Lainnya: {other_deductions}\n" .
            "─────────────\n" .
            "Total: {total_deductions}\n\n" .
            "✅ *GAJI BERSIH*\n" .
            "*{net_salary}*\n\n" .
            "📅 Tanggal Pembayaran: {payment_date}\n\n" .
            "Terima kasih atas dedikasi Anda! 🙏";
    }

    /**
     * Send warning letter notification to employee
     */
    public function sendWarningLetterNotification($warningLetter)
    {
        Log::info('sendWarningLetterNotification called', [
            'sp_id' => $warningLetter->id,
            'has_setting' => !is_null($this->setting),
            'is_enabled' => $this->setting ? $this->setting->is_enabled : false,
            'notify_warning_letter' => $this->setting ? ($this->setting->notify_warning_letter ?? 'not set') : 'no setting',
        ]);

        if (!$this->setting || !$this->setting->is_enabled) {
            Log::warning('WhatsApp service is disabled', [
                'has_setting' => !is_null($this->setting),
                'is_enabled' => $this->setting ? $this->setting->is_enabled : false,
            ]);
            return false;
        }

        // Check notify_warning_letter setting (optional, defaults to true if not set)
        if (isset($this->setting->notify_warning_letter) && !$this->setting->notify_warning_letter) {
            Log::info('Warning letter notification is disabled in settings');
            return false;
        }

        // Load relations
        if (!$warningLetter->relationLoaded('employee')) {
            $warningLetter->load('employee');
        }
        if (!$warningLetter->relationLoaded('issuer')) {
            $warningLetter->load('issuer');
        }

        $employee = $warningLetter->employee;

        Log::info('Employee data', [
            'employee_id' => $warningLetter->employee_id,
            'has_employee' => !is_null($employee),
            'employee_name' => $employee->name ?? 'N/A',
            'employee_phone' => $employee->phone ?? 'N/A',
        ]);

        if (!$employee || !$employee->phone) {
            Log::warning('Employee phone number not found for warning letter notification', [
                'employee_id' => $warningLetter->employee_id,
                'has_employee' => !is_null($employee),
                'phone' => $employee->phone ?? 'null',
            ]);
            return false;
        }

        // Get template
        $template = $this->setting->warning_letter_template ?: $this->getDefaultWarningLetterTemplate();

        // SP type labels
        $spTypeLabel = [
            'ST' => 'ST (Surat Teguran)',
            'SP1' => 'SP 1 (Peringatan Pertama)',
            'SP2' => 'SP 2 (Peringatan Kedua)',
            'SP3' => 'SP 3 (Peringatan Terakhir)',
        ];

        // Replace variables
        $message = str_replace(
            [
                '{employee_name}',
                '{sp_type}',
                '{sp_number}',
                '{violation}',
                '{issue_date}',
                '{effective_date}',
                '{issued_by}',
            ],
            [
                $employee->name ?? 'N/A',
                $spTypeLabel[$warningLetter->sp_type] ?? $warningLetter->sp_type,
                $warningLetter->sp_number,
                $warningLetter->violation,
                $warningLetter->issue_date->format('d/m/Y'),
                $warningLetter->effective_date->format('d/m/Y'),
                $warningLetter->issuer->name ?? 'HRD',
            ],
            $template
        );

        // Send notification with document link (if available)
        $documentUrl = null;
        if ($warningLetter->document_path) {
            $documentUrl = asset('storage/' . $warningLetter->document_path);
        }

        Log::info('Preparing to send WA', [
            'phone' => $employee->phone,
            'has_document' => !is_null($documentUrl),
            'message_length' => strlen($message),
            'sender' => $this->getSenderFor('warning_letter'),
            'api_key' => $this->getApiKeyFor('warning_letter') ? 'custom' : 'default',
        ]);

        // Get custom sender and API key for warning letter notifications
        $sender = $this->getSenderFor('warning_letter');
        $apiKey = $this->getApiKeyFor('warning_letter');

        $result = $this->send($employee->phone, $message, $documentUrl, $sender, $apiKey);

        Log::info('WA send result', [
            'result' => $result ? 'success' : 'failed',
            'sp_id' => $warningLetter->id,
            'sender_used' => $sender,
            'api_key_used' => $apiKey ? 'custom' : 'default',
        ]);

        if ($result) {
            // Update wa_sent_at and wa_message
            $warningLetter->update([
                'wa_sent_at' => now(),
                'wa_message' => $message,
            ]);

            Log::info('Warning letter notification sent to employee', [
                'sp_id' => $warningLetter->id,
                'employee_name' => $employee->name,
                'sp_type' => $warningLetter->sp_type,
            ]);
        } else {
            Log::warning('Failed to send warning letter notification', [
                'sp_id' => $warningLetter->id,
                'employee_id' => $warningLetter->employee_id,
            ]);
        }

        return $result;
    }

    /**
     * Send alpha notification to employee
     */
    public function sendAlphaNotification($attendance, $totalAlphaThisMonth = null, $delay = '2')
    {
        if (!$this->setting || !$this->setting->is_enabled) {
            Log::info('WhatsApp notification is disabled');
            return false;
        }

        // Check notify_alpha setting (defaults to true if not set)
        if (isset($this->setting->notify_alpha) && !$this->setting->notify_alpha) {
            Log::info('Alpha notification is disabled in settings');
            return false;
        }

        // Load employee relation if not loaded
        if (!$attendance->relationLoaded('employee')) {
            $attendance->load('employee.department');
        }

        $employee = $attendance->employee;

        if (!$employee || !$employee->phone) {
            Log::warning('Employee phone number not found for alpha notification', [
                'employee_id' => $attendance->employee_id,
            ]);
            return false;
        }

        // Calculate total alpha this month if not provided
        if ($totalAlphaThisMonth === null) {
            $attendanceDate = \Carbon\Carbon::parse($attendance->attendance_date);
            $totalAlphaThisMonth = \App\Models\Attendance::where('employee_id', $employee->id)
                ->where('status', 'alpha')
                ->whereYear('attendance_date', $attendanceDate->year)
                ->whereMonth('attendance_date', $attendanceDate->month)
                ->count();
        }

        // Get template
        $template = $this->setting->alpha_template ?: \App\Models\WhatsAppSetting::getDefaultAlphaTemplate();

        // Format date
        $formattedDate = \Carbon\Carbon::parse($attendance->attendance_date)
            ->locale('id')
            ->translatedFormat('l, d F Y');

        // Replace variables
        $message = str_replace(
            [
                '{employee_name}',
                '{employee_code}',
                '{department}',
                '{date}',
                '{total_alpha}',
            ],
            [
                $employee->name ?? 'N/A',
                $employee->employee_code ?? 'N/A',
                $employee->department->name ?? 'N/A',
                $formattedDate,
                $totalAlphaThisMonth,
            ],
            $template
        );

        // Get custom sender and API key for alpha notifications
        $sender = $this->getSenderFor('alpha');
        $apiKey = $this->getApiKeyFor('alpha');

        $result = $this->send($employee->phone, $message, null, $sender, $apiKey, $delay);

        if ($result) {
            Log::info('Alpha notification sent to employee', [
                'attendance_id' => $attendance->id,
                'employee_name' => $employee->name,
                'date' => $attendance->attendance_date,
                'total_alpha' => $totalAlphaThisMonth,
            ]);
        } else {
            Log::warning('Failed to send alpha notification', [
                'attendance_id' => $attendance->id,
                'employee_id' => $attendance->employee_id,
            ]);
        }

        return $result;
    }

    /**
     * Send bulk alpha notifications for a date range
     * Returns array with success/failed counts
     */
    public function sendBulkAlphaNotifications($dateFrom, $dateTo, $departmentId = null)
    {
        $query = \App\Models\Attendance::with(['employee.department'])
            ->where('status', 'alpha')
            ->whereBetween('attendance_date', [$dateFrom, $dateTo]);

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $alphaAttendances = $query->get();

        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $details = [];

        foreach ($alphaAttendances as $attendance) {
            $employee = $attendance->employee;

            if (!$employee || !$employee->phone) {
                $skipped++;
                $details[] = [
                    'employee' => $employee->name ?? 'Unknown',
                    'status' => 'skipped',
                    'reason' => 'No phone number',
                ];
                continue;
            }

            // Calculate progressively increasing delay (e.g. 60 seconds apart)
            // First message: 2s, second: 62s, third: 122s
            $currentDelay = 2 + ($sent * 60);

            $result = $this->sendAlphaNotification($attendance, null, $currentDelay);

            if ($result) {
                $sent++;
                $details[] = [
                    'employee' => $employee->name,
                    'date' => $attendance->attendance_date,
                    'status' => 'sent',
                ];
            } else {
                $failed++;
                $details[] = [
                    'employee' => $employee->name,
                    'date' => $attendance->attendance_date,
                    'status' => 'failed',
                ];
            }

            // Small delay locally just to avoid hitting Fonnte API too fast
            usleep(200000); // 0.2 second
        }

        return [
            'total' => $alphaAttendances->count(),
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'details' => $details,
        ];
    }

    /**
     * Get default warning letter template
     */
    protected function getDefaultWarningLetterTemplate(): string
    {
        return "⚠️ *SURAT PERINGATAN*\n\n" .
            "Kepada Yth.\n" .
            "*{employee_name}*\n\n" .
            "Dengan ini kami sampaikan *{sp_type}* dengan detail sebagai berikut:\n\n" .
            "📄 Nomor: *{sp_number}*\n" .
            "📅 Tanggal Terbit: {issue_date}\n" .
            "📅 Berlaku: {effective_date}\n\n" .
            "⚠️ *Pelanggaran:*\n" .
            "{violation}\n\n" .
            "Dokumen SP akan dikirimkan melalui pesan ini.\n" .
            "Mohon untuk membaca dan memahami isi surat peringatan dengan seksama.\n\n" .
            "Diterbitkan oleh: {issued_by}\n" .
            "HRD PT Mingda Indonesia Furniture";
    }

    /**
     * Send welcome notification to newly registered employee
     */
    public function sendWelcomeNotification($employee, $password = 'password123')
    {
        if (!$this->setting || !$this->setting->is_enabled) {
            Log::info('WhatsApp notification is disabled. Welcome message not sent.');
            return false;
        }

        if (isset($this->setting->notify_welcome) && !$this->setting->notify_welcome) {
            Log::info('Welcome notification is disabled in settings.');
            return false;
        }

        if (!$employee || !$employee->phone) {
            Log::warning('Employee phone number not found for welcome notification', [
                'employee_id' => $employee->id ?? 'unknown',
            ]);
            return false;
        }

        // Get template from setting or fallback to default
        $template = $this->setting->welcome_template ?: WhatsAppSetting::getDefaultWelcomeTemplate();

        $message = str_replace(
            [
                '{employee_name}',
                '{employee_code}',
                '{email}',
                '{password}',
            ],
            [
                $employee->name,
                $employee->employee_code,
                $employee->email,
                $password,
            ],
            $template
        );

        // Get custom sender and API key
        $sender = $this->getSenderFor('welcome');
        $apiKey = $this->getApiKeyFor('welcome');

        // Send to employee
        $result = $this->send($employee->phone, $message, null, $sender, $apiKey);

        if ($result) {
            Log::info('Welcome notification sent to employee', [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
            ]);
        } else {
            Log::warning('Failed to send welcome notification', [
                'employee_id' => $employee->id,
            ]);
        }

        return $result;
    }
}
