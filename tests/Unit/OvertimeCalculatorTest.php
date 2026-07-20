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