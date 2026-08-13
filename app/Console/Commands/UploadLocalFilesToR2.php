<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class UploadLocalFilesToR2 extends Command
{
    protected $signature = "storage:upload-to-r2
                            {folder? : Sub-folder di storage/app/public/ yang akan di-upload (kosong = semua folder)}
                            {--dry-run : Tampilkan daftar file tanpa upload}";

    protected $description = "Upload file dari local storage (storage/app/public/) ke Cloudflare R2";

    public function handle(): int
    {
        $folder    = $this->argument("folder") ?? "";
        $dryRun    = $this->option("dry-run");
        $localDisk = Storage::disk("public");
        $r2Disk    = Storage::disk("r2");

        $files = $localDisk->allFiles($folder);

        if (empty($files)) {
            $this->warn("Tidak ada file ditemukan" . ($folder ? " di folder: {$folder}" : "") . ".");
            return self::SUCCESS;
        }

        $this->info("Ditemukan " . count($files) . " file di local storage.");

        if ($dryRun) {
            $this->info("[DRY RUN] File yang akan di-upload ke R2:");
            foreach ($files as $file) {
                $this->line("  -> " . $file);
            }
            return self::SUCCESS;
        }

        $success = 0;
        $skip    = 0;
        $fail    = 0;

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $file) {
            try {
                // Skip jika sudah ada di R2
                if ($r2Disk->exists($file)) {
                    $skip++;
                    $bar->advance();
                    continue;
                }

                $content  = $localDisk->get($file);
                $mimeType = $localDisk->mimeType($file) ?: "application/octet-stream";

                $r2Disk->put($file, $content, [
                    "visibility"  => "public",
                    "ContentType" => $mimeType,
                ]);

                $success++;
            } catch (\Throwable $e) {
                $fail++;
                $this->newLine();
                $this->error("GAGAL: " . $file . " - " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ["Status", "Jumlah"],
            [
                ["Berhasil di-upload ke R2", $success],
                ["Sudah ada di R2 (skip)",   $skip],
                ["Gagal",                     $fail],
            ]
        );

        if ($fail > 0) {
            $this->warn("Ada " . $fail . " file gagal di-upload. Cek log untuk detail.");
            return self::FAILURE;
        }

        $this->info("Semua file berhasil di-upload ke R2!");
        return self::SUCCESS;
    }
}