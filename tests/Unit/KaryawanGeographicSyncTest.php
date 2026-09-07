<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Exports\KaryawanTemplateExport;
use App\Exports\KaryawanExport;
use App\Services\IndonesianGeographicHelper;

class KaryawanGeographicSyncTest extends TestCase
{
    public function test_template_export_columns_aligned(): void
    {
        $ref = new \ReflectionClass(KaryawanTemplateExport::class);
        /** @var KaryawanTemplateExport $template */
        $template = $ref->newInstanceWithoutConstructor();

        $headings = $template->headings();
        $widths = $template->columnWidths();

        $this->assertCount(39, $headings);
        $this->assertCount(39, $widths);

        $this->assertContains('Provinsi', $headings);
        $this->assertContains('Kabupaten/Kota', $headings);
        $this->assertContains('Kecamatan', $headings);
        $this->assertContains('Desa/Kelurahan', $headings);
        $this->assertContains('Kota', $headings);
    }

    public function test_export_columns_aligned(): void
    {
        $export = new KaryawanExport();
        $headings = $export->headings();
        $widths = $export->columnWidths();

        $this->assertCount(40, $headings);
        $this->assertCount(40, $widths);

        $this->assertContains('Provinsi', $headings);
        $this->assertContains('Kabupaten/Kota', $headings);
        $this->assertContains('Kecamatan', $headings);
        $this->assertContains('Desa/Kelurahan', $headings);
        $this->assertContains('Kota', $headings);

        $dummy = (object) [
            'employee_code' => 'EMP999',
            'nik' => '3212010000000001',
            'name' => 'John Doe',
            'gender' => 'L',
            'birth_place' => 'Indramayu',
            'birth_date' => '1995-01-01',
            'marital_status' => 'Belum Menikah',
            'tanggungan_anak' => 0,
            'agama' => 'Islam',
            'bangsa' => 'Indonesia',
            'status_kependudukan' => 'WNI',
            'nama_ibu_kandung' => 'Jane',
            'kartu_keluarga' => '3212010000000002',
            'department' => (object) ['name' => 'Produksi'],
            'subDepartment' => (object) ['name' => 'Sewing'],
            'position' => (object) ['name' => 'Operator'],
            'lulusan_sekolah' => 'SMA',
            'join_date' => '2023-01-01',
            'employment_status' => 'Tetap',
            'serikat' => 'Non Serikat',
            'workSchedule' => (object) ['name' => 'Non Shift'],
            'status' => 'active',
            'tanggal_resign' => null,
            'tanggal_phk' => null,
            'termination_recommendation' => null,
            'bank' => 'BCA',
            'nomor_rekening' => '1234567890',
            'tax_npwp' => null,
            'bpjs_kesehatan' => null,
            'bpjs_ketenagakerjaan' => null,
            'address' => 'Jl. Merdeka No. 1',
            'province' => 'JAWA BARAT',
            'kabupaten' => 'KABUPATEN INDRAMAYU',
            'kecamatan' => 'LOSARANG',
            'desa' => 'LOSARANG',
            'city' => 'INDRAMAYU',
            'postal_code' => '45253',
            'phone' => '081234567890',
            'email' => 'john@test.com',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '081234567891',
        ];

        $mapped = $export->map($dummy);
        $this->assertCount(40, $mapped);
        $this->assertEquals('JAWA BARAT', $mapped[30]);
        $this->assertEquals('KABUPATEN INDRAMAYU', $mapped[31]);
        $this->assertEquals('LOSARANG', $mapped[32]);
        $this->assertEquals('LOSARANG', $mapped[33]);
        $this->assertEquals('INDRAMAYU', $mapped[34]);
    }

    public function test_import_geographic_resolution_rules(): void
    {
        // 1. Explicit geographic resolution
        $rawAddress = 'Blok Tipar RT 01 RW 02';
        $rawProvince = 'Jawa Barat';
        $rawKabupaten = 'Kabupaten Indramayu';
        $rawKecamatan = 'Kandanghaur';
        $rawDesa = 'Karanganyar';
        $rawCity = 'Indramayu';

        $geo = IndonesianGeographicHelper::parseFullAddress(
            $rawAddress,
            $rawCity ?? $rawKabupaten,
            $rawProvince
        );

        $kabupaten = $rawKabupaten
            ? IndonesianGeographicHelper::normalizeKabupatenKota($rawKabupaten, $rawAddress, $rawKecamatan)
            : ($geo['kabupaten'] ?? null);
        $kecamatan = $rawKecamatan
            ? IndonesianGeographicHelper::cleanKecamatan($rawKecamatan)
            : ($geo['kecamatan'] ?? null);
        $desa = $rawDesa
            ? IndonesianGeographicHelper::cleanDesa($rawDesa)
            : ($geo['desa'] ?? null);
        $province = $rawProvince
            ? IndonesianGeographicHelper::normalizeProvince($rawProvince, $rawAddress, $rawCity ?? $rawKabupaten)
            : ($geo['province'] ?? 'JAWA BARAT');
        $city = $rawCity
            ? strtoupper($rawCity)
            : ($kabupaten ? preg_replace('/^(KABUPATEN|KOTA)\s+/i', '', $kabupaten) : 'INDRAMAYU');

        $this->assertEquals('JAWA BARAT', $province);
        $this->assertEquals('KABUPATEN INDRAMAYU', $kabupaten);
        $this->assertEquals('KANDANGHAUR', $kecamatan);
        $this->assertEquals('KARANGANYAR', $desa);
        $this->assertEquals('INDRAMAYU', $city);

        // 2. Fallback parse from alamat when kota/provinsi/kabupaten omitted
        $rawAddress2 = 'DESA LOSARANG RT 002 RW 001 KEL/DESA LOSARANG KEC LOSARANG INDRAMAYU JAWA BARAT';
        $geo2 = IndonesianGeographicHelper::parseFullAddress($rawAddress2, null, null);

        $kabupaten2 = $geo2['kabupaten'];
        $kecamatan2 = $geo2['kecamatan'];
        $desa2 = $geo2['desa'];
        $province2 = $geo2['province'] ?? 'JAWA BARAT';
        $city2 = $kabupaten2 ? preg_replace('/^(KABUPATEN|KOTA)\s+/i', '', $kabupaten2) : 'INDRAMAYU';

        $this->assertEquals('JAWA BARAT', $province2);
        $this->assertEquals('KABUPATEN INDRAMAYU', $kabupaten2);
        $this->assertEquals('LOSARANG', $kecamatan2);
        $this->assertEquals('LOSARANG', $desa2);
        $this->assertEquals('INDRAMAYU', $city2);
    }
}

