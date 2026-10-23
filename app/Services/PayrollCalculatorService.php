<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

/**
 * PayrollCalculatorService
 *
 * Menghitung komponen gaji otomatis mengikuti format Excel payroll PT Mingda.
 *
 * Rumus utama:
 *   Total Gaji          = Upah Pokok + Tunjangan Jabatan + Tunjangan Bonus
 *   Gaji Per Jam        = (Upah Pokok + Tunjangan Jabatan) / 173
 *   Gaji Per Hari       = Total Gaji / 21
 *   Tunjangan Makan     = Hadir Weekday (Senin-Jumat) x Rp10.000
 *   Lembur 150%         = Jam ke-1 x 1.5 x Gaji Per Jam
 *   Lembur 200%         = Jam ke-2 s/d ke-8 & hari libur x 2 x Gaji Per Jam
 *   BPJS Perusahaan     = (Pokok+TjJab) x (4%+0.89%+0.30%+3.70%+2%)
 *   BPJS Karyawan       = (Pokok+TjJab) x (1%+2%+1%)
 *   Potongan Alpha      = Alpha (hari) x Gaji Per Hari
 *   Potongan Izin       = Izin (hari) x Gaji Per Hari
 *   Potongan Terlambat  = Menit terlambat x Gaji Per Hari / 8 / 60
 */
class PayrollCalculatorService
{
    public const HOUR_DIVISOR = 173;
    public const DAY_DIVISOR = 21;
    public const MEAL_ALLOWANCE_PER_DAY = 10000;
    public const HOURS_PER_DAY = 8;

    public const COMPANY_BPJS_RATES = [
        'kesehatan' => 0.04,
        'jkk'       => 0.0089,
        'jkm'       => 0.0030,
        'jht'       => 0.0370,
        'jp'        => 0.0200,
    ];

    public const EMPLOYEE_BPJS_RATES = [
        'kesehatan' => 0.01,
        'jht'       => 0.02,
        'jp'        => 0.01,
    ];

    public const OVERTIME_FIRST_HOUR_RATE = 1.5;
    public const OVERTIME_SUBSEQUENT_HOUR_RATE = 2.0;

    public function calculate(Employee $employee, string $periodMonth, array $overrides = []): array
    {
        $start = Carbon::parse($periodMonth . '-01')->startOfMonth();
        $end = Carbon::parse($periodMonth . '-01')->endOfMonth();
        $joinDate = $employee->join_date ? Carbon::parse($employee->join_date)->startOfDay() : null;
        $payrollStart = $joinDate && $joinDate->greaterThan($start) ? $joinDate->copy() : $start->copy();
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$payrollStart->toDateString(), $end->toDateString()])->get();

        $present = $attendances->whereIn('status', ['hadir', 'terlambat'])->count();
        $weekdayPresent = $attendances
            ->filter(fn ($attendance) => in_array($attendance->status, ['hadir', 'terlambat'], true))
            ->filter(fn ($attendance) => Carbon::parse($attendance->attendance_date)->isWeekday())
            ->count();
        $lateDays = $attendances->where('status', 'terlambat')->count();
        $sick = $attendances->where('status', 'sakit')->count();
        $alpha = $attendances->where('status', 'alpha')->count();
        $leave = $attendances->whereIn('status', ['cuti', 'cuti_tahunan'])->count();
        $permission = $attendances->where('status', 'izin')->count();
        $lateMinutes = (int) $attendances->sum('late_minutes');
        [$minutes150, $minutes200] = $this->splitOvertimeMinutes($attendances);

        $fullBasic = (float) ($overrides['basic_salary'] ?? $employee->salary_base ?? 0);
        $basic = $this->proratedBasicSalary($fullBasic, $payrollStart, $end);
        $position = (float) ($overrides['allowance_position'] ?? $employee->allowance_position ?? 0);
        $bonus = (float) ($overrides['bonus'] ?? $employee->bonus ?? 0);
        $totalBase = $basic + $position + $bonus;
        $hourly = ($basic + $position) / self::HOUR_DIVISOR;
        $daily = $totalBase / self::DAY_DIVISOR;
        $meal = $weekdayPresent * self::MEAL_ALLOWANCE_PER_DAY;
        $pay150 = ($minutes150 / 60) * self::OVERTIME_FIRST_HOUR_RATE * $hourly;
        $pay200 = ($minutes200 / 60) * self::OVERTIME_SUBSEQUENT_HOUR_RATE * $hourly;
        $overtime = $pay150 + $pay200;

        $bpjsBase = $basic + $position;
        $companyBpjs = array_sum(self::COMPANY_BPJS_RATES) * $bpjsBase;
        $employeeBpjs = array_sum(self::EMPLOYEE_BPJS_RATES) * $bpjsBase;
        $permissionHours = (int) ($overrides['permission_hours'] ?? 0);
        $noWorkNoPayDays = (float) ($overrides['no_work_no_pay_days'] ?? 0);

        $deductionAbsent = $alpha * $daily;
        $deductionPermission = $permission * $daily + ($permissionHours * $hourly / self::HOURS_PER_DAY);
        $deductionLate = ($lateMinutes / 60 / self::HOURS_PER_DAY) * $daily;
        $deductionNoWork = $noWorkNoPayDays * $daily;
        $deductionBpjs = (float) ($overrides['deduction_bpjs'] ?? $employeeBpjs);
        $deductionTax = (float) ($overrides['deduction_tax'] ?? 0);
        $deductionLoan = (float) ($overrides['deduction_loan'] ?? 0);
        $deductionSick = (float) ($overrides['deduction_sick_leave'] ?? 0);
        $deductionUnion = $employee->serikat === 'Serikat GARTEKS'
            ? (float) ($overrides['deduction_union'] ?? $employee->union_deduction ?? 0)
            : 0; // Non Serikat tidak mendapat potongan iuran serikat.
        $deductionOthers = (float) ($overrides['deduction_others'] ?? 0);
        $attendanceDeduction = $deductionAbsent + $deductionPermission + $deductionLate + $deductionNoWork;
        $earnings = $basic + $position + $bonus + $meal + $overtime;
        $deductions = $attendanceDeduction + $deductionBpjs + $deductionTax + $deductionLoan + $deductionSick + $deductionUnion + $deductionOthers;

        return [
            'employee_id' => $employee->id, 'period_month' => $periodMonth,
            'basic_salary' => round($basic, 2), 'allowance_position' => round($position, 2), 'bonus' => round($bonus, 2),
            'allowance_meal' => round($meal, 2), 'meal_allowance' => round($meal, 2), 'overtime_pay' => round($overtime, 2),
            'total_earnings' => round($earnings, 2), 'hourly_rate' => round($hourly, 4), 'daily_rate' => round($daily, 4),
            'overtime_150_minutes' => $minutes150, 'overtime_150_pay' => round($pay150, 2),
            'overtime_200_minutes' => $minutes200, 'overtime_200_pay' => round($pay200, 2),
            'company_bpjs_total' => round($companyBpjs, 2),
            'deduction_absent' => round($deductionAbsent, 2), 'deduction_permission' => round($deductionPermission, 2),
            'deduction_late' => round($deductionLate, 2), 'deduction_bpjs' => round($deductionBpjs, 2),
            'deduction_tax' => round($deductionTax, 2), 'deduction_loan' => round($deductionLoan, 2),
            'deduction_sick_leave' => round($deductionSick, 2), 'deduction_union' => round($deductionUnion, 2),
            'deduction_others' => round($deductionOthers, 2), 'total_deductions' => round($deductions, 2),
            'net_salary' => round($earnings - $deductions, 2),
            'total_days_present' => $present, 'total_days_late' => $lateDays, 'total_days_absent' => $alpha,
            'total_days_leave' => $leave + $sick + $permission, 'days_leave' => $leave, 'days_sick' => $sick,
            'days_permission' => $permission, 'permission_hours' => $permissionHours, 'late_minutes_total' => $lateMinutes,
        ];
    }

    private function proratedBasicSalary(float $fullBasic, Carbon $payrollStart, Carbon $end): float
    {
        $monthStart = $end->copy()->startOfMonth();
        if ($payrollStart->lessThanOrEqualTo($monthStart)) {
            return $fullBasic;
        }

        $totalWorkingDays = $this->countWeekdays($monthStart, $end);
        $eligibleWorkingDays = $this->countWeekdays($payrollStart, $end);

        return $totalWorkingDays > 0
            ? $fullBasic * $eligibleWorkingDays / $totalWorkingDays
            : 0.0;
    }

    private function countWeekdays(Carbon $start, Carbon $end): int
    {
        $days = 0;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($date->isWeekday()) {
                $days++;
            }
        }
        return $days;
    }

    private function splitOvertimeMinutes($attendances): array
    {
        $minutes150 = 0; $minutes200 = 0;
        foreach ($attendances->groupBy(fn ($a) => (string) $a->attendance_date) as $date => $items) {
            $minutes = (int) $items->sum(fn ($a) => $a->effective_overtime_minutes);
            if ($minutes <= 0) continue;
            if (Carbon::parse($date)->isWeekend()) {
                $minutes200 += $minutes;
                continue;
            }
            $first = min($minutes, 60);
            $minutes150 += $first;
            $minutes200 += min(max($minutes - $first, 0), 7 * 60);
        }
        return [$minutes150, $minutes200];
    }
}