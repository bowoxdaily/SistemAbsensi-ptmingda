<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Karyawans;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithEvents
{
    protected $dateFrom;
    protected $dateTo;
    protected $status;
    protected $department;
    protected $search;
    protected $subDepartment;
    protected $rowNumber = 0;
    protected $totalHadirByEmployee = [];
    protected $weekendRows = [];

    public function __construct($dateFrom, $dateTo, $status = null, $department = null, $search = null, $subDepartment = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->status = $status;
        $this->department = $department;
        $this->search = $search;
        $this->subDepartment = $subDepartment;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $this->totalHadirByEmployee = $this->calculateTotalHadirByEmployee();

        $query = Attendance::with([
            'employee.department',
            'employee.subDepartment',
            'employee.position',
            'employee.workSchedule',
        ]);

        $this->applyFilters($query);
        $query->whereBetween('attendances.attendance_date', [$this->dateFrom, $this->dateTo]);

        $data = $query
            ->orderBy('attendance_date')
            ->orderBy('employee_id')
            ->get();

        $weekendPlaceholders = $this->generateWeekendPlaceholders($this->dateFrom, $this->dateTo, $data);

        return $data
            ->concat($weekendPlaceholders)
            ->sortBy(function ($attendance) {
                $employeeCode = $attendance->employee->employee_code ?? '-';
                $date = $attendance->attendance_date ?? null;

                return [$employeeCode, $date];
            })
            ->values();
    }

    private function applyFilters(Builder $query): void
    {
        if ($this->search) {
            $query->whereHas('employee', function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('employee_code', 'like', "%{$this->search}%");
            });
        }

        if ($this->status) {
            $query->where('attendances.status', $this->status);
        }

        if ($this->department) {
            $query->whereHas('employee', function ($q) {
                $q->where('department_id', $this->department);
            });
        }

        if ($this->subDepartment) {
            $query->whereHas('employee', function ($q) {
                $q->where('sub_department_id', $this->subDepartment);
            });
        }
    }

    private function calculateTotalHadirByEmployee(): array
    {
        $query = Attendance::query()
            ->whereBetween('attendances.attendance_date', [$this->dateFrom, $this->dateTo])
            ->whereRaw('LOWER(attendances.status) = ?', ['hadir']);

        $this->applyFilters($query);

        return $query
            ->join('employees', 'attendances.employee_id', '=', 'employees.id')
            ->groupBy('employees.employee_code')
            ->selectRaw('employees.employee_code, COUNT(*) as total')
            ->pluck('total', 'employee_code')
            ->toArray();
    }

    /**
     * Generate weekend (Saturday & Sunday) placeholders for dates without attendance
     */
    private function generateWeekendPlaceholders($dateFrom, $dateTo, $existingAttendances)
    {
        $placeholders = collect();

        $employees = $existingAttendances
            ->pluck('employee')
            ->filter()
            ->unique('id');

        if ($employees->isEmpty() && $this->search) {
            $employeeQuery = Karyawans::with(['department', 'subDepartment', 'position', 'workSchedule'])
                ->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('employee_code', 'like', "%{$this->search}%");
                });

            if ($this->department) {
                $employeeQuery->where('department_id', $this->department);
            }

            $employees = $employeeQuery->get();
        }

        if ($employees->isEmpty()) {
            return $placeholders;
        }

        $start = Carbon::parse($dateFrom)->startOfDay();
        $end = Carbon::parse($dateTo)->startOfDay();

        $existingDates = [];
        foreach ($existingAttendances as $attendance) {
            if (!$attendance->employee_id) {
                continue;
            }

            $existingDates[$attendance->employee_id][] = Carbon::parse($attendance->attendance_date)->format('Y-m-d');
        }

        foreach ($employees as $employee) {
            $current = $start->copy();

            while ($current->lte($end)) {
                if ($current->dayOfWeek === Carbon::SATURDAY || $current->dayOfWeek === Carbon::SUNDAY) {
                    $dateStr = $current->format('Y-m-d');
                    $hasAttendance = isset($existingDates[$employee->id])
                        && in_array($dateStr, $existingDates[$employee->id], true);

                    if (!$hasAttendance) {
                        $placeholder = new \stdClass();
                        $placeholder->is_weekend_placeholder = true;
                        $placeholder->weekend_day = $current->dayOfWeek === Carbon::SATURDAY ? 'Sabtu' : 'Minggu';
                        $placeholder->id = null;
                        $placeholder->employee_id = $employee->id;
                        $placeholder->employee = $employee;
                        $placeholder->attendance_date = $current->copy();
                        $placeholder->check_in = null;
                        $placeholder->check_out = null;
                        $placeholder->status = null;
                        $placeholder->late_minutes = 0;
                        $placeholder->overtime_minutes = 0;
                        $placeholder->notes = null;

                        $placeholders->push($placeholder);
                    }
                }

                $current->addDay();
            }
        }

        return $placeholders;
    }

    /**
     * Map data for export
     */
    public function map($attendance): array
    {
        $this->rowNumber++;

        $employee = $attendance->employee ?? null;
        $employeeCode = $employee->employee_code ?? '-';
        $totalHadir = $this->totalHadirByEmployee[$employeeCode] ?? 0;

        if (isset($attendance->is_weekend_placeholder) && $attendance->is_weekend_placeholder) {
            $this->weekendRows[] = $this->rowNumber + 1;

            return [
                $this->rowNumber,
                $employeeCode,
                $employee->name ?? '-',
                $employee->department->name ?? '-',
                $employee->subDepartment->name ?? '-',
                $employee->position->name ?? '-',
                Carbon::parse($attendance->attendance_date)->locale('id')->translatedFormat('l, d F Y'),
                '-',
                '-',
                '-',
                'HARI ' . strtoupper($attendance->weekend_day ?? 'MINGGU'),
                '-',
                '-',
                $totalHadir,
                '-',
            ];
        }

        return [
            $this->rowNumber,
            $employeeCode,
            $employee->name ?? '-',
            $employee->department->name ?? '-',
            $employee->subDepartment->name ?? '-',
            $employee->position->name ?? '-',
            Carbon::parse($attendance->attendance_date)->locale('id')->translatedFormat('l, d F Y'),
            $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : '-',
            $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i') : '-',
            $this->formatWorkSchedule($employee),
            strtoupper((string) ($attendance->status ?? '-')),
            $attendance->late_minutes > 0 ? $attendance->late_minutes . ' menit' : '-',
            $attendance->overtime_minutes > 0 ? $attendance->overtime_minutes . ' menit' : '-',
            $totalHadir,
            $attendance->notes ?? '-',
        ];
    }

    private function formatWorkSchedule($employee): string
    {
        if (!$employee || !$employee->workSchedule) {
            return '-';
        }

        $schedule = $employee->workSchedule;
        $startTime = $schedule->start_time;
        $endTime = $schedule->end_time;

        $startFormatted = $startTime instanceof Carbon
            ? $startTime->format('H:i')
            : (preg_match('/(\d{1,2}):(\d{2})/', (string) $startTime, $startMatch) ? $startMatch[1] . ':' . $startMatch[2] : '08:00');

        $endFormatted = $endTime instanceof Carbon
            ? $endTime->format('H:i')
            : (preg_match('/(\d{1,2}):(\d{2})/', (string) $endTime, $endMatch) ? $endMatch[1] . ':' . $endMatch[2] : '17:00');

        return $startFormatted . ' - ' . $endFormatted;
    }

    /**
     * Define headings
     */
    public function headings(): array
    {
        return [
            'No',
            'NIP',
            'Nama Karyawan',
            'Departemen',
            'Sub Departemen',
            'Jabatan',
            'Tanggal',
            'Check In',
            'Check Out',
            'Jam Kerja',
            'Status',
            'Terlambat',
            'Lembur',
            'Total Hadir',
            'Catatan',
        ];
    }

    /**
     * Style the worksheet header only — weekend rows styled via AfterSheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4CAF50'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                if (empty($this->weekendRows)) {
                    return;
                }

                $sheet = $event->sheet->getDelegate();
                $weekendStyle = [
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E0E0E0'],
                    ],
                    'font' => [
                        'color' => ['rgb' => '757575'],
                    ],
                ];

                foreach ($this->weekendRows as $rowNumber) {
                    $sheet->getStyle('A' . $rowNumber . ':O' . $rowNumber)->applyFromArray($weekendStyle);
                }
            },
        ];
    }

    /**
     * Set worksheet title
     */
    public function title(): string
    {
        return 'Laporan Absensi';
    }
}
