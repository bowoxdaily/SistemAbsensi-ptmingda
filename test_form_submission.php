<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WhatsAppSetting;

echo "=== SIMULATE FORM SUBMISSION ===\n\n";

$setting = WhatsAppSetting::first();

echo "BEFORE UPDATE:\n";
echo "- API Key: " . ($setting->api_key ?? 'NULL') . "\n";
echo "- Sender: " . ($setting->sender ?? 'NULL') . "\n";
echo "- Enabled: " . ($setting->is_enabled ? 'Yes' : 'No') . "\n\n";

// Simulate CONFIG form submission with NEW API KEY
echo "SIMULATING CONFIG FORM:\n";
echo "- form_type: config\n";
echo "- api_key: NEW_API_KEY_FROM_FORM\n";
echo "- sender: 628123456789\n";
echo "- is_enabled: checked\n\n";

// This is what controller does:
$formType = 'config';
$setting->provider = 'fonnte';
$setting->api_url = null;

if ($formType === 'config') {
    $setting->api_key = 'NEW_API_KEY_FROM_FORM';
    $setting->sender = '628123456789';
    $setting->is_enabled = 1;
    $setting->notify_checkin = 1;
    $setting->notify_checkout = 1;
}

$setting->save();
$setting->refresh();

echo "AFTER SAVE:\n";
echo "- API Key: " . ($setting->api_key ?? 'NULL') . "\n";
echo "- Sender: " . ($setting->sender ?? 'NULL') . "\n";
echo "- Enabled: " . ($setting->is_enabled ? 'Yes' : 'No') . "\n\n";

if ($setting->api_key === 'NEW_API_KEY_FROM_FORM') {
    echo "✅ SUCCESS! API Key updated from form!\n\n";
} else {
    echo "❌ FAILED! API Key not updated!\n\n";
}

// Restore original
echo "Restoring original values...\n";
$setting->api_key = 'wmS3c1u1MKQLBRpsgSGr';
$setting->sender = null;
$setting->save();
echo "✅ Restored\n";
