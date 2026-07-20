<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\WorkSchedule;
use Carbon\Carbon;

class OvertimeCalculator
{
    private const WEEKLY_MAX_MINUTES = 3600;

    public function calculate(
        object $attendance,
        Carbon $attendanceDate,
        Carbon $checkInTime,
        Carbon $checkOutTime,
        WorkSchedule $schedule,
        bool $allowWeekdayOvertime,
        ?int $weeklyUsedMinutes = null
    ): int {
        $rawMinutes = $this->calculateRawMinutes(
            $attendanceDate,
            $checkInTime,
            $checkOutTime,
            $schedule,
            $allowWeekdayOvertime
        );

        if ($rawMinutes <= 0) {
            return 0;
        }

        $usedMinutes = $weeklyUsedMinutes ?? $this->getWeeklyUsedOvertimeMinutes($attendance);
        $remainingMinutes = max(0, self::WEEKLY_MAX_MINUTES - $usedMinutes);
        $countedMinutes = min($rawMinutes, $remainingMinutes);

        return $this->roundDownToHour($countedMinutes);
    }

    private function calculateRawMinutes(
        Carbon $attendanceDate,
        Carbon $checkInTime,
        Carbon $checkOutTime,
        WorkSchedule $schedule,
        bool $allowWeekdayOvertime
    ): int {
        if ($checkOutTime->lessThanOrEqualTo($checkInTime)) {
            return 0;
        }

        if ($attendanceDate->isWeekend()) {
            return $checkInTime->diffInMinutes($checkOutTime);
        }

        if (!$allowWeekdayOvertime) {
            return 0;
        }

        [$endHour, $endMinute] = $this->extractTimeParts($schedule->end_time, 17, 0);
        $scheduledEndTime = $attendanceDate->copy()->setTime($endHour, $endMinute, 0);
        $overtimeThreshold = $schedule->overtime_threshold ?? 50;
        $thresholdTime = $scheduledEndTime->copy()->addMinutes($overtimeThreshold);

        if ($checkOutTime->greaterThan($thresholdTime)) {
            return $scheduledEndTime->diffInMinutes($checkOutTime);
        }

        return 0;
    }

    private function getWeeklyUsedOvertimeMinutes(object $attendance): int
    {
        $attendanceDate = Carbon::parse($attendance->attendance_date);
        $weekStart = $attendanceDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $attendanceDate->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $query = Attendance::query()
            ->where('employee_id', $attendance->employee_id)
            ->whereBetween('attendance_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->whereNotNull('check_out')
            ->whereIn('status', ['hadir', 'terlambat']);

        if ($attendance->exists) {
            $query->where('id', '!=', $attendance->id);
        }

        return (int) $query->sum('overtime_minutes');
    }

    private function extractTimeParts(mixed $timeValue, int $defaultHour, int $defaultMinute): array
    {
        if ($timeValue instanceof Carbon) {
            return [$timeValue->hour, $timeValue->minute];
        }

        preg_match('/(\d{1,2}):(\d{2})/', (string) $timeValue, $match);

        return [
            $match ? (int) $match[1] : $defaultHour,
            $match ? (int) $match[2] : $defaultMinute,
        ];
    }

    private function roundDownToHour(int $minutes): int
    {
        return intdiv(max(0, $minutes), 60) * 60;
    }
}