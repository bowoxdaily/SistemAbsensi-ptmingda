<?php

require __DIR__ . '/vendor/autoload.php';

use Carbon\Carbon;

echo "=== Debug Check-In Error ===\n\n";

// Simulate the exact data from the frontend
$date = '2025-11-16';
$checkInTime = '08:30';

echo "Input Data:\n";
echo "  date: $date\n";
echo "  check_in_time: $checkInTime\n\n";

// Test 1: Parse date
echo "Test 1: Parse date\n";
try {
    $attendanceDate = Carbon::parse($date)->startOfDay();
    echo "  Parsed date: " . $attendanceDate->toDateTimeString() . "\n";
    $dateString = $attendanceDate->toDateString();
    echo "  Date string: $dateString\n";
    echo "  ✅ Success\n\n";
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Create check-in time
echo "Test 2: Create check-in time\n";
try {
    $dateString = '2025-11-16';
    $timeString = '08:30';
    $combined = $dateString . ' ' . $timeString;
    echo "  Combined string: '$combined'\n";
    echo "  String length: " . strlen($combined) . "\n";
    echo "  Expected format: 'Y-m-d H:i'\n";

    $checkInTimeObj = Carbon::createFromFormat('Y-m-d H:i', $combined);
    echo "  Result: " . $checkInTimeObj->toDateTimeString() . "\n";
    echo "  ✅ Success\n\n";
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Check if there's any hidden characters
echo "Test 3: Check for hidden characters\n";
$testDate = '2025-11-16';
$testTime = '08:30';
$testCombined = $testDate . ' ' . $testTime;

echo "  Date bytes: " . bin2hex($testDate) . "\n";
echo "  Time bytes: " . bin2hex($testTime) . "\n";
echo "  Combined bytes: " . bin2hex($testCombined) . "\n";
echo "  Combined: '$testCombined'\n\n";

// Test 4: Test with actual schedule parsing
echo "Test 4: Parse schedule start time\n";
try {
    $scheduleStartTime = '08:00:00';
    $startTime = substr($scheduleStartTime, 0, 5);
    echo "  Schedule start: $scheduleStartTime\n";
    echo "  Extracted: $startTime\n";

    $scheduledTime = Carbon::createFromFormat('Y-m-d H:i', '2025-11-16 ' . $startTime);
    echo "  Scheduled time: " . $scheduledTime->toDateTimeString() . "\n";
    echo "  ✅ Success\n\n";
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n\n";
}

echo "=== Debug Complete ===\n";
