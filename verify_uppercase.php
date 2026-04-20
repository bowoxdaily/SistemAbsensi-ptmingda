<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Karyawans;

$aktif = Karyawans::where('status', 'active')->get();

echo "═════════════════════════════════════════════════════\n";
echo "GEOGRAPHIC DATA VERIFICATION\n";
echo "═════════════════════════════════════════════════════\n\n";

echo "Test 1: Count INDRAMAYU variations\n";
$indra = $aktif->where('kabupaten', 'INDRAMAYU')->count();
echo "Kabupaten = 'INDRAMAYU': " . $indra . " employees\n";

echo "\nTest 2: List all unique kabupaten (should be ALL UPPERCASE now)\n";
$uniq_kab = $aktif->unique('kabupaten')->pluck('kabupaten')->sort()->values();
foreach ($uniq_kab as $k => $v) {
    echo ($k+1) . ". " . ($v ?? '[NULL]') . "\n";
}

echo "\nTest 3: Check for mixed case (should be 0)\n";
$mixed = 0;
$mixed_list = [];
foreach ($aktif as $emp) {
    if ($emp->kabupaten && $emp->kabupaten !== strtoupper($emp->kabupaten)) {
        $mixed_list[] = "  {$emp->name} - {$emp->kabupaten}";
        $mixed++;
    }
}
if ($mixed > 0) {
    echo implode("\n", $mixed_list) . "\n";
}
echo "Total mixed case: " . $mixed . "\n";

echo "\nTest 4: Check kecamatan\n";
$kec_mixed = 0;
$kec_list = [];
foreach ($aktif as $emp) {
    if ($emp->kecamatan && $emp->kecamatan !== strtoupper($emp->kecamatan)) {
        $kec_list[] = "  {$emp->name} - {$emp->kecamatan}";
        $kec_mixed++;
    }
}
echo "Total kecamatan mixed case: " . $kec_mixed . "\n";
if ($kec_mixed > 0) {
    echo implode("\n", $kec_list) . "\n";
}

echo "\n═════════════════════════════════════════════════════\n";
echo "STATUS: " . ($mixed === 0 && $kec_mixed === 0 ? "✓ ALL DATA NORMALIZED" : "✗ SOME DATA NEEDS NORMALIZATION") . "\n";
echo "═════════════════════════════════════════════════════\n";
