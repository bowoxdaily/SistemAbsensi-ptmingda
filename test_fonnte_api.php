<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test Fonnte API Connection ===\n\n";

// Get WhatsApp setting
$setting = App\Models\WhatsAppSetting::first();

if (!$setting) {
    echo "❌ WhatsApp settings not found\n";
    exit(1);
}

echo "Provider: " . $setting->provider . "\n";
echo "API Key: " . ($setting->api_key ? substr($setting->api_key, 0, 10) . '...' : 'NULL') . "\n";
echo "Enabled: " . ($setting->is_enabled ? 'Yes' : 'No') . "\n\n";

if (!$setting->api_key) {
    echo "❌ API Key is empty!\n";
    exit(1);
}

echo "Testing Fonnte API...\n";

try {
    // Try /device endpoint for checking connection
    $response = Illuminate\Support\Facades\Http::withHeaders([
        'Authorization' => $setting->api_key,
    ])->post('https://api.fonnte.com/device');

    echo "Status Code: " . $response->status() . "\n";
    echo "Response Body:\n";
    echo $response->body() . "\n\n";

    if ($response->successful()) {
        $data = $response->json();
        echo "✅ Connection Successful!\n";
        echo "Device: " . ($data['device'] ?? 'N/A') . "\n";
        echo "Status: " . ($data['status'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Connection Failed\n";
        $error = $response->json();
        echo "Reason: " . ($error['reason'] ?? 'Unknown') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
