<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Leave;
use App\Models\Employee;
use App\Models\User;
use App\Services\WhatsAppService;
use Carbon\Carbon;

echo "=== TEST WHATSAPP LEAVE NOTIFICATION ===\n\n";

// Get test employee
$employee = Employee::where('id', 8)->first();

if (!$employee) {
    echo "❌ Employee ID 8 not found!\n";
    exit(1);
}

echo "Test Employee:\n";
echo "- ID: {$employee->id}\n";
echo "- Name: {$employee->name}\n";
echo "- Kode Karyawan: {$employee->employee_code}\n";
echo "- Phone: {$employee->phone}\n\n";

// Create dummy leave request
echo "Creating test leave request...\n";
$leave = Leave::create([
    'employee_id' => $employee->id,
    'leave_type' => 'cuti',
    'start_date' => Carbon::today()->addDays(7),
    'end_date' => Carbon::today()->addDays(9),
    'total_days' => 3,
    'reason' => 'Test pengajuan cuti untuk keperluan keluarga',
    'status' => 'pending',
]);

echo "✅ Leave request created (ID: {$leave->id})\n\n";

// Test 1: Send leave request notification to admin
echo "TEST 1: Send Leave Request Notification to Admin\n";
echo "---------------------------------------------------\n";
$whatsappService = new WhatsAppService();
$leave->load('employee');

$result = $whatsappService->sendLeaveRequestNotification($leave);
echo $result ? "✅ SUCCESS - Notification sent to admin\n\n" : "❌ FAILED - Could not send notification\n\n";

// Test 2: Approve and send notification
echo "TEST 2: Approve Leave and Send Notification to Employee\n";
echo "---------------------------------------------------\n";
$admin = User::where('role', 'admin')->first();
if ($admin) {
    $leave->update([
        'status' => 'approved',
        'approved_by' => $admin->id,
        'approved_at' => now(),
    ]);

    $leave->load(['employee', 'approver']);
    $result = $whatsappService->sendLeaveApprovedNotification($leave);
    echo $result ? "✅ SUCCESS - Approval notification sent to employee\n\n" : "❌ FAILED - Could not send notification\n\n";
} else {
    echo "⚠️  SKIP - No admin user found\n\n";
}

// Test 3: Reject and send notification
echo "TEST 3: Reject Leave and Send Notification to Employee\n";
echo "---------------------------------------------------\n";
$leave->update([
    'status' => 'rejected',
    'rejection_reason' => 'Maaf, pada tanggal tersebut sudah ada karyawan lain yang cuti. Mohon reschedule.',
]);

$leave->load(['employee', 'approver']);
$result = $whatsappService->sendLeaveRejectedNotification($leave);
echo $result ? "✅ SUCCESS - Rejection notification sent to employee\n\n" : "❌ FAILED - Could not send notification\n\n";

// Cleanup - delete test leave
echo "Cleaning up test data...\n";
$leave->delete();
echo "✅ Test leave deleted\n\n";

echo "=== TEST COMPLETED ===\n";
echo "\nNote: Check storage/logs/laravel.log for detailed logs\n";
