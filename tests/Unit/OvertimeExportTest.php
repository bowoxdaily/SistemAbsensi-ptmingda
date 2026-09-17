<?php

use App\Exports\OvertimeExport;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

it('builds overtime export rows grouped by employee with subtotals', function () {
    $employee = new Employee();
    $employee->id = 1;
    $employee->employee_code = 'EMP001';
    $employee->name = 'Budi Santoso';

    $att1 = new Attendance();
    $att1->employee_id = 1;
    $att1->setRelation('employee', $employee);
    $att1->attendance_date = Carbon::parse('2026-09-01');
    $att1->check_out = '18:15:00';
    $att1->overtime_minutes = 75;
    $att1->overtime_category = 'OT1';

    $att2 = new Attendance();
    $att2->employee_id = 1;
    $att2->setRelation('employee', $employee);
    $att2->attendance_date = Carbon::parse('2026-09-05');
    $att2->check_out = '19:40:00';
    $att2->overtime_minutes = 160;
    $att2->overtime_category = 'OT2';

    // Set static data BEFORE constructing — constructor calls buildRows() immediately
    TestableOvertimeExport::$testData = collect([$att1, $att2]);
    $export = new TestableOvertimeExport('2026-09-01', '2026-09-30');

    $rows = $export->array();

    // 2 data rows + 1 subtotal + 1 grand total = 4
    expect($rows)->toHaveCount(4);

    // First data row
    expect($rows[0][0])->toBe(1);
    expect($rows[0][1])->toBe('EMP001');
    expect($rows[0][2])->toBe('Budi Santoso');
    expect($rows[0][7])->toBe(1); // OT1 = 1 jam
    expect($rows[0][8])->toBe(60);

    // Second data row
    expect($rows[1][7])->toBe(2); // OT2 = 2 jam
    expect($rows[1][8])->toBe(120);

    // Subtotal row
    expect($rows[2][2])->toBe('SUBTOTAL BUDI SANTOSO');
    expect($rows[2][7])->toBe(3); // 1+2
    expect($rows[2][8])->toBe(180); // 60+120

    // Grand total row
    expect($rows[3][2])->toBe('GRAND TOTAL');
    expect($rows[3][7])->toBe(3);
    expect($rows[3][8])->toBe(180);
});

it('returns empty array when no data', function () {
    TestableOvertimeExport::$testData = collect([]);
    $export = new TestableOvertimeExport('2026-09-01', '2026-09-30');
    expect($export->array())->toBe([]);
});

it('headings match expected columns', function () {
    TestableOvertimeExport::$testData = collect([]);
    $export = new TestableOvertimeExport('2026-09-01', '2026-09-30');
    expect($export->headings())->toHaveCount(9);
    expect($export->headings()[0])->toBe('No');
    expect($export->headings()[8])->toBe('Lembur Diakui (Menit)');
});

// Helper class — defined once, static data set before each construct
class TestableOvertimeExport extends OvertimeExport
{
    public static $testData;
    protected function queryData() { return static::$testData; }
}


