<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Cache;
use App\Console\ScheduleRunMiddleware;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Update tracking setiap kali schedule:run dipanggil
// Ini akan dijalankan sebelum semua scheduled tasks
Schedule::call(function () {
    ScheduleRunMiddleware::updateTracking();
})->everyMinute()->name('update-cron-tracking');

// Schedule: Generate absent attendance setiap hari jam 8 pagi
// Mengecek apakah ada karyawan yang belum absen untuk hari kemarin
Schedule::command('attendance:generate-absent')
    ->dailyAt('08:00')
    ->weekdays()
    ->before(function () {
        // Update cache untuk tracking
        Cache::put('cron_last_run', now(), now()->addDays(7));

        // Update sentinel file untuk tracking (better for cPanel)
        $sentinelFile = storage_path('framework/schedule-sentinel');
        touch($sentinelFile);
    })
    ->after(function () {
        // Update timestamp after successful run
        Cache::put('cron_last_run', now(), now()->addDays(7));

        $sentinelFile = storage_path('framework/schedule-sentinel');
        touch($sentinelFile);
    });

// Schedule: Auto sync Fingerspot data every 5 minutes
// Mengambil data attlog dari API Fingerspot dan memproses ke attendance
Schedule::command('fingerspot:sync')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/fingerspot-sync.log'));
