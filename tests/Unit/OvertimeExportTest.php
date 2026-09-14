<?php

use App\Exports\OvertimeExport;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

it('maps overtime export row correctly with recognized hours and minutes based on category', function () {
    $export = new OvertimeExport('2026-09-01', '2026-09-30');

    $employee = new Employee();
    $employee->employee_code = 'EMP001';
    $employee->name = 'Budi Santoso';

    $attendance = new Attendance();
    $attendance->setRelation('employee', $employee);
    $attendance->attendance_date = Carbon::parse('2026-09-14');
    $attendance->check_out = '18:15:00';
    $attendance->overtime_minutes = 75;
    $attendance->overtime_category = 'OT1';

    $row = $export->map($attendance);

    expect($row)->toBe([
        1,
        'EMP001',
        'Budi Santoso',
        '14-09-2026',
        '18:15',
        '1:15',
        'OT1',
        1,
        60,
    ]);
});

it('maps OT2 correctly with 2 hours and 120 minutes', function () {
    $export = new OvertimeExport('2026-09-01', '2026-09-30');

    $employee = new Employee();
    $employee->employee_code = 'EMP002';
    $employee->name = 'Siti Aminah';

    $attendance = new Attendance();
    $attendance->setRelation('employee', $employee);
    $attendance->attendance_date = Carbon::parse('2026-09-14');
    $attendance->check_out = '19:40:00';
    $attendance->overtime_minutes = 160;
    $attendance->overtime_category = 'OT2';

    $row = $export->map($attendance);

    expect($row)->toBe([
        1,
        'EMP002',
        'Siti Aminah',
        '14-09-2026',
        '19:40',
        '2:40',
        'OT2',
        2,
        120,
    ]);
});

it('filters by employee_id when building query', function () {
    $export = new OvertimeExport('2026-09-01', '2026-09-30', null, null, 42);
    $sql = $export->query()->toSql();
    $bindings = $export->query()->getBindings();

    expect($sql)->toContain('employee_id')
        ->and($bindings)->toContain(42);
});


