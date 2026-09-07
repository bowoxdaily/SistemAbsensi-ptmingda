<?php

namespace Tests\Unit;

use App\Services\IndonesianGeographicHelper;
use PHPUnit\Framework\TestCase;

class IndonesianGeographicHelperTest extends TestCase
{
    public function test_kota_vs_kabupaten_classification()
    {
        // Autonomous cities without shared name
        $this->assertEquals('KOTA MAKASSAR', IndonesianGeographicHelper::normalizeKabupatenKota('MAKASSAR'));
        $this->assertEquals('KOTA SURABAYA', IndonesianGeographicHelper::normalizeKabupatenKota('SURABAYA'));

        // Shared names: Cirebon
        $this->assertEquals(
            'KOTA CIREBON',
            IndonesianGeographicHelper::normalizeKabupatenKota('CIREBON', 'JL KESAMBI NO 10 KEC KESAMBI', 'KESAMBI')
        );
        $this->assertEquals(
            'KABUPATEN CIREBON',
            IndonesianGeographicHelper::normalizeKabupatenKota('CIREBON', 'DESA PANGURAGAN KEC PANGURAGAN', 'PANGURAGAN')
        );

        // Shared names: Sukabumi
        $this->assertEquals(
            'KOTA SUKABUMI',
            IndonesianGeographicHelper::normalizeKabupatenKota('SUKABUMI', 'JL ACEH KEC CIBEUREUM', 'CIBEUREUM')
        );
        $this->assertEquals(
            'KABUPATEN SUKABUMI',
            IndonesianGeographicHelper::normalizeKabupatenKota('SUKABUMI', 'DESA KOMPA KEC PARUNGKUDA', 'PARUNGKUDA')
        );

        // Standard kabupaten
        $this->assertEquals('KABUPATEN INDRAMAYU', IndonesianGeographicHelper::normalizeKabupatenKota('INDRAMAYU'));
        $this->assertEquals('KABUPATEN BREBES', IndonesianGeographicHelper::normalizeKabupatenKota('BREBES'));
    }

    public function test_province_normalization()
    {
        $this->assertEquals('JAWA BARAT', IndonesianGeographicHelper::normalizeProvince('JAA'));
        $this->assertEquals('JAWA BARAT', IndonesianGeographicHelper::normalizeProvince('JAWA BRAT'));
        $this->assertEquals('LAMPUNG', IndonesianGeographicHelper::normalizeProvince('LAMOUNG'));
        $this->assertEquals('LAMPUNG', IndonesianGeographicHelper::normalizeProvince('LAMPUNG SELATAN'));
        $this->assertEquals('SUMATERA UTARA', IndonesianGeographicHelper::normalizeProvince('SUMATERA UTRA'));
        $this->assertEquals('JAWA TENGAH', IndonesianGeographicHelper::normalizeProvince('JAWA BARAT', null, 'WONOGIRI'));
        $this->assertEquals('NUSA TENGGARA TIMUR', IndonesianGeographicHelper::normalizeProvince(null, null, 'KUATNAMA'));
    }

    public function test_parse_full_address()
    {
        $parsed = IndonesianGeographicHelper::parseFullAddress(
            'GENTING PURI JL.ACEH BLOK T NO.6 RT 004 RW 010 KEL/DESA BABAKAN KEC CIBEUREUM SUKABUMI JAWA BARAT',
            'SUKABUMI',
            'JAWA BARAT'
        );

        $this->assertEquals('JAWA BARAT', $parsed['province']);
        $this->assertEquals('KOTA SUKABUMI', $parsed['kabupaten']);
        $this->assertEquals('CIBEUREUM', $parsed['kecamatan']);
        $this->assertEquals('BABAKAN', $parsed['desa']);
    }
}
