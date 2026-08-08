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
// JADWAL DIOPTIMASI: Tidak jalan saat jam puncak absen masuk (07:00-08:59)
// dan jam puncak absen pulang (16:00-18:59) — menghindari kompetisi disk I/O & table lock
// Jalan: 06:00-06:59 (sebelum rush), 09:00-15:59 (siang), 19:00-21:00 (malam)
// [FIX 2026-08-06] Tambah skip jam 16 (16:00-16:59 = peak checkout karyawan)
Schedule::command('fingerspot:sync')
    ->cron('*/5 6,9-15,19-21 * * *')  // skip jam 7,8,16,17,18 (peak attendance hours)
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/fingerspot-sync.log'));

// Schedule: Recalculate overtime same-day jam 19:00 (geser 30 menit dari 18:30)
// [FIX 2026-08-06] Geser dari 18:30 → 19:00 agar tidak bertabrakan dengan
// generate-absent 17:30 dan queue:work peak sehingga MySQL tidak overload
Schedule::command('attendance:recalculate-overtime', ['--from' => now()->format('Y-m-d')])
    ->dailyAt('19:00')
    ->weekdays()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/overtime-recalculate.log'));

// Schedule: Recalculate overtime final jam 02:00 (off-peak hours)
// Recheck data kemarin untuk menangkap checkout Fingerspot yang masuk setelah 18:30
Schedule::command('attendance:recalculate-overtime', ['--from' => now()->subDay()->format('Y-m-d')])
    ->dailyAt('02:00')
    ->weekdays()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/overtime-recalculate.log'));

// Schedule: Queue worker — proses jobs (email broadcast, notifikasi, dll)
// --queue=default : eksplisit hanya proses queue 'default' (alpha notification, broadcast)
// --sleep=3       : tunggu 3 detik sebelum poll ulang jika queue kosong (kurangi DB polling)
// --max-time=50   : berhenti setelah 50 detik (memberi jeda antar run agar tidak tumpang-tindih)
// --stop-when-empty: berhenti segera jika queue sudah kosong
Schedule::command('queue:work', ['--queue' => 'default', '--stop-when-empty', '--max-time=50', '--sleep=3'])
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/queue-worker.log'));

// [FIX 2026-08-08] session:cleanup DIHAPUS — SESSION_DRIVER=redis di production,
// Redis mengelola TTL/expiry session secara otomatis tanpa perlu cleanup manual.
// Menjalankan session:cleanup dengan SESSION_DRIVER=redis hanya membuang waktu
// dan menambah beban cron tanpa manfaat.

// Schedule: Bersihkan cache file expired setiap hari jam 01:00
// Mencegah storage/framework/cache membengkak (untuk cache tag yang pakai file/database)
Schedule::command('cache:prune-stale-tags')
    ->dailyAt('01:00')
    ->appendOutputTo(storage_path('logs/cache-cleanup.log'));
