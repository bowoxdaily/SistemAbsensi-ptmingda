<?php

namespace Tests\Unit;

use App\Imports\KaryawanDataKosongImport;
use PHPUnit\Framework\TestCase;

class KaryawanDataKosongImportTest extends TestCase
{
    public function test_import_only_updates_supported_fields_and_skips_empty_values(): void
    {
        $import = new KaryawanDataKosongImport();
        $source = file_get_contents(__DIR__ . '/../../app/Imports/KaryawanDataKosongImport.php');

        $this->assertStringContainsString('if ($value !== \'\' && $value !== \'-\')', $source);
        $this->assertStringContainsString("'npwp' => 'tax_npwp'", $source);
        $this->assertStringContainsString("'tax_npwp' => 'tax_npwp'", $source);
        $this->assertStringContainsString("'bpjs_kesehatan' => 'bpjs_kesehatan'", $source);
        $this->assertStringContainsString("'bpjs_ketenagakerjaan' => 'bpjs_ketenagakerjaan'", $source);
        $this->assertStringContainsString("where('status', 'active')", $source);
        $this->assertSame([
            'kode_karyawan' => 'nullable|exists:employees,employee_code',
            'bank' => 'nullable|max:100',
            'nomor_rekening' => 'nullable|max:100',
            'npwp' => 'nullable|max:100',
            'tax_npwp' => 'nullable|max:100',
            'bpjs_kesehatan' => 'nullable|max:100',
            'bpjs_ketenagakerjaan' => 'nullable|max:100',
        ], $import->rules());
    }
}