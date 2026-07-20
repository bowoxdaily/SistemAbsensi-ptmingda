<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Membersihkan file session yang sudah expired.
 *
 * Saat SESSION_DRIVER=file, session lama menumpuk di storage/framework/sessions.
 * Jalankan command ini via cron setiap tengah malam agar folder session tidak membengkak.
 *
 * Cron: 0 0 * * * php /path/to/artisan session:cleanup
 */
class CleanupExpiredSessions extends Command
{
    protected $signature   = 'session:cleanup {--dry-run : Hanya tampilkan tanpa hapus}';
    protected $description = 'Hapus file session yang sudah expired (untuk SESSION_DRIVER=file)';

    public function handle(): int
    {
        $sessionPath    = config('session.files', storage_path('framework/sessions'));
        $lifetime       = (int) config('session.lifetime', 120); // menit
        $expiredBefore  = now()->subMinutes($lifetime)->timestamp;
        $isDryRun       = $this->option('dry-run');

        if (!File::isDirectory($sessionPath)) {
            $this->error("Session directory tidak ditemukan: {$sessionPath}");
            return self::FAILURE;
        }

        $files   = File::files($sessionPath);
        $deleted = 0;
        $total   = count($files);

        foreach ($files as $file) {
            if ($file->getMTime() < $expiredBefore) {
                if (!$isDryRun) {
                    File::delete($file->getPathname());
                }
                $deleted++;
            }
        }

        $action = $isDryRun ? 'Akan dihapus' : 'Dihapus';
        $this->info("{$action}: {$deleted} dari {$total} file session expired.");

        return self::SUCCESS;
    }
}
