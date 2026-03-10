<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppSetting;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class WhatsAppSettingController extends Controller
{
    /**
     * Display WhatsApp settings page
     */
    public function index()
    {
        $setting = WhatsAppSetting::first();

        if (!$setting) {
            // Create default settings
            $setting = WhatsAppSetting::create([
                'provider' => 'fonnte',
                'is_enabled' => false,
                'notify_checkin' => true,
                'notify_checkout' => true,
                'checkin_template' => WhatsAppSetting::getDefaultCheckinTemplate(),
                'checkout_template' => WhatsAppSetting::getDefaultCheckoutTemplate(),
            ]);
        }

        return view('admin.whatsapp.index', compact('setting'));
    }

    /**
     * Update WhatsApp settings
     */
    public function update(Request $request)
    {
        // Log incoming request for debugging
        Log::info('WhatsApp Settings Update Request', [
            'form_type' => $request->input('form_type'),
            'api_key' => $request->input('api_key') ? '***' . substr($request->input('api_key'), -4) : 'NULL',
            'sender' => $request->input('sender'),
            'has_is_enabled' => $request->has('is_enabled'),
            'all_keys' => array_keys($request->all())
        ]);

        // Simple validation - no complex rules
        $validator = Validator::make($request->all(), [
            'api_key' => 'nullable|string|max:255',
            'sender' => 'nullable|string|max:50',
            'admin_phone' => 'nullable|string|max:50',
            'checkin_api_key' => 'nullable|string|max:255',
            'checkout_api_key' => 'nullable|string|max:255',
            'leave_api_key' => 'nullable|string|max:255',
            'warning_letter_api_key' => 'nullable|string|max:255',
            'payroll_api_key' => 'nullable|string|max:255',
            'checkin_sender' => 'nullable|string|max:50',
            'checkout_sender' => 'nullable|string|max:50',
            'leave_sender' => 'nullable|string|max:50',
            'warning_letter_sender' => 'nullable|string|max:50',
            'payroll_sender' => 'nullable|string|max:50',
            'sp_number_format' => 'nullable|string|max:100',
            'sp_department_code' => 'nullable|string|max:10',
            'sp_counter_width' => 'nullable|integer|min:1|max:10',
            'checkin_template' => 'nullable|string',
            'checkout_template' => 'nullable|string',
            'leave_request_template' => 'nullable|string',
            'leave_approved_template' => 'nullable|string',
            'leave_rejected_template' => 'nullable|string',
            'warning_letter_template' => 'nullable|string',
            'payroll_template' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::warning('WhatsApp Settings Validation Failed', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $setting = WhatsAppSetting::first();

            if (!$setting) {
                $setting = new WhatsAppSetting();
            }

            // Log before update
            Log::info('Before Update', [
                'api_key' => $setting->api_key ? '***' . substr($setting->api_key, -4) : 'NULL'
            ]);

            // Always set provider to fonnte
            $setting->provider = 'fonnte';
            $setting->api_url = null;

            // Detect which form is being submitted
            $formType = $request->input('form_type', 'config');

            if ($formType === 'config') {
                // CONFIG FORM: Update API Key, Sender, Admin Phone, and Toggles
                $setting->api_key = $request->input('api_key');
                $setting->sender = $request->input('sender');
                $setting->admin_phone = $request->input('admin_phone');

                // Custom API keys
                $setting->checkin_api_key = $request->input('checkin_api_key');
                $setting->checkout_api_key = $request->input('checkout_api_key');
                $setting->leave_api_key = $request->input('leave_api_key');
                $setting->warning_letter_api_key = $request->input('warning_letter_api_key');
                $setting->payroll_api_key = $request->input('payroll_api_key');

                // Custom senders
                $setting->checkin_sender = $request->input('checkin_sender');
                $setting->checkout_sender = $request->input('checkout_sender');
                $setting->leave_sender = $request->input('leave_sender');
                $setting->warning_letter_sender = $request->input('warning_letter_sender');
                $setting->payroll_sender = $request->input('payroll_sender');

                $setting->is_enabled = $request->has('is_enabled') ? 1 : 0;
                $setting->notify_checkin = $request->has('notify_checkin') ? 1 : 0;
                $setting->notify_checkout = $request->has('notify_checkout') ? 1 : 0;
                $setting->send_checkin_photo = $request->has('send_checkin_photo') ? 1 : 0;
                $setting->send_checkout_photo = $request->has('send_checkout_photo') ? 1 : 0;
                $setting->notify_leave_request = $request->has('notify_leave_request') ? 1 : 0;
                $setting->notify_leave_approved = $request->has('notify_leave_approved') ? 1 : 0;
                $setting->notify_leave_rejected = $request->has('notify_leave_rejected') ? 1 : 0;
                $setting->notify_warning_letter = $request->has('notify_warning_letter') ? 1 : 0;
                $setting->notify_payroll = $request->has('notify_payroll') ? 1 : 0;

                // SP Number Format Settings
                $setting->sp_number_format = $request->input('sp_number_format') ?: '{sp_type}/{dept}/{counter}/{year}';
                $setting->sp_department_code = $request->input('sp_department_code') ?: 'HR';
                $setting->sp_counter_width = $request->input('sp_counter_width') ?: 3;

                Log::info('Config Form Processing', [
                    'new_api_key' => $setting->api_key ? '***' . substr($setting->api_key, -4) : 'NULL',
                    'new_sender' => $setting->sender,
                    'new_admin_phone' => $setting->admin_phone
                ]);

                // Don't update templates from config form
            } elseif ($formType === 'template') {
                // TEMPLATE FORM: Only update templates, preserve toggles from hidden inputs
                $setting->is_enabled = $request->input('is_enabled', 0) ? 1 : 0;
                $setting->notify_checkin = $request->input('notify_checkin', 0) ? 1 : 0;
                $setting->notify_checkout = $request->input('notify_checkout', 0) ? 1 : 0;
                $setting->send_checkin_photo = $request->input('send_checkin_photo', 0) ? 1 : 0;
                $setting->send_checkout_photo = $request->input('send_checkout_photo', 0) ? 1 : 0;
                $setting->notify_leave_request = $request->input('notify_leave_request', 0) ? 1 : 0;
                $setting->notify_leave_approved = $request->input('notify_leave_approved', 0) ? 1 : 0;
                $setting->notify_leave_rejected = $request->input('notify_leave_rejected', 0) ? 1 : 0;
                $setting->notify_warning_letter = $request->input('notify_warning_letter', 0) ? 1 : 0;
                $setting->notify_payroll = $request->input('notify_payroll', 0) ? 1 : 0;

                Log::info('Template Form Processing - Not updating API Key');

                // Update attendance templates
                if ($request->has('checkin_template')) {
                    $setting->checkin_template = $request->checkin_template ?: WhatsAppSetting::getDefaultCheckinTemplate();
                }
                if ($request->has('checkout_template')) {
                    $setting->checkout_template = $request->checkout_template ?: WhatsAppSetting::getDefaultCheckoutTemplate();
                }

                // Update leave templates
                if ($request->has('leave_request_template')) {
                    $setting->leave_request_template = $request->leave_request_template ?: WhatsAppSetting::getDefaultLeaveRequestTemplate();
                }
                if ($request->has('leave_approved_template')) {
                    $setting->leave_approved_template = $request->leave_approved_template ?: WhatsAppSetting::getDefaultLeaveApprovedTemplate();
                }
                if ($request->has('leave_rejected_template')) {
                    $setting->leave_rejected_template = $request->leave_rejected_template ?: WhatsAppSetting::getDefaultLeaveRejectedTemplate();
                }

                // Update warning letter template
                if ($request->has('warning_letter_template')) {
                    $setting->warning_letter_template = $request->warning_letter_template ?: WhatsAppSetting::getDefaultWarningLetterTemplate();
                }

                // Update payroll template
                if ($request->has('payroll_template')) {
                    $setting->payroll_template = $request->payroll_template ?: WhatsAppSetting::getDefaultPayrollTemplate();
                }
            }

            $setting->save();

            // Log after save
            $setting->refresh();
            Log::info('After Save', [
                'api_key' => $setting->api_key ? '***' . substr($setting->api_key, -4) : 'NULL'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengaturan WhatsApp berhasil disimpan',
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp Settings Update Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan pengaturan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test WhatsApp connection
     */
    public function testConnection()
    {
        try {
            $whatsappService = new WhatsAppService();
            $result = $whatsappService->testConnection();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data'] ?? null,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send test message
     */
    public function sendTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $whatsappService = new WhatsAppService();
            $result = $whatsappService->send(
                $request->phone,
                $request->message
            );

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pesan test berhasil dikirim ke ' . $request->phone,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim pesan. Cek log untuk detail.',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset templates to default
     */
    public function resetTemplates()
    {
        try {
            $setting = WhatsAppSetting::first();

            if ($setting) {
                $setting->update([
                    'checkin_template' => WhatsAppSetting::getDefaultCheckinTemplate(),
                    'checkout_template' => WhatsAppSetting::getDefaultCheckoutTemplate(),
                    'leave_request_template' => WhatsAppSetting::getDefaultLeaveRequestTemplate(),
                    'leave_approved_template' => WhatsAppSetting::getDefaultLeaveApprovedTemplate(),
                    'leave_rejected_template' => WhatsAppSetting::getDefaultLeaveRejectedTemplate(),
                    'warning_letter_template' => WhatsAppSetting::getDefaultWarningLetterTemplate(),
                    'payroll_template' => WhatsAppSetting::getDefaultPayrollTemplate(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Template berhasil direset ke default',
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal reset template: ' . $e->getMessage()
            ], 500);
        }
    }
}
