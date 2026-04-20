<?php

namespace App\Console\Commands;

use App\Models\Karyawans;
use Illuminate\Console\Command;

class MigrateGeographicData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geographic:migrate {--dry-run : Show what would be migrated without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate geographic data from existing fields and parse address field';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $employees = Karyawans::where('status', 'active')
            ->whereNull('kabupaten') // Only migrate those without data yet
            ->get();

        if ($employees->isEmpty()) {
            $this->info('✓ Semua karyawan sudah memiliki data geografis lengkap!');
            return;
        }

        $this->info("📊 Ditemukan {$employees->count()} karyawan yang perlu di-update");
        $this->line('');

        $updated = 0;
        $skipped = 0;

        foreach ($employees as $employee) {
            $this->line("─────────────────────────────────────────────────────");
            $this->line("Karyawan: {$employee->name} ({$employee->employee_code})");
            $this->line("Province: {$employee->province}");
            $this->line("City: {$employee->city}");
            $this->line("Alamat: {$employee->address}");
            $this->line('');

            // Try to extract geographic data from existing fields and address
            $data = $this->extractGeographicData($employee);

            if ($data['success']) {
                $this->line("✓ Hasil parsing:");
                $this->line("  Kabupaten: " . ($data['kabupaten'] ?? 'Menggunakan city'));
                $this->line("  Kecamatan: " . ($data['kecamatan'] ?? '-'));
                $this->line("  Desa: " . ($data['desa'] ?? '-'));

                if (!$dryRun) {
                    $employee->update($data);
                    $this->info("  → Disimpan ✓");
                } else {
                    $this->line("  [DRY RUN - Tidak disimpan]");
                }

                $updated++;
            } else {
                $this->warn("✗ Tidak bisa parse alamat");
                $this->line("  Manual entry diperlukan untuk karyawan ini");
                $skipped++;
            }

            $this->line('');
        }

        $this->line('═════════════════════════════════════════════════════');
        $this->info("📈 Summary:");
        $this->line("  Berhasil di-update: $updated");
        $this->line("  Perlu manual: $skipped");

        if ($dryRun) {
            $this->warn("⚠️  Mode DRY RUN - Data belum disimpan.");
            $this->line("Jalankan tanpa --dry-run untuk menyimpan data");
        }
    }

    /**
     * Extract geographic data from employee fields
     */
    private function extractGeographicData(Karyawans $employee): array
    {
        $address = strtoupper($employee->address ?? '');
        $city = $employee->city ?? '';
        $province = $employee->province ?? '';

        // Kabupaten biasanya sama dengan city - normalize to UPPERCASE
        $kabupaten = $city ? strtoupper(trim($city)) : null;

        // Try to extract kecamatan and desa from address
        $addressParts = $this->parseAddress($address);

        return [
            'success' => !empty($kabupaten),
            'kabupaten' => $kabupaten,
            'kecamatan' => $addressParts['kecamatan'] ?? null,
            'desa' => $addressParts['desa'] ?? null,
        ];
    }

    /**
     * Parse address string to extract kecamatan and desa
     * 
     * Expected patterns:
     * - "DESA LOSARANG RT 010 RW 003 KEL/DESA LOSARANG KEC LOSARANG INDRAMAYU JAWA BARAT"
     * - "JL. PENDIDIKAN NO. 123 KELURAHAN CITARUM KECAMATAN BANDUNG TENGAH"
     */
    private function parseAddress(string $address): array
    {
        $result = [
            'desa' => null,
            'kecamatan' => null,
        ];

        // Clean address
        $address = trim($address);

        // Pattern 1: Look for "DESA" or "KEL" or "KELURAHAN" 
        if (preg_match('/(?:DESA|KEL|KELURAHAN)\s+([A-Z\s]+?)(?:\s+RT|\s+RW|\s+KEC|$)/i', $address, $matches)) {
            $result['desa'] = trim($matches[1]);
        }

        // Pattern 2: Look for "KEC" or "KECAMATAN" followed by name
        // This pattern looks for KEC followed by word(s) that are not city/province names
        if (preg_match('/\s+KEC\s+([A-Z\s]+?)(?:\s+INDRAMAYU|\s+BANDUNG|\s+JAWA|\s+SUMATERA|\s+SULAWESI|\s+KALIMANTAN|\s+BALI|\s+NTT|\s+MALUKU|$)/i', $address, $matches)) {
            $potentialKec = trim($matches[1]);
            // Filter out noise
            if (!preg_match('/^\d+$|^RT$|^RW$/i', $potentialKec)) {
                $result['kecamatan'] = $potentialKec;
            }
        }

        // Pattern 3: Try alternative "KECAMATAN" keyword
        if (!$result['kecamatan']) {
            if (preg_match('/\s+KECAMATAN\s+([A-Z\s]+?)(?:\s+KOTA|\s+KAB|\s+JAWA|$)/i', $address, $matches)) {
                $result['kecamatan'] = trim($matches[1]);
            }
        }

        return $result;
    }
}
