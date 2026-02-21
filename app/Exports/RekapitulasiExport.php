<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Karyawans;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class RekapitulasiExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $month;
    protected $year;
    protected $departmentId;
    protected $positionId;
    protected $employeeId;
    protected $rowNumber = 0;

    public function __construct($month, $year, $departmentId = null, $positionId = null, $employeeId = null)
    {
        $this->month = $month;
        $this->year = $year;
        $this->departmentId = $departmentId;
        $this->positionId = $positionId;
        $this->employeeId = $employeeId;
    }

    public function collection()
    {
        $employees = Karyawans::where('status', 'active')
            ->with(['department', 'position', 'workSchedule'])
            ->when($this->employeeId, function($q) {
                return $q->where('id', $this->employeeId);
            })
            ->when($this->departmentId, function($q) {
                return $q->where('department_id', $this->departmentId);
            })
            ->when($this->positionId, function($q) {
                return $q->where('position_id', $this->positionId);
            })
            ->orderBy('employee_code')
            ->get();

        $startDate = Carbon::createFromDate($this->year, $this->month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        $rekapitulasi = collect();
        
        foreach ($employees as $employee) {
            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereYear('attendance_date', $this->year)
                ->whereMonth('attendance_date', $this->month)
                ->get();

            $stats = [
                'hadir' => $attendances->where('status', 'hadir')->count(),
                'terlambat' => $attendances->where('status', 'terlambat')->count(),
                'izin' => $attendances->where('status', 'izin')->count(),
                'sakit' => $attendances->where('status', 'sakit')->count(),
                'cuti' => $attendances->where('status', 'cuti')->count(),
                'alpha' => $attendances->where('status', 'alpha')->count(),
            ];

            $workingDays = $this->calculateWorkingDays($employee, $startDate, $endDate);
            $totalPresent = $stats['hadir'] + $stats['terlambat'];
            $percentage = $workingDays > 0 ? round(($totalPresent / $workingDays) * 100, 1) : 0;

            $rekapitulasi->push((object)[
                'employee_code' => $employee->employee_code,
                'name' => $employee->name,
                'department' => $employee->department->name ?? '-',
                'position' => $employee->position->name ?? '-',
                'hadir' => $stats['hadir'],
                'terlambat' => $stats['terlambat'],
                'izin' => $stats['izin'],
                'sakit' => $stats['sakit'],
                'cuti' => $stats['cuti'],
                'alpha' => $stats['alpha'],
                'total_present' => $totalPresent,
                'working_days' => $workingDays,
                'percentage' => $percentage,
            ]);
        }

        return $rekapitulasi;
    }

    public function headings(): array
    {
        return [
            ['REKAPITULASI ABSENSI KARYAWAN'],
            ['Periode: ' . Carbon::createFromDate($this->year, $this->month, 1)->translatedFormat('F Y')],
            [],
            [
                'No',
                'Kode',
                'Nama Karyawan',
                'Department',
                'Jabatan',
                'Hadir',
                'Terlambat',
                'Izin',
                'Sakit',
                'Cuti',
                'Alpha',
                'Total Masuk',
                'Hari Kerja',
                'Persentase (%)',
            ]
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;
        
        return [
            $this->rowNumber,
            $row->employee_code,
            $row->name,
            $row->department,
            $row->position,
            $row->hadir,
            $row->terlambat,
            $row->izin,
            $row->sakit,
            $row->cuti,
            $row->alpha,
            $row->total_present,
            $row->working_days,
            $row->percentage . '%',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Merge title cells
        $sheet->mergeCells('A1:N1');
        $sheet->mergeCells('A2:N2');

        // Title styling
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setSize(11);
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header styling
        $sheet->getStyle('A4:N4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Auto column width
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Data alignment
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A5:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F5:N' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Borders for data
        $sheet->getStyle('A4:N' . $lastRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Rekapitulasi ' . Carbon::createFromDate($this->year, $this->month, 1)->format('M Y');
    }

    private function calculateWorkingDays($employee, $startDate, $endDate)
    {
        if (!$employee->workSchedule) {
            return $this->countWeekdays($startDate, $endDate);
        }

        $schedule = $employee->workSchedule;
        $workingDays = 0;
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dayOfWeek = strtolower($current->format('l'));
            $workColumn = 'work_' . $dayOfWeek;
            
            if (isset($schedule->$workColumn) && $schedule->$workColumn) {
                $workingDays++;
            }
            
            $current->addDay();
        }

        return $workingDays;
    }

    private function countWeekdays($startDate, $endDate)
    {
        $count = 0;
        $current = $startDate->copy();

        while ($current <= $endDate) {
            if ($current->isWeekday()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }
}
