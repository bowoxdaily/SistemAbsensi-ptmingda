<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\IndonesianGeographicHelper;
use Illuminate\Console\Command;

class NormalizeGeographicDataCommand extends Command
{
    protected $signature = 'geographic:normalize {--dry-run : Only show changes without saving}';
    protected $description = 'Normalize employee province, kabupaten/kota, kecamatan, and desa';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $employees = Employee::all();
        $total = $employees->count();
        $updatedCount = 0;

        $this->info("Processing {$total} employees... (Dry-run: " . ($isDryRun ? 'YES' : 'NO') . ")");

        foreach ($employees as $employee) {
            $parsed = IndonesianGeographicHelper::parseFullAddress(
                (string) $employee->address,
                $employee->city,
                $employee->province
            );

            // Determine target values
            $newProvince = $parsed['province'] ?? $employee->province;
            $newKabupaten = $parsed['kabupaten'] ?? IndonesianGeographicHelper::normalizeKabupatenKota($employee->kabupaten ?: $employee->city, $employee->address);
            
            // For kecamatan, use existing cleaned if valid, else parsed
            $cleanedExistingKec = IndonesianGeographicHelper::cleanKecamatan($employee->kecamatan);
            $newKecamatan = $cleanedExistingKec ?: $parsed['kecamatan'];

            // For desa, use existing cleaned if valid, else parsed
            $cleanedExistingDesa = IndonesianGeographicHelper::cleanDesa($employee->desa);
            $newDesa = $cleanedExistingDesa ?: $parsed['desa'];

            // Check if changes needed
            $changed = false;
            $changes = [];

            if ($employee->province !== $newProvince) {
                $changes['province'] = [$employee->province, $newProvince];
                $employee->province = $newProvince;
                $changed = true;
            }

            if ($employee->kabupaten !== $newKabupaten) {
                $changes['kabupaten'] = [$employee->kabupaten, $newKabupaten];
                $employee->kabupaten = $newKabupaten;
                $changed = true;
            }

            if ($newKecamatan && $employee->kecamatan !== $newKecamatan) {
                $changes['kecamatan'] = [$employee->kecamatan, $newKecamatan];
                $employee->kecamatan = $newKecamatan;
                $changed = true;
            }

            if ($newDesa && $employee->desa !== $newDesa) {
                $changes['desa'] = [$employee->desa, $newDesa];
                $employee->desa = $newDesa;
                $changed = true;
            }

            if ($changed) {
                $updatedCount++;
                $desc = collect($changes)->map(fn($v, $k) => "$k: '{$v[0]}' -> '{$v[1]}'")->implode(', ');
                $this->line("[#{$employee->id}] {$employee->name}: {$desc}");

                if (!$isDryRun) {
                    $employee->save();
                }
            }
        }

        $this->info("Completed. Updated {$updatedCount} of {$total} employees.");
        return self::SUCCESS;
    }
}
