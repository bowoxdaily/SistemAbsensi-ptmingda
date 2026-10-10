<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\PayrollCalculatorService;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

it('returns 0 effective overtime minutes when status is pending', function () {
    $att = new Attendance();
    $att->overtime_minutes = 120;
    $att->overtime_approval_status = 'pending';
    $att->approved_overtime_minutes = 0;

    expect($att->effective_overtime_minutes)->toBe(0);
});

it('returns 0 effective overtime minutes when status is rejected', function () {
    $att = new Attendance();
    $att->overtime_minutes = 120;
    $att->overtime_approval_status = 'rejected';
    $att->approved_overtime_minutes = 0;

    expect($att->effective_overtime_minutes)->toBe(0);
});

it('returns machine overtime minutes when approved but no manual approved_overtime_minutes set', function () {
    $att = new Attendance();
    $att->overtime_minutes = 90;
    $att->overtime_approval_status = 'approved';
    $att->approved_overtime_minutes = 0;

    expect($att->effective_overtime_minutes)->toBe(90);
});

it('returns approved_overtime_minutes when status is approved and value is set', function () {
    $att = new Attendance();
    $att->overtime_minutes = 60;
    $att->overtime_approval_status = 'approved';
    $att->approved_overtime_minutes = 120;

    expect($att->effective_overtime_minutes)->toBe(120);
});

it('calculates payroll overtime pay using approved overtime only', function () {
    $employee = new Employee();
    $employee->id = 999;
    $employee->salary_base = 3460000;

    $attApproved = new Attendance();
    $attApproved->employee_id = 999;
    $attApproved->setRelation('employee', $employee);
    $attApproved->attendance_date = Carbon::parse('2026-09-01'); // Tuesday (weekday)
    $attApproved->status = 'hadir';
    $attApproved->overtime_minutes = 120;
    $attApproved->approved_overtime_minutes = 120;
    $attApproved->overtime_approval_status = 'approved';

    $attPending = new Attendance();
    $attPending->employee_id = 999;
    $attPending->setRelation('employee', $employee);
    $attPending->attendance_date = Carbon::parse('2026-09-02'); // Wednesday
    $attPending->status = 'hadir';
    $attPending->overtime_minutes = 120;
    $attPending->approved_overtime_minutes = 0;
    $attPending->overtime_approval_status = 'pending';

    $attendances = collect([$attApproved, $attPending]);

    // Hourly rate = 3460000 / 173 = 20000
    // 120 mins approved OT on weekday = 60 mins @ 1.5x (30000) + 60 mins @ 2.0x (40000) = 70000 total OT pay
    $service = new PayrollCalculatorService();
    
    // Test helper to run split over collection
    $refMethod = new ReflectionMethod(PayrollCalculatorService::class, 'splitOvertimeMinutes');
    $refMethod->setAccessible(true);
    [$m150, $m200] = $refMethod->invoke($service, $attendances);

    expect($m150)->toBe(60);
    expect($m200)->toBe(60);
});
