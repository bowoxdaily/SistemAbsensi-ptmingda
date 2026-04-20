<?php

namespace App\Console\Commands;

use App\Models\Karyawans;
use Illuminate\Console\Command;

class NormalizeGeographicData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geographic:normalize {--dry-run : Preview changes without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize geographic data to uppercase (CITY, PROVINSI, KABUPATEN, KECAMATAN, DESA)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('Normalizing geographic data to UPPERCASE...');
        $this->line('');

        // Get all employees with at least one geographic field filled.
        $employees = Karyawans::query()
            ->where(function ($query) {
                $query->whereNotNull('city')
                    ->orWhereNotNull('province')
                    ->orWhereNotNull('kabupaten')
                    ->orWhereNotNull('kecamatan')
                    ->orWhereNotNull('desa');
            })
            ->get();

        if ($employees->isEmpty()) {
            $this->info('✓ Tidak ada data yang perlu di-normalize');
            return;
        }

        $this->info("📊 Ditemukan {$employees->count()} karyawan dengan data geografis");
        $this->line('');

        $updated = 0;
        $skipped = 0;

        foreach ($employees as $employee) {
            $original = [
                'city' => $employee->city,
                'province' => $employee->province,
                'kabupaten' => $employee->kabupaten,
                'kecamatan' => $employee->kecamatan,
                'desa' => $employee->desa,
            ];

            // Normalize to uppercase
            $normalized = [
                'city' => $employee->city ? strtoupper(trim($employee->city)) : null,
                'province' => $employee->province ? strtoupper(trim($employee->province)) : null,
                'kabupaten' => $employee->kabupaten ? strtoupper(trim($employee->kabupaten)) : null,
                'kecamatan' => $employee->kecamatan ? strtoupper(trim($employee->kecamatan)) : null,
                'desa' => $employee->desa ? strtoupper(trim($employee->desa)) : null,
            ];

            // Check if data changed
            $changed = false;
            foreach ($normalized as $key => $value) {
                if ($original[$key] !== $value) {
                    $changed = true;
                    break;
                }
            }

            if ($changed) {
                $this->line("─────────────────────────────────────────────────────");
                $this->line("Karyawan: {$employee->name} ({$employee->employee_code})");

                foreach ($normalized as $key => $value) {
                    if ($original[$key] !== $value) {
                        $this->line("  $key: '{$original[$key]}' → '{$value}'");
                    }
                }

                if (!$dryRun) {
                    $employee->update($normalized);
                    $this->info("  → Disimpan ✓");
                } else {
                    $this->line("  [DRY RUN - Tidak disimpan]");
                }

                $updated++;
            } else {
                $skipped++;
            }
        }

        $this->line('');
        $this->line('═════════════════════════════════════════════════════');
        $this->info("📈 Summary:");
        $this->line("  Karyawan yang di-update: $updated");
        $this->line("  Sudah uppercase: $skipped");

        if ($dryRun) {
            $this->warn("⚠️  Mode DRY RUN - Data belum disimpan.");
            $this->line("Jalankan tanpa --dry-run untuk menyimpan data");
        } else {
            $this->info("✓ Normalisasi selesai!");
        }
    }
}
