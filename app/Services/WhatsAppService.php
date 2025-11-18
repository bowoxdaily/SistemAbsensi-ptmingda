<?php

namespace App\Services;

use App\Models\WhatsAppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $setting;

    public function __construct()
    {
        $this->setting = WhatsAppSetting::getActive();
    }

    /**
     * Send WhatsApp message
     */
    public function send($phoneNumber, $message, $image = null)
    {
        if (!$this->setting || !$this->setting->is_enabled) {
            Log::info('WhatsApp notification disabled or not configured');
            return false;
        }

        try {
            if ($this->setting->isFonnte()) {
                return $this->sendViaFonnte($phoneNumber, $message, $image);
            } elseif ($this->setting->isBaileys()) {
                return $this->sendViaBaileys($phoneNumber, $message, $image);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp Send Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via Fonnte API
     */
    protected function sendViaFonnte($phoneNumber, $message, $image = null)
    {
        $url = 'https://api.fonnte.com/send';

        $data = [
            'target' => $this->formatPhoneNumber($phoneNumber),
            'message' => $message,
            'countryCode' => '62', // Indonesia
        ];

        if ($image) {
            $data['url'] = $image;
        }

        $response = Http::withHeaders([
            'Authorization' => $this->setting->api_key,
        ])->post($url, $data);

        if ($response->successful()) {
            Log::info('WhatsApp sent via Fonnte', [
                'phone' => $phoneNumber,
                'response' => $response->json(),
            ]);
            return true;
        }

        Log::error('Fonnte API Error', [
            'phone' => $phoneNumber,
            'response' => $response->body(),
        ]);
        return false;
    }

    /**
     * Send via Baileys (self-hosted)
     */
    protected function sendViaBaileys($phoneNumber, $message, $image = null)
    {
        $url = rtrim($this->setting->api_url, '/') . '/send-message';

        $data = [
            'phone' => $this->formatPhoneNumber($phoneNumber),
            'message' => $message,
        ];

        if ($image) {
            $data['image'] = $image;
        }

        $response = Http::timeout(10)->post($url, $data);

        if ($response->successful()) {
            Log::info('WhatsApp sent via Baileys', [
                'phone' => $phoneNumber,
                'response' => $response->json(),
            ]);
            return true;
        }

        Log::error('Baileys API Error', [
            'phone' => $phoneNumber,
            'response' => $response->body(),
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
}
