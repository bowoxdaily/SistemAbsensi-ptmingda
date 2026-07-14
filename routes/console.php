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

// Schedule: Generate absent attendance - sweep pagi (07:00-10:00, setiap 30 menit)
// Menangkap karyawan yang tidak absen setelah grace period jam masuk + 10 menit
// Cron: setiap 30 menit antara jam 07-10 pada hari kerja (Senin-Jumat)
Schedule::command('attendance:generate-absent')
    ->cron('*/30 7-10 * * 1-5')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/generate-absent.log'));

// Schedule: Generate absent attendance - sweep akhir hari (17:30)
// Final sweep untuk karyawan yang belum absen sepanjang hari
Schedule::command('attendance:generate-absent')
    ->weekdays()
    ->dailyAt('17:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/generate-absent.log'));

// Schedule: Auto sync Fingerspot data every 5 minutes - hanya saat jam kerja (06:00-22:00)
// Mengambil data attlog dari API Fingerspot dan memproses ke attendance
// Cron: setiap 5 menit antara jam 06-21 (tidak jalan tengah malam)
Schedule::command('fingerspot:sync')
    ->cron('*/5 6-21 * * *')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/fingerspot-sync.log'));

// Schedule: Recalculate overtime setiap hari kerja jam 23:30 (setelah semua absen masuk)
// Menghitung ulang lembur untuk data attendance hari ini
Schedule::command('attendance:recalculate-overtime', ['--from' => now()->format('Y-m-d')])
    ->dailyAt('23:30') // Jalan setelah semua karyawan selesai absen
    ->weekdays() // Hanya hari kerja (Senin-Jumat)
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/overtime-recalculate.log'));
