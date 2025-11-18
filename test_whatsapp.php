<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\WhatsAppService;
use App\Models\Employee;
use App\Models\Attendance;

echo "=== Test WhatsApp Service ===\n\n";

// Test 1: Direct send
echo "Test 1: Send direct message\n";
$service = new WhatsAppService();
$result = $service->send('628995765460', 'Test dari sistem absensi');
echo "Result: " . ($result ? "✓ SUCCESS" : "✗ FAILED") . "\n\n";

// Test 2: Get employee and attendance
echo "Test 2: Get latest attendance\n";
$attendance = Attendance::with('employee')
    ->whereHas('employee', function ($q) {
        $q->where('id', 8);
    })
    ->latest()
    ->first();

if ($attendance) {
    echo "Attendance found:\n";
    echo "  Employee: " . ($attendance->employee->name ?? 'NULL') . "\n";
    echo "  Phone: " . ($attendance->employee->phone ?? 'NULL') . "\n";
    echo "  Check-in: " . ($attendance->check_in ?? 'NULL') . "\n";

    echo "\nTest 3: Send check-in notification\n";
    $result = $service->sendCheckinNotification($attendance);
    echo "Result: " . ($result ? "✓ SUCCESS" : "✗ FAILED") . "\n";
} else {
    echo "No attendance found\n";
}
