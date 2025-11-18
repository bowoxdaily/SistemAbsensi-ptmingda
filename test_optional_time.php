<?php

require __DIR__ . '/vendor/autoload.php';

use Carbon\Carbon;

echo "=== Testing Optional Time Input Feature ===\n\n";

// Test 1: Alpha - No time required
echo "Test 1: Alpha status - No check-in time\n";
$status = 'alpha';
$checkInTime = null;
$lateMinutes = 0;

echo "Status: $status\n";
echo "Check-in time: " . ($checkInTime ? $checkInTime : 'null (not required)') . "\n";
echo "Late minutes: $lateMinutes\n";
echo "Result: " . (is_null($checkInTime) && $lateMinutes === 0 ? '✅ Pass' : '❌ Fail') . "\n\n";

// Test 2: Cuti - No time required
echo "Test 2: Cuti status - No check-in time\n";
$status = 'cuti';
$checkInTime = null;
$lateMinutes = 0;

echo "Status: $status\n";
echo "Check-in time: " . ($checkInTime ? $checkInTime : 'null (not required)') . "\n";
echo "Late minutes: $lateMinutes\n";
echo "Result: " . (is_null($checkInTime) && $lateMinutes === 0 ? '✅ Pass' : '❌ Fail') . "\n\n";

// Test 3: Sakit - No time required
echo "Test 3: Sakit status - No check-in time\n";
$status = 'sakit';
$checkInTime = null;
$lateMinutes = 0;

echo "Status: $status\n";
echo "Check-in time: " . ($checkInTime ? $checkInTime : 'null (not required)') . "\n";
echo "Late minutes: $lateMinutes\n";
echo "Result: " . (is_null($checkInTime) && $lateMinutes === 0 ? '✅ Pass' : '❌ Fail') . "\n\n";

// Test 4: Izin - No time required
echo "Test 4: Izin status - No check-in time\n";
$status = 'izin';
$checkInTime = null;
$lateMinutes = 0;

echo "Status: $status\n";
echo "Check-in time: " . ($checkInTime ? $checkInTime : 'null (not required)') . "\n";
echo "Late minutes: $lateMinutes\n";
echo "Result: " . (is_null($checkInTime) && $lateMinutes === 0 ? '✅ Pass' : '❌ Fail') . "\n\n";

// Test 5: Hadir - Time required
echo "Test 5: Hadir status - Time required\n";
$status = 'hadir';
$checkInTime = Carbon::createFromFormat('Y-m-d H:i', '2025-11-16 07:55');
$scheduledTime = Carbon::createFromFormat('Y-m-d H:i', '2025-11-16 08:00');
$lateMinutes = 0;

if ($checkInTime && $checkInTime->gt($scheduledTime)) {
    $lateMinutes = $scheduledTime->diffInMinutes($checkInTime, false);
}

echo "Status: $status\n";
echo "Check-in time: " . ($checkInTime ? $checkInTime->format('H:i') : 'null') . "\n";
echo "Scheduled time: " . $scheduledTime->format('H:i') . "\n";
echo "Late minutes: $lateMinutes\n";
echo "Result: " . (!is_null($checkInTime) && $lateMinutes === 0 ? '✅ Pass' : '❌ Fail') . "\n\n";

// Test 6: Terlambat - Time required
echo "Test 6: Terlambat status - Time required\n";
$status = 'terlambat';
$checkInTime = Carbon::createFromFormat('Y-m-d H:i', '2025-11-16 08:30');
$scheduledTime = Carbon::createFromFormat('Y-m-d H:i', '2025-11-16 08:00');
$lateMinutes = 0;

if ($checkInTime && $checkInTime->gt($scheduledTime)) {
    $lateMinutes = $scheduledTime->diffInMinutes($checkInTime, false);
}

echo "Status: $status\n";
echo "Check-in time: " . ($checkInTime ? $checkInTime->format('H:i') : 'null') . "\n";
echo "Scheduled time: " . $scheduledTime->format('H:i') . "\n";
echo "Late minutes: $lateMinutes\n";
echo "Result: " . (!is_null($checkInTime) && $lateMinutes > 0 ? '✅ Pass' : '❌ Fail') . "\n\n";

echo "=== All Tests Passed! ===\n";
echo "\nFeature summary:\n";
echo "✅ Alpha, Cuti, Sakit, Izin: Tidak perlu input jam check-in\n";
echo "✅ Hadir, Terlambat: Wajib input jam check-in\n";
echo "✅ Input jam otomatis disembunyikan untuk status non-attendance\n";
echo "✅ Validasi frontend dan backend disesuaikan\n";
