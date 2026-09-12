<?php

use App\Models\Attendance;
use App\Models\WorkSchedule;
use App\Services\OvertimeCalculator;
use Carbon\Carbon;

it('caps weekday overtime at the remaining weekly limit', function () {
    $calculator = new OvertimeCalculator();

    $attendance = new Attendance([
        'employee_id' => 1,
    ]);
    $attendance->id = 10;
    $attendance->exists = true;

    $schedule = new WorkSchedule();
    $schedule->end_time = Carbon::create(2026, 7, 20, 17, 0, 0);
    $schedule->overtime_threshold = 50;

    $minutes = $calculator->calculate(
        $attendance,
        Carbon::parse('2026-07-20'),
        Carbon::parse('2026-07-20 08:00:00'),
        Carbon::parse('2026-07-20 19:00:00'),
        $schedule,
        true,
        3560
    );

    expect($minutes)->toBe(40);
});

it('treats weekend work as overtime and still caps the weekly total', function () {
    $calculator = new OvertimeCalculator();

    $attendance = new Attendance([
        'employee_id' => 1,
    ]);
    $attendance->id = 11;
    $attendance->exists = true;

    $schedule = new WorkSchedule();
    $schedule->start_time = Carbon::create(2026, 7, 18, 8, 0, 0);
    $schedule->end_time = Carbon::create(2026, 7, 18, 17, 0, 0);
    $schedule->overtime_threshold = 50;

    $minutes = $calculator->calculate(
        $attendance,
        Carbon::parse('2026-07-18'),
        Carbon::parse('2026-07-18 09:00:00'),
        Carbon::parse('2026-07-18 18:00:00'),
        $schedule,
        true,
        3300
    );

    expect($minutes)->toBe(300);
});

it('returns zero for weekday overtime when the role is not eligible', function () {
    $calculator = new OvertimeCalculator();

    $attendance = new Attendance([
        'employee_id' => 1,
    ]);
    $attendance->id = 12;
    $attendance->exists = true;

    $schedule = new WorkSchedule();
    $schedule->end_time = Carbon::create(2026, 7, 20, 17, 0, 0);
    $schedule->overtime_threshold = 50;

    $minutes = $calculator->calculate(
        $attendance,
        Carbon::parse('2026-07-20'),
        Carbon::parse('2026-07-20 08:00:00'),
        Carbon::parse('2026-07-20 19:00:00'),
        $schedule,
        false,
        0
    );

    expect($minutes)->toBe(0);
});

it('keeps overtime duration as actual minutes', function () {
    $calculator = new OvertimeCalculator();

    $attendance = new Attendance([
        'employee_id' => 1,
    ]);
    $attendance->id = 13;
    $attendance->exists = true;

    $schedule = new WorkSchedule();
    $schedule->end_time = Carbon::create(2026, 7, 18, 17, 0, 0);
    $schedule->overtime_threshold = 50;

    $minutes = $calculator->calculate(
        $attendance,
        Carbon::parse('2026-07-18'),
        Carbon::parse('2026-07-18 08:00:00'),
        Carbon::parse('2026-07-18 10:10:00'),
        $schedule,
        true,
        0
    );

    expect($minutes)->toBe(130);
});

it('maps the 40-minute threshold to OT1 through OT8', function () {
    $calculator = new OvertimeCalculator();

    expect($calculator->categoryForMinutes(39))->toBeNull()
        ->and($calculator->categoryForMinutes(40))->toBe('OT1')
        ->and($calculator->categoryForMinutes(99))->toBe('OT1')
        ->and($calculator->categoryForMinutes(100))->toBe('OT2')
        ->and($calculator->categoryForMinutes(460))->toBe('OT8')
        ->and($calculator->categoryForMinutes(1000))->toBe('OT8');
});

it('uses the schedule threshold for overtime categories', function () {
    $calculator = new OvertimeCalculator();
    $schedule = new WorkSchedule();
    $schedule->overtime_threshold = 50;

    expect($calculator->categoryForMinutes(49, $schedule))->toBeNull()
        ->and($calculator->categoryForMinutes(50, $schedule))->toBe('OT1');
});

it('uses check-in to check-out on weekends', function () {
    $calculator = new OvertimeCalculator();
    $schedule = new WorkSchedule();
    $schedule->end_time = Carbon::create(2026, 7, 18, 17, 0, 0);

    $minutes = $calculator->calculate(
        new Attendance(),
        Carbon::parse('2026-07-18'),
        Carbon::parse('2026-07-18 08:00:00'),
        Carbon::parse('2026-07-18 17:00:00'),
        $schedule,
        true,
        0
    );

    expect($minutes)->toBe(480);
});

it('starts weekend overtime at schedule start when check-in is early', function () {
    $calculator = new OvertimeCalculator();
    $schedule = new WorkSchedule();
    $schedule->start_time = Carbon::create(2026, 7, 18, 8, 0, 0);

    $minutes = $calculator->calculate(
        new Attendance(),
        Carbon::parse('2026-07-18'),
        Carbon::parse('2026-07-18 07:00:00'),
        Carbon::parse('2026-07-18 17:00:00'),
        $schedule,
        true,
        0
    );

    expect($minutes)->toBe(480);
});

it('starts weekend overtime at actual check-in after schedule start', function () {
    $calculator = new OvertimeCalculator();
    $schedule = new WorkSchedule();
    $schedule->start_time = Carbon::create(2026, 7, 18, 8, 0, 0);

    $minutes = $calculator->calculate(
        new Attendance(),
        Carbon::parse('2026-07-18'),
        Carbon::parse('2026-07-18 09:00:00'),
        Carbon::parse('2026-07-18 17:00:00'),
        $schedule,
        true,
        0
    );

    expect($minutes)->toBe(420);
});

it('deducts only weekend break time that overlaps attendance', function () {
    $calculator = new OvertimeCalculator();
    $schedule = new WorkSchedule();
    $schedule->overtime_threshold = 40;

    $minutes = $calculator->calculate(
        new Attendance(),
        Carbon::parse('2026-07-18'),
        Carbon::parse('2026-07-18 11:30:00'),
        Carbon::parse('2026-07-18 13:30:00'),
        $schedule,
        true,
        0
    );

    expect($minutes)->toBe(60);
});

it('does not count early weekday arrival as overtime', function () {
    $calculator = new OvertimeCalculator();
    $schedule = new WorkSchedule();
    $schedule->start_time = Carbon::create(2026, 7, 20, 8, 0, 0);
    $schedule->end_time = Carbon::create(2026, 7, 20, 17, 0, 0);
    $schedule->overtime_threshold = 40;

    $minutes = $calculator->calculate(
        new Attendance(),
        Carbon::parse('2026-07-20'),
        Carbon::parse('2026-07-20 07:00:00'),
        Carbon::parse('2026-07-20 17:30:00'),
        $schedule,
        true,
        0
    );

    expect($minutes)->toBe(0);
});

it('uses scheduled end to check-out on weekdays', function () {
    $calculator = new OvertimeCalculator();
    $schedule = new WorkSchedule();
    $schedule->end_time = Carbon::create(2026, 7, 20, 16, 30, 0);

    $minutes = $calculator->calculate(
        new Attendance(),
        Carbon::parse('2026-07-20'),
        Carbon::parse('2026-07-20 08:00:00'),
        Carbon::parse('2026-07-20 17:10:00'),
        $schedule,
        true,
        0
    );

    expect($minutes)->toBe(40);
});