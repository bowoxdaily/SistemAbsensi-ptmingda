<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

$app = new Illuminate\Foundation\Application(getcwd());
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Karyawans;

$total = Karyawans::count();
$withGeo = Karyawans::whereNotNull('kabupaten')->count();

echo "Total Karyawan: $total\n";
echo "Dengan data geografis: $withGeo\n";
echo "\nSample data:\n";
echo "─────────────────────────────────────────────\n";

$samples = Karyawans::whereNotNull('kabupaten')->limit(5)->get();
foreach ($samples as $emp) {
    echo "Nama: {$emp->name}\n";
    echo "  Desa: {$emp->desa}\n";
    echo "  Kecamatan: {$emp->kecamatan}\n";
    echo "  Kabupaten: {$emp->kabupaten}\n";
    echo "  Province: {$emp->province}\n";
    echo "\n";
}
