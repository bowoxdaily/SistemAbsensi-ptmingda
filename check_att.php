<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FINAL SYNC RESULTS ===\n\n";

// Summary
$totalLogs = DB::table('fingerspot_logs')->count();
$successLogs = DB::table('fingerspot_logs')->where('process_status', 'success')->count();
$failedLogs = DB::table('fingerspot_logs')->where('process_status', 'failed')->count();
$skippedLogs = DB::table('fingerspot_logs')->where('process_status', 'skipped')->count();
$totalAtt = DB::table('attendances')->count();
$withCheckout = DB::table('attendances')->whereNotNull('check_out')->count();

echo "Fingerspot Logs:\n";
echo "  Total: {$totalLogs}\n";
echo "  Success: {$successLogs}\n";
echo "  Failed: {$failedLogs}\n";
echo "  Skipped: {$skippedLogs}\n";
echo "\nAttendances:\n";
echo "  Total: {$totalAtt}\n";
echo "  With Check-out: {$withCheckout}\n";
echo "  Without Check-out: " . ($totalAtt - $withCheckout) . "\n";

// Sample attendance with check-out
echo "\n=== SAMPLE ATTENDANCE WITH CHECK-OUT ===\n";
$attsWithCheckout = DB::table('attendances')
    ->join('employees', 'attendances.employee_id', '=', 'employees.id')
    ->whereNotNull('attendances.check_out')
    ->select('employees.employee_code', 'attendances.attendance_date', 'attendances.check_in', 'attendances.check_out', 'attendances.status')
    ->limit(15)
    ->get();

foreach ($attsWithCheckout as $att) {
    echo "Code: {$att->employee_code} | Date: {$att->attendance_date} | In: {$att->check_in} | Out: {$att->check_out} | Status: {$att->status}\n";
}

// Check one specific employee with correct order now
echo "\n=== CHECK SPECIFIC EMPLOYEE (PIN 0003) ===\n";
$logs = DB::table('fingerspot_logs')
    ->where('pin', '0003')
    ->whereDate('scan_time', '2026-02-03')
    ->orderBy('scan_time')
    ->get(['scan_time', 'process_status', 'process_message']);

foreach ($logs as $log) {
    echo "  {$log->scan_time} | {$log->process_status}: {$log->process_message}\n";
}

$emp = DB::table('employees')
    ->where('employee_code', 'MIF-0003')
    ->first(['id']);

if ($emp) {
    $att = DB::table('attendances')
        ->where('employee_id', $emp->id)
        ->where('attendance_date', '2026-02-03')
        ->first(['check_in', 'check_out', 'status']);

    if ($att) {
        echo "  >> ATTENDANCE: In={$att->check_in} | Out=" . ($att->check_out ?? 'NULL') . " | Status={$att->status}\n";
    }
}
