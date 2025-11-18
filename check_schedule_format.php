<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking work_schedules time format:\n";
echo "=====================================\n\n";

$schedule = DB::table('work_schedules')->first();

if ($schedule) {
    echo "start_time value: {$schedule->start_time}\n";
    echo "end_time value: {$schedule->end_time}\n";
    echo "\nstart_time type: " . gettype($schedule->start_time) . "\n";
    echo "end_time type: " . gettype($schedule->end_time) . "\n";
} else {
    echo "No schedule found in database\n";
}
