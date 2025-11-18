<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WhatsAppSetting;

echo "=== UPDATE TEMPLATE: NIP → Kode Karyawan ===\n\n";

$setting = WhatsAppSetting::first();

if (!$setting) {
    echo "❌ Settings not found!\n";
    exit(1);
}

echo "Updating leave_request_template...\n";
$setting->leave_request_template = WhatsAppSetting::getDefaultLeaveRequestTemplate();
$setting->save();

echo "✅ Template updated!\n\n";
echo "New template:\n";
echo $setting->leave_request_template . "\n";
