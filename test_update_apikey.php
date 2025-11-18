<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WhatsAppSetting;

echo "=== TEST UPDATE API KEY ===\n\n";

$setting = WhatsAppSetting::first();

echo "Current API Key: " . ($setting->api_key ?? 'NULL') . "\n\n";

// Test 1: Update with new value
echo "Test 1: Update to new API key...\n";
$setting->api_key = 'TEST_API_KEY_NEW_12345';
$setting->save();
$setting->refresh();
echo "After save: " . $setting->api_key . "\n";
echo $setting->api_key === 'TEST_API_KEY_NEW_12345' ? "✅ SUCCESS\n\n" : "❌ FAILED\n\n";

// Test 2: Update to empty string
echo "Test 2: Update to empty string...\n";
$setting->api_key = '';
$setting->save();
$setting->refresh();
echo "After save: '" . $setting->api_key . "'\n";
echo $setting->api_key === '' ? "✅ SUCCESS (empty)\n\n" : "❌ FAILED\n\n";

// Test 3: Update to null
echo "Test 3: Update to null...\n";
$setting->api_key = null;
$setting->save();
$setting->refresh();
echo "After save: " . ($setting->api_key ?? 'NULL') . "\n";
echo $setting->api_key === null ? "✅ SUCCESS (null)\n\n" : "❌ FAILED\n\n";

// Restore original
echo "Restoring original API key...\n";
$setting->api_key = 'wmS3c1u1MKQLBRpsgSGr';
$setting->save();
echo "✅ Restored to: " . $setting->api_key . "\n";
