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

        $this->assertStringContainsString('preg_match', $source);
        $this->assertStringContainsString("'npwp' => 'tax_npwp'", $source);
        $this->assertStringContainsString('KaryawanDataKosongExport::FIELDS', $source);
        $this->assertStringContainsString("'pendidikan_terakhir' => 'lulusan_sekolah'", $source);
        $this->assertStringContainsString("'nama_lengkap' => 'name'", $source);
        $this->assertStringContainsString("'jenis_kelamin' => 'gender'", $source);
        $this->assertStringContainsString("'tanggal_lahir' => 'birth_date'", $source);
        $this->assertStringContainsString("'alamat_lengkap' => 'address'", $source);
        $this->assertStringContainsString("'kota' => 'city'", $source);
        $this->assertStringContainsString("'kode_pos' => 'postal_code'", $source);
        $this->assertStringContainsString("excelToDateTimeObject", $source);
        $this->assertStringContainsString("where('status', 'active')", $source);
        $rules = $import->rules();
        $this->assertSame('nullable|exists:employees,employee_code', $rules['kode_karyawan']);
        $this->assertSame('nullable|date', $rules['birth_date']);
        $this->assertSame('nullable|date', $rules['join_date']);
        $this->assertSame('nullable|integer|exists:departments,id', $rules['department_id']);
        $this->assertSame('nullable|integer|exists:positions,id', $rules['position_id']);
        $this->assertSame('nullable|integer|exists:work_schedules,id', $rules['work_schedule_id']);
        $this->assertSame('nullable|max:255', $rules['lulusan_sekolah']);
        $this->assertSame('nullable|max:255', $rules['bpjs_kesehatan']);
        $this->assertSame('nullable|max:255', $rules['bpjs_ketenagakerjaan']);
        $this->assertArrayHasKey('address', $rules);
        $this->assertArrayHasKey('emergency_contact_phone', $rules);
    }
}