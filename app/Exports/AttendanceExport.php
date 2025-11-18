<?php

namespace App\Exports;

use App\Models\Attendance;
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
        $query = Attendance::with(['employee.department', 'employee.position', 'employee.workSchedule']);

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
        $data = $query->get()
            ->sortBy(function ($attendance) {
                return [
                    $attendance->employee->employee_code,  // Sort by NIP first
                    $attendance->attendance_date            // Then by date
                ];
            })
            ->values();

        // Calculate total hadir per employee (based on status = 'Hadir')
        $this->totalHadirByEmployee = $data
            ->filter(function ($attendance) {
                return strtoupper($attendance->status) === 'HADIR';
            })
            ->groupBy(function ($attendance) {
                return $attendance->employee->employee_code;
            })
            ->map(function ($group) {
                return count($group);
            })
            ->toArray();

        return $data;
    }

    /**
     * Map data for export
     */
    public function map($attendance): array
    {
        $this->rowNumber++;
        $employeeCode = $attendance->employee->employee_code ?? '-';
        $totalHadir = $this->totalHadirByEmployee[$employeeCode] ?? 0;
        
        return [
            $this->rowNumber,
            $employeeCode,
            $attendance->employee->name ?? '-',
            $attendance->employee->department->name ?? '-',
            $attendance->employee->position->name ?? '-',
            Carbon::parse($attendance->attendance_date)->locale('id')->translatedFormat('l, d F Y'),
            $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : '-',
            $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i') : '-',
            $attendance->employee->workSchedule ?
                Carbon::parse($attendance->employee->workSchedule->start_time)->format('H:i') . ' - ' .
                Carbon::parse($attendance->employee->workSchedule->end_time)->format('H:i') : '-',
            strtoupper($attendance->status),
            $attendance->late_minutes > 0 ? $attendance->late_minutes . ' menit' : '-',
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
            'Jabatan',
            'Tanggal',
            'Check In',
            'Check Out',
            'Jam Kerja',
            'Status',
            'Terlambat',
            'Total Hadir',
            'Catatan',
        ];
    }

    /**
     * Style the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
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
    }

    /**
     * Set worksheet title
     */
    public function title(): string
    {
        return 'Laporan Absensi';
    }
}
