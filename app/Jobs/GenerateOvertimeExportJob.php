<?php

namespace App\Jobs;

use App\Exports\OvertimeExport;
use App\Models\ExportFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class GenerateOvertimeExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 300; // 5 minutes max

    public function __construct(
        public int $exportFileId,
        public string $dateFrom,
        public string $dateTo,
        public ?string $category = null,
        public ?string $search = null,
        public ?int $employeeId = null,
    ) {}

    public function handle(): void
    {
        $exportFile = ExportFile::find($this->exportFileId);
        if (!$exportFile) {
            return;
        }

        $exportFile->update(['status' => 'processing']);

        try {
            ini_set('memory_limit', '512M');

            Storage::disk('local')->makeDirectory('exports');

            $path = 'exports/' . $exportFile->filename;

            Excel::store(
                new OvertimeExport(
                    $this->dateFrom,
                    $this->dateTo,
                    $this->category,
                    $this->search,
                    $this->employeeId,
                ),
                $path,
                'local'
            );

            $exportFile->update([
                'status' => 'done',
                'path' => $path,
            ]);

            Log::info("Overtime export #{$this->exportFileId} selesai: {$path}");
        } catch (\Throwable $e) {
            $exportFile->update([
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 500),
            ]);

            Log::error("Overtime export #{$this->exportFileId} gagal: " . $e->getMessage());

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $exportFile = ExportFile::find($this->exportFileId);
        if ($exportFile && !$exportFile->isFailed()) {
            $exportFile->update([
                'status' => 'failed',
                'error' => mb_substr($exception->getMessage(), 0, 500),
            ]);
        }
    }
}
