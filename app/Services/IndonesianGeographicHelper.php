<?php

namespace App\Services;

class IndonesianGeographicHelper
{
    private const EXCLUSIVE_KOTA = [
        'BATAM', 'CIMAHI', 'DEPOK', 'BANJAR', 'SALATIGA', 'SURAKARTA', 'SOLO',
        'BATU', 'CILEGON', 'TANGERANG SELATAN', 'YOGYAKARTA', 'BANDA ACEH',
        'MEDAN', 'PADANG', 'PEKANBARU', 'JAMBI', 'BENGKULU', 'PALEMBANG',
        'PANGKALPINANG', 'BANDAR LAMPUNG', 'METRO', 'DENPASAR', 'MATARAM',
        'KUPANG', 'PONTIANAK', 'BANJARMASIN', 'PALANGKA RAYA', 'BALIKPAPAN',
        'SAMARINDA', 'MAKASSAR', 'PALU', 'MANADO', 'AMBON', 'JAYAPURA', 'SURABAYA',
        'JAKARTA PUSAT', 'JAKARTA UTARA', 'JAKARTA BARAT', 'JAKARTA SELATAN', 'JAKARTA TIMUR'
    ];

    private const KOTA_KECAMATAN_MAP = [
        'CIREBON' => ['HARJAMUKTI', 'KEJAKSAN', 'KESAMBI', 'LEMAHWUNGKUK', 'PEKALIPAN'],
        'SUKABUMI' => ['BAROS', 'CIBEUREUM', 'CIKOLE', 'CITAMIANG', 'GUNUNGPUYUH', 'GUNUNG PUYUH', 'LEMBURSITU', 'WARUDOYONG'],
        'TEGAL' => ['MARGADANA', 'TEGAL BARAT', 'TEGAL SELATAN', 'TEGAL TIMUR'],
        'BANDUNG' => [
            'ANDIR', 'ANTAPANI', 'ARCAMANIK', 'ASTANAANYAR', 'BABAKANCIPARAY', 'BANDUNG KIDUL',
            'BANDUNG KULON', 'BANDUNG WETAN', 'BATUNUNGGAL', 'BOJONGLOA KALER', 'BOJONGLOA KIDUL',
            'BUAHBATU', 'CIBEUNYING KALER', 'CIBEUNYING KIDUL', 'CIBIRU', 'CICENDO', 'CIDADAP',
            'CINAMBO', 'COBLONG', 'GEDEBAGE', 'KIARACONDONG', 'LENGKONG', 'MANDALAJATI',
            'PANYILEUKAN', 'RANCASARI', 'REGOL', 'SUKAJADI', 'SUKASARI', 'SUMUR BANDUNG', 'UJUNG BERUNG'
        ],
        'BEKASI' => ['BANTAR GEBANG', 'BEKASI BARAT', 'BEKASI SELATAN', 'BEKASI TIMUR', 'BEKASI UTARA', 'JATIASIH', 'JATISAMPURNA', 'MEDAN SATRIA', 'MUSTIKA JAYA', 'PONDOK GEDE', 'PONDOK MELATI', 'RAWALUMBU'],
        'BOGOR' => ['BOGOR BARAT', 'BOGOR SELATAN', 'BOGOR TENGAH', 'BOGOR TIMUR', 'BOGOR UTARA', 'TANAH SAREAL'],
        'TANGERANG' => ['BATUCEPER', 'BENDA', 'CIBODAS', 'CILEDUG', 'CIPONDOH', 'JATIUWUNG', 'KARANG TENGAH', 'KARAWACI', 'LARANGAN', 'NEGLASARI', 'PERIUK', 'PINANG', 'TANGERANG'],
        'TASIKMALAYA' => ['BUNGURSARI', 'CIBEUREUM', 'CIHIDEUNG', 'CIPEDES', 'INDIHIANG', 'KAWALU', 'MANGKUBUMI', 'PURBARATU', 'TAMANSARI', 'TAWANG'],
    ];

    public static function normalizeProvince(?string $province, ?string $address = null, ?string $city = null): ?string
    {
        $p = strtoupper(trim((string)$province));
        $c = strtoupper(trim((string)$city));
        $a = strtoupper(trim((string)$address));

        if ($c === 'WONOGIRI') return 'JAWA TENGAH';
        if ($c === 'KUATNAMA' || str_contains($a, 'KUATNANA') || str_contains($a, 'TETAF')) return 'NUSA TENGGARA TIMUR';
        if ($c === 'TALANG PADANG' && str_contains($a, 'BALARAJA')) return 'BANTEN';

        if (in_array($p, ['JAA', 'JAWA BRAT', 'JAWABARAT', 'INDRAMAYU']) || str_contains($p, 'JAWA BARAT')) return 'JAWA BARAT';
        if (in_array($p, ['LAMOUNG', 'LAMPUNG SELATAN']) || str_contains($p, 'LAMPUNG')) return 'LAMPUNG';
        if ($p === 'SUMATERA UTRA' || $p === 'SUMUT') return 'SUMATERA UTARA';
        if (str_contains($p, 'JAWA TENGAH')) return 'JAWA TENGAH';
        if (str_contains($p, 'JAWA TIMUR')) return 'JAWA TIMUR';
        if (str_contains($p, 'BANTEN')) return 'BANTEN';

        return $p !== '' ? $p : 'JAWA BARAT';
    }

    public static function normalizeKabupatenKota(?string $raw, ?string $address = null, ?string $kecamatan = null): ?string
    {
        $raw = strtoupper(trim((string)$raw));
        $addr = strtoupper(trim((string)$address));
        $kec = strtoupper(trim((string)$kecamatan));

        if ($raw === 'KUATNAMA' || str_contains($addr, 'KUATNANA')) return 'KABUPATEN TIMOR TENGAH SELATAN';
        if ($raw === 'TALANG PADANG' && (str_contains($addr, 'BALARAJA') || str_contains($addr, 'TANGERANG'))) return 'KABUPATEN TANGERANG';
        if ($raw === 'LAMPUNG') {
            if (str_contains($addr, 'NATAR')) return 'KABUPATEN LAMPUNG SELATAN';
            if (str_contains($addr, 'BANJAR AGUNG') || str_contains($addr, 'TULANG BAWANG')) return 'KABUPATEN TULANG BAWANG';
        }
        if ($raw === 'NIAS' && str_contains($addr, 'SITOLU ORI')) return 'KABUPATEN NIAS UTARA';

        if (str_starts_with($raw, 'KABUPATEN ')) return preg_replace('/\s+/', ' ', $raw);
        if (str_starts_with($raw, 'KOTA ')) return preg_replace('/\s+/', ' ', $raw);
        if (str_starts_with($raw, 'KAB. ')) return 'KABUPATEN ' . trim(substr($raw, 5));
        if (str_starts_with($raw, 'KAB ')) return 'KABUPATEN ' . trim(substr($raw, 4));

        $base = $raw;
        if ($base === '' && $addr !== '') {
            if (str_contains($addr, 'INDRAMAYU')) $base = 'INDRAMAYU';
            elseif (str_contains($addr, 'CIREBON')) $base = 'CIREBON';
        }

        // If address specifies a different known district and does not contain original city
        if ($base === 'INDRAMAYU' && !str_contains($addr, 'INDRAMAYU')) {
            $knownDistricts = [
                'MAJALENGKA', 'KUNINGAN', 'CIREBON', 'SUBANG', 'GARUT', 'CIANJUR',
                'KARAWANG', 'BANDUNG', 'BEKASI', 'BOGOR', 'TANGERANG', 'SUKABUMI',
                'TASIKMALAYA', 'BREBES', 'TEGAL', 'NGANJUK', 'NGAWI', 'WONOGIRI'
            ];
            foreach ($knownDistricts as $kd) {
                if (str_contains($addr, $kd)) {
                    $base = $kd;
                    break;
                }
            }
        }

        if ($base === '') return null;

        if (in_array($base, self::EXCLUSIVE_KOTA, true)) return 'KOTA ' . $base;

        if (isset(self::KOTA_KECAMATAN_MAP[$base])) {
            if (str_contains($addr, 'KOTA ' . $base)) return 'KOTA ' . $base;
            if (str_contains($addr, 'KABUPATEN ' . $base) || str_contains($addr, 'KAB. ' . $base) || str_contains($addr, 'KAB ' . $base)) return 'KABUPATEN ' . $base;
            foreach (self::KOTA_KECAMATAN_MAP[$base] as $cityKec) {
                if ($kec === $cityKec || str_contains($kec, $cityKec) || str_contains($addr, 'KEC ' . $cityKec) || str_contains($addr, 'KEC. ' . $cityKec) || str_contains($addr, 'KECAMATAN ' . $cityKec)) {
                    return 'KOTA ' . $base;
                }
            }
            if ($base === 'TEGAL' && str_contains($addr, 'PESURUNGAN LOR')) return 'KOTA TEGAL';
            return 'KABUPATEN ' . $base;
        }

        return 'KABUPATEN ' . $base;
    }

    public static function cleanKecamatan(?string $kecamatan): ?string
    {
        if (!$kecamatan) return null;
        $k = strtoupper(trim($kecamatan, " \t\n\r\0\x0B,.:;"));
        $patterns = [
            '/\s+(?:SUKABUMI|TANGERANG|KUNINGAN|GARUT|KARAWANG|CIANJUR|SUBANG|INDRAMAYU|BANDUNG|TEGAL|CIREBON|NIAS UTARA|NIAS|NGANJUK|NGAWI|WONOGIRI|TASIKMALAYA|MAJALENGKA|BREBES|BOGOR|BEKASI)$/i',
            '/\s+KAB(?:\.|\b).*$/i',
            '/\s+KOTA(?:\.|\b).*$/i',
        ];
        foreach ($patterns as $pattern) {
            $k = preg_replace($pattern, '', $k);
        }
        $k = trim($k, " \t\n\r\0\x0B,.:;");
        $k = preg_replace('/\s+/', ' ', $k);
        $corrections = ['PEMEUNGPEUK' => 'PAMEUNGPEUK', 'CIBOGOH' => 'CIBOGO', 'SULARANG' => 'SUKALARANG', 'BALAJARA' => 'BALARAJA'];
        return $corrections[$k] ?? ($k !== '' ? $k : null);
    }

    public static function cleanDesa(?string $desa): ?string
    {
        if (!$desa) return null;
        $d = strtoupper(trim($desa, " \t\n\r\0\x0B,.:;"));
        $d = preg_replace('/\s+(?:RT|RW|KEC|BLOK|NO\.|NO|TEGAL|INDRAMAYU|CIREBON|BANDUNG|JAWA|BANTEN).*$/i', '', $d);
        $d = trim($d, " \t\n\r\0\x0B,.:;");
        $d = preg_replace('/\s+/', ' ', $d);
        if (preg_match('/^(?:RT|RW|\d+)/i', $d)) {
            return null;
        }
        return $d !== '' ? $d : null;
    }

    public static function parseFullAddress(string $address, ?string $city = null, ?string $province = null): array
    {
        $addr = strtoupper(trim($address));
        $addr = str_replace([',', ';'], ' ', $addr);
        $addr = preg_replace('/KABUPATEN([A-Z])/i', 'KABUPATEN $1', $addr);
        $addr = preg_replace('/\s+/', ' ', $addr);

        // 1. Desa extraction
        $desa = null;
        if (preg_match('/(?:KEL\/DESA|DESA\/KEL|KELURAHAN|DESA|KEL)\.?:?\s*([A-Z0-9\s\.\'\-]+?)(?:\s+(?:RT|RW|KEC|KECAMATAN|BLOK|KAB|KOTA|INDRAMAYU|BANDUNG|CIREBON|SUKABUMI|TEGAL|TANGERANG|GARUT|CIANJUR|SUBANG|JAWA|BANTEN|LAMPUNG|SUMATERA|\d{5}|$))/i', $addr, $m)) {
            $desa = self::cleanDesa($m[1]);
        }

        // 2. Kecamatan extraction
        $kecamatan = null;
        if (preg_match('/(?:KECAMATAN|KEC)\.?:?\s*([A-Z\s]+?)(?:\s+(?:KEL|DESA|KAB|KOTA|INDRAMAYU|BANDUNG|CIREBON|SUKABUMI|TEGAL|TANGERANG|GARUT|CIANJUR|SUBANG|JAWA|BANTEN|LAMPUNG|SUMATERA|\d{5})|\s*$)/i', $addr, $m)) {
            $kecamatan = self::cleanKecamatan($m[1]);
        }
        if (!$kecamatan && preg_match('/KECLOSARANG/i', $addr)) {
            $kecamatan = 'LOSARANG';
        }

        // If Pesurungan Lor (Kota Tegal), kecamatan is Margadana
        if (!$kecamatan && str_contains($addr, 'PESURUNGAN LOR')) {
            $kecamatan = 'MARGADANA';
        }

        $cleanProvince = self::normalizeProvince($province, $addr, $city);
        $cleanKabupaten = self::normalizeKabupatenKota($city, $addr, $kecamatan);

        return [
            'province' => $cleanProvince,
            'kabupaten' => $cleanKabupaten,
            'kecamatan' => $kecamatan,
            'desa' => $desa,
        ];
    }
}
