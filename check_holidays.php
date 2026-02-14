<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Holiday;
use Carbon\Carbon;

echo "Checking active holidays...\n\n";

$holidays = Holiday::where('is_active', true)
    ->orderBy('date')
    ->get();

if ($holidays->isEmpty()) {
    echo "❌ No active holidays found in database!\n";
    echo "\nThis is why the system generates 'alpha' instead of 'libur' or 'cuti_bersama'.\n";
    echo "You need to add holidays first via Settings → Cron Job → Tambah Hari Libur\n";
} else {
    echo "✓ Found {$holidays->count()} active holidays:\n\n";
    foreach ($holidays as $holiday) {
        echo "- {$holiday->date->format('d M Y')} ({$holiday->date->format('l')}): {$holiday->name}\n";
        echo "  Type: {$holiday->type} → Status: " . match($holiday->type) {
            'cuti_bersama' => 'cuti_bersama',
            'nasional' => 'libur',
            'custom' => 'libur',
            default => 'libur'
        } . "\n\n";
    }
}

echo "\nToday: " . Carbon::now()->format('d M Y (l)') . "\n";
echo "Yesterday: " . Carbon::yesterday()->format('d M Y (l)') . "\n";
