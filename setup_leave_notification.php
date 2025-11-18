<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WhatsAppSetting;

echo "=== UPDATE WHATSAPP SETTINGS FOR LEAVE NOTIFICATION ===\n\n";

$setting = WhatsAppSetting::first();

if (!$setting) {
    echo "❌ WhatsApp settings not found!\n";
    exit(1);
}

echo "Current Settings:\n";
echo "- API Key: " . ($setting->api_key ? '***' . substr($setting->api_key, -4) : 'NULL') . "\n";
echo "- Admin Phone: " . ($setting->admin_phone ?? 'NULL') . "\n";
echo "- Notify Leave Request: " . ($setting->notify_leave_request ? 'Yes' : 'No') . "\n";
echo "- Notify Leave Approved: " . ($setting->notify_leave_approved ? 'Yes' : 'No') . "\n";
echo "- Notify Leave Rejected: " . ($setting->notify_leave_rejected ? 'Yes' : 'No') . "\n\n";

// Update settings
echo "Updating settings...\n";

$setting->notify_leave_request = true;
$setting->notify_leave_approved = true;
$setting->notify_leave_rejected = true;

// Set default templates if not exists
if (!$setting->leave_request_template) {
    $setting->leave_request_template = WhatsAppSetting::getDefaultLeaveRequestTemplate();
    echo "✅ Set default leave request template\n";
}

if (!$setting->leave_approved_template) {
    $setting->leave_approved_template = WhatsAppSetting::getDefaultLeaveApprovedTemplate();
    echo "✅ Set default leave approved template\n";
}

if (!$setting->leave_rejected_template) {
    $setting->leave_rejected_template = WhatsAppSetting::getDefaultLeaveRejectedTemplate();
    echo "✅ Set default leave rejected template\n";
}

$setting->save();

echo "\n✅ Settings updated successfully!\n\n";

echo "Updated Settings:\n";
$setting->refresh();
echo "- Notify Leave Request: " . ($setting->notify_leave_request ? 'Yes' : 'No') . "\n";
echo "- Notify Leave Approved: " . ($setting->notify_leave_approved ? 'Yes' : 'No') . "\n";
echo "- Notify Leave Rejected: " . ($setting->notify_leave_rejected ? 'Yes' : 'No') . "\n";
echo "- Has Leave Request Template: " . ($setting->leave_request_template ? 'Yes' : 'No') . "\n";
echo "- Has Leave Approved Template: " . ($setting->leave_approved_template ? 'Yes' : 'No') . "\n";
echo "- Has Leave Rejected Template: " . ($setting->leave_rejected_template ? 'Yes' : 'No') . "\n";

echo "\n📋 NEXT STEPS:\n";
echo "1. Set admin_phone di admin panel untuk menerima notifikasi cuti\n";
echo "2. Pastikan nomor karyawan sudah benar format (628xxx)\n";
echo "3. Test dengan submit pengajuan cuti dari mobile/dashboard\n";
