<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Attendance;

$attendance = Attendance::where(function($q) {
    $q->whereNotNull('photo_in')
      ->orWhereNotNull('photo_out');
})
->latest()
->first();

if ($attendance) {
    echo "ID: " . $attendance->id . PHP_EOL;
    echo "Photo In: " . ($attendance->photo_in ?: 'NULL') . PHP_EOL;
    echo "Photo Out: " . ($attendance->photo_out ?: 'NULL') . PHP_EOL;
    echo PHP_EOL;

    // Check if URLs are valid
    if ($attendance->photo_in) {
        $isUrl = filter_var($attendance->photo_in, FILTER_VALIDATE_URL);
        echo "Photo In is URL: " . ($isUrl ? "YES" : "NO") . PHP_EOL;
        if ($isUrl) {
            echo "Protocol: " . parse_url($attendance->photo_in, PHP_URL_SCHEME) . PHP_EOL;
        }
    }

    if ($attendance->photo_out) {
        $isUrl = filter_var($attendance->photo_out, FILTER_VALIDATE_URL);
        echo "Photo Out is URL: " . ($isUrl ? "YES" : "NO") . PHP_EOL;
        if ($isUrl) {
            echo "Protocol: " . parse_url($attendance->photo_out, PHP_URL_SCHEME) . PHP_EOL;
        }
    }
} else {
    echo "No attendance records with photos found" . PHP_EOL;
}
