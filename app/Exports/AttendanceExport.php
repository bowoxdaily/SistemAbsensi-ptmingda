<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Karyawans;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $dateFrom;
    protected $dateTo;
    protected $status;
    protected $department;
    protected $search;
    protected $rowNumber = 0;
    protected $attendanceData = [];
    protected $totalHadirByEmployee = [];
    protected $weekendRows = []; // Track weekend placeholder rows

    public function __construct($dateFrom, $dateTo, $status = null, $department = null, $search = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->status = $status;
        $this->department = $department;
        $this->search = $search;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Attendance::with(['employee.department', 'employee.subDepartment', 'employee.position', 'employee.workSchedule']);

        // Apply filters
        if ($this->search) {
            $query->whereHas('employee', function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('employee_code', 'like', "%{$this->search}%");
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->department) {
            $query->whereHas('employee', function ($q) {
                $q->where('department_id', $this->department);
            });
        }

        $query->whereBetween('attendance_date', [$this->dateFrom, $this->dateTo]);

        // Get all attendance data
        $data = $query->get();

        // Generate weekend placeholders
        $weekendPlaceholders = $this->generateWeekendPlaceholders($this->dateFrom, $this->dateTo, $data);

        // Combine attendance data with weekend placeholders
        $combinedData = $data->concat($weekendPlaceholders);

        // Sort by employee code and date
        $sortedData = $combinedData->sortBy(function ($attendance) {
            $employeeCode = isset($attendance->employee) ? $attendance->employee->employee_code : '-';
            $date = isset($attendance->attendance_date) ? $attendance->attendance_date : null;

            return [
                $employeeCode,  // Sort by NIP first
                $date           // Then by date
            ];
        })->values();

        // Calculate total hadir per employee (based on status = 'Hadir')
        $this->totalHadirByEmployee = $sortedData
            ->filter(function ($attendance) {
                // Skip weekend placeholders
                if (isset($attendance->is_weekend_placeholder) && $attendance->is_weekend_placeholder) {
                    return false;
                }
                return isset($attendance->status) && strtoupper($attendance->status) === 'HADIR';
            })
            ->groupBy(function ($attendance) {
                return $attendance->employee->employee_code ?? '-';
            })
            ->map(function ($group) {
                return count($group);
            })
            ->toArray();

        return $sortedData;
    }

    /**
     * Generate weekend (Saturday & Sunday) placeholders for dates without attendance
     */
    private function generateWeekendPlaceholders($dateFrom, $dateTo, $existingAttendances)
    {
        $placeholders = collect();

        // Get unique employees from existing attendances
        $employees = $existingAttendances->pluck('employee')->unique('id');

        // If no employees found from data, try to get from search filter
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

        // Generate dates between dateFrom and dateTo
        $start = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);

        // Build a map of existing attendance dates per employee for quick lookup
        $existingDates = $existingAttendances->groupBy('employee_id')->map(function ($items) {
            return $items->pluck('attendance_date')->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            });
        });

        // Generate placeholders for weekends (Saturday & Sunday) without attendance
        foreach ($employees as $employee) {
            $current = $start->copy();
            while ($current->lte($end)) {
                // Check if it's Saturday (6) or Sunday (0)
                if ($current->dayOfWeek === 6 || $current->dayOfWeek === 0) {
                    $dateStr = $current->format('Y-m-d');

                    // Check if this employee has attendance on this date
                    $hasAttendance = isset($existingDates[$employee->id]) &&
                                    $existingDates[$employee->id]->contains($dateStr);

                    if (!$hasAttendance) {
                        // Create placeholder object
                        $placeholder = new \stdClass();
                        $placeholder->is_weekend_placeholder = true;
                        $placeholder->weekend_day = $current->dayOfWeek === 6 ? 'Sabtu' : 'Minggu';
                        $placeholder->id = null;
                        $placeholder->employee_id = $employee->id;
                        $placeholder->employee = $employee;
                        $placeholder->attendance_date = Carbon::parse($dateStr);
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

        // Handle weekend placeholder
        if (isset($attendance->is_weekend_placeholder) && $attendance->is_weekend_placeholder) {
            // Track this row number for styling
            $this->weekendRows[] = $this->rowNumber + 1; // +1 because Excel rows start at 1, plus 1 for header

            $employeeCode = $attendance->employee->employee_code ?? '-';
            $totalHadir = $this->totalHadirByEmployee[$employeeCode] ?? 0;

            return [
                $this->rowNumber,
                $employeeCode,
                $attendance->employee->name ?? '-',
                $attendance->employee->department->name ?? '-',
                $attendance->employee->subDepartment->name ?? '-',
                $attendance->employee->position->name ?? '-',
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

        // Handle normal attendance
        $employeeCode = $attendance->employee->employee_code ?? '-';
        $totalHadir = $this->totalHadirByEmployee[$employeeCode] ?? 0;

        return [
            $this->rowNumber,
            $employeeCode,
            $attendance->employee->name ?? '-',
            $attendance->employee->department->name ?? '-',
            $attendance->employee->subDepartment->name ?? '-',
            $attendance->employee->position->name ?? '-',
            Carbon::parse($attendance->attendance_date)->locale('id')->translatedFormat('l, d F Y'),
            $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : '-',
            $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i') : '-',
            $attendance->employee->workSchedule ?
                Carbon::parse($attendance->employee->workSchedule->start_time)->format('H:i') . ' - ' .
                Carbon::parse($attendance->employee->workSchedule->end_time)->format('H:i') : '-',
            strtoupper($attendance->status),
            $attendance->late_minutes > 0 ? $attendance->late_minutes . ' menit' : '-',
            $attendance->overtime_minutes > 0 ? $attendance->overtime_minutes . ' menit' : '-',
            $totalHadir,
            $attendance->notes ?? '-',
        ];
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
     * Style the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        $styles = [
            // Style the first row as header
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4CAF50']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];

        // Apply grey background to weekend placeholder rows
        foreach ($this->weekendRows as $rowNumber) {
            $styles[$rowNumber] = [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0'] // Light grey
                ],
                'font' => [
                    'color' => ['rgb' => '757575'], // Grey text
                ],
            ];
        }

        return $styles;
    }

    /**
     * Set worksheet title
     */
    public function title(): string
    {
        return 'Laporan Absensi';
    }
}
