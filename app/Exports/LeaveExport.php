<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Karyawans;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeaveExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly int $year)
    {
    }

    public function collection(): Collection
    {
        return Karyawans::query()
            ->with('position')
            ->where('status', 'active')
            ->whereNotNull('join_date')
            ->orderBy('employee_code')
            ->get()
            ->map(fn (Karyawans $employee) => $this->buildRow($employee))
            ->filter(fn (object $row) => $row->period !== '-')
            ->values();
    }

    public function headings(): array
    {
        return [
            'Kode Karyawan',
            'Nama Karyawan',
            'Jabatan',
            'Hak Cuti sesuai Jabatan',
            'Periode',
            'Total Cuti Terpakai (Hari)',
            'Tanggal Cuti Terpakai',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee_code,
            $row->name,
            $row->position,
            $row->quota,
            $row->period,
            $row->used_days,
            $row->used_dates,
        ];
    }

    private function buildRow(Karyawans $employee): object
    {
        $joinDate = Carbon::parse($employee->join_date);
        $firstEntitlementDate = $joinDate->copy()->addYear();
        $periodStartYear = $this->year;

        if ($firstEntitlementDate->year > $periodStartYear) {
            return (object) [
                'employee_code' => $employee->employee_code,
                'name' => $employee->name,
                'position' => $employee->position?->name ?? '-',
                'quota' => 0,
                'period' => '-',
                'used_days' => 0,
                'used_dates' => '-',
            ];
        }

        $leaveStart = $firstEntitlementDate->copy()->addYears($periodStartYear - $firstEntitlementDate->year);
        $leaveEnd = $leaveStart->copy()->addYear()->subDay();
        $quota = $this->quotaFor($employee->position?->name);

        $attendanceQuery = Attendance::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'cuti_tahunan');

        $debtDates = (clone $attendanceQuery)
            ->whereBetween('attendance_date', [$joinDate->toDateString(), $firstEntitlementDate->copy()->subDay()->toDateString()])
            ->pluck('attendance_date');

        $usedDates = (clone $attendanceQuery)
            ->whereBetween('attendance_date', [$leaveStart->toDateString(), $leaveEnd->toDateString()])
            ->pluck('attendance_date');

        $leaveDates = $debtDates
            ->merge($usedDates)
            ->map(fn ($date) => Carbon::parse($date))
            ->sort()
            ->map(fn (Carbon $date) => $date->format('d-m-Y'))
            ->values();

        return (object) [
            'employee_code' => $employee->employee_code,
            'name' => $employee->name,
            'position' => $employee->position?->name ?? '-',
            'quota' => $quota,
            'period' => $leaveStart->format('d-m-Y') . ' s/d ' . $leaveEnd->format('d-m-Y'),
            'used_days' => $leaveDates->count(),
            'used_dates' => $leaveDates->isNotEmpty() ? $leaveDates->implode(', ') : '-',
        ];
    }

    private function quotaFor(?string $position): int
    {
        $positionName = mb_strtoupper($position ?? '');

        return str_contains($positionName, 'ASS MEN') || str_contains($positionName, 'ASSISTANT MANAGER')
            ? 16
            : (str_contains($positionName, 'KABAG') ? 14 : (str_contains($positionName, 'MANAGER') ? 18 : 12));
    }
}
