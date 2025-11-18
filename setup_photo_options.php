<?php

/**
 * Script untuk setup opsi pengiriman foto
 * Mengaktifkan kedua opsi foto secara default
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== SETUP PHOTO OPTIONS ===\n\n";

// Get setting
$setting = DB::table('whatsapp_settings')->first();

if (!$setting) {
    echo "❌ WhatsApp settings tidak ditemukan!\n";
    exit(1);
}

echo "Current Status:\n";
echo "- Send Check-in Photo: " . ($setting->send_checkin_photo ?? 'NULL') . "\n";
echo "- Send Check-out Photo: " . ($setting->send_checkout_photo ?? 'NULL') . "\n\n";

// Update both to enabled (default)
DB::table('whatsapp_settings')
    ->where('id', $setting->id)
    ->update([
        'send_checkin_photo' => 1,
        'send_checkout_photo' => 1,
        'updated_at' => now()
    ]);

echo "✅ Photo options enabled!\n\n";

// Verify
$updated = DB::table('whatsapp_settings')->first();
echo "New Status:\n";
echo "- Send Check-in Photo: " . ($updated->send_checkin_photo ? '✅ Enabled' : '❌ Disabled') . "\n";
echo "- Send Check-out Photo: " . ($updated->send_checkout_photo ? '✅ Enabled' : '❌ Disabled') . "\n";

echo "\n=== SETUP COMPLETE ===\n";
echo "\n📋 Admin sekarang bisa mengatur pengiriman foto lewat panel admin:\n";
echo "   Menu: Admin > Pengaturan WhatsApp\n";
echo "   Bagian: Notifikasi Absensi\n";
echo "   Toggle: 📷 Kirim foto check-in / check-out\n\n";
