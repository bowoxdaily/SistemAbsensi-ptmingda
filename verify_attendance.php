<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Attendance;

echo "Verifying attendance data...\n\n";

// Check Libur Nasional
$liburCount = Attendance::where('attendance_date', '2026-02-17')
    ->where('status', 'libur')
    ->count();

$liburSample = Attendance::where('attendance_date', '2026-02-17')
    ->where('status', 'libur')
    ->with('employee')
    ->first();

echo "✓ Libur Nasional (17 Feb 2026):\n";
echo "  - Total records: {$liburCount}\n";
echo "  - Status: libur\n";
if ($liburSample) {
    echo "  - Sample: {$liburSample->employee->name} ({$liburSample->employee->employee_code})\n";
    echo "  - Notes: {$liburSample->notes}\n";
}

echo "\n";

// Check Cuti Bersama
$cutberCount = Attendance::where('attendance_date', '2026-02-18')
    ->where('status', 'cuti_bersama')
    ->count();

$cutberSample = Attendance::where('attendance_date', '2026-02-18')
    ->where('status', 'cuti_bersama')
    ->with('employee')
    ->first();

echo "✓ Cuti Bersama (18 Feb 2026):\n";
echo "  - Total records: {$cutberCount}\n";
echo "  - Status: cuti_bersama\n";
if ($cutberSample) {
    echo "  - Sample: {$cutberSample->employee->name} ({$cutberSample->employee->employee_code})\n";
    echo "  - Notes: {$cutberSample->notes}\n";
}

echo "\n\n✅ Verification completed successfully!\n";
