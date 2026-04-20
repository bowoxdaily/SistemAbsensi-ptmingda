<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

$app = new Illuminate\Foundation\Application(getcwd());
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test the parseAddress method
use App\Http\Controllers\Admin\KaryawanController;

$controller = new KaryawanController();

// Test Case 1: Standar Indramayu format
echo "TEST 1: Standar Indramayu Format\n";
echo "─────────────────────────────────────────────────────────\n";
$result = $controller->extractGeographicData(
    address: "DESA LOSARANG RT 002 RW 001 KEL/DESA LOSARANG KEC LOSARANG INDRAMAYU JAWA BARAT 45253",
    city: "INDRAMAYU",
    province: "JAWA BARAT"
);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test Case 2: Bandung format with jalan
echo "TEST 2: Bandung Format\n";
echo "─────────────────────────────────────────────────────────\n";
$result = $controller->extractGeographicData(
    address: "JL. HALMAHERA III NO.567 RT 006 RW 008 KEL/DESA LIMBANGAN WETAN KEC BREBES JAWA TENGAH 52211",
    city: "BREBES",
    province: "JAWA TENGAH"
);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test Case 3: Data lengkap Indramayu
echo "TEST 3: Data Karyawan RIZQI MAULANA AZKA\n";
echo "─────────────────────────────────────────────────────────\n";
$result = $controller->extractGeographicData(
    address: "BLOK PLASAH RT 002 RW 002 KEL/DESA RANCAHAN KEC GABUSWETAN INDRAMAYU JAWA BARAT 45263",
    city: "INDRAMAYU",
    province: "JAWA BARAT"
);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Test Case 4: Minimal format
echo "TEST 4: Minimal Format (SDA)\n";
echo "─────────────────────────────────────────────────────────\n";
$result = $controller->extractGeographicData(
    address: "SDA",
    city: "INDRAMAYU",
    province: "JAWA BARAT"
);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "✓ All tests completed!\n";
