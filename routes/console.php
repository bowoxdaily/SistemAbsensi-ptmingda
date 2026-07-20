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

// Schedule: Generate absent attendance - sweep pagi (07:00-10:00, setiap 10 menit)
// Menangkap karyawan yang tidak absen setelah grace period jam masuk + 10 menit
// Cron: setiap 10 menit antara jam 07-10 pada hari kerja (Senin-Jumat)
Schedule::command('attendance:generate-absent')
    ->cron('*/10 7-10 * * 1-5')
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

// Schedule: Auto sync Fingerspot data
// JADWAL DIOPTIMASI: Tidak jalan saat jam puncak absen masuk (07:00-08:30)
// dan jam puncak absen pulang (16:30-18:00) — menghindari kompetisi disk I/O
// Jalan: 06:00-06:59 (sebelum rush), 09:00-16:29 (siang), 18:01-21:00 (malam)
Schedule::command('fingerspot:sync')
    ->cron('*/5 6,9-16,18-21 * * *')  // skip jam 7,8,17 (peak attendance hours)
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

// Schedule: Queue worker — proses jobs (email broadcast, notifikasi, dll)
// --sleep=3 : tunggu 3 detik sebelum poll ulang jika queue kosong (kurangi DB polling)
// --max-time=50 : berhenti setelah 50 detik (memberi jeda antar run)
// --stop-when-empty : berhenti segera jika queue sudah kosong
Schedule::command('queue:work', ['--stop-when-empty', '--max-time=50', '--sleep=3'])
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/queue-worker.log'));

// Schedule: Dispatch warmup emails every hour (respects daily limits)
// Sends emails based on warmup schedule configuration
Schedule::command('email:dispatch-warmup')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/warmup-emails.log'));

// Schedule: Bersihkan session file expired setiap tengah malam
// Mencegah folder storage/framework/sessions membengkak (SESSION_DRIVER=file)
Schedule::command('session:cleanup')
    ->dailyAt('00:30')
    ->appendOutputTo(storage_path('logs/session-cleanup.log'));

// Schedule: Bersihkan cache file expired setiap hari jam 01:00
// Mencegah storage/framework/cache membengkak
Schedule::command('cache:prune-stale-tags')
    ->dailyAt('01:00')
    ->appendOutputTo(storage_path('logs/cache-cleanup.log'));
