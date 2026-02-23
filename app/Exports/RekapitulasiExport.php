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
    protected $filters;
    protected $rowNumber = 0;
    protected $startDate;
    protected $endDate;
    protected $periodName;

    public function __construct($filters = [])
    {
        $this->filters = array_merge([
            'period_type' => 'monthly',
            'month' => now()->month,
            'quarter' => now()->quarter,
            'year' => now()->year,
            'range_from_month' => null,
            'range_from_year' => null,
            'range_to_month' => null,
            'range_to_year' => null,
            'department_id' => null,
            'position_id' => null,
            'employee_id' => null,
            'join_date_from' => null,
            'join_date_to' => null,
        ], $filters);

        // Calculate date range based on period type
        if ($this->filters['period_type'] === 'quarterly') {
            $quarterStartMonth = ($this->filters['quarter'] - 1) * 3 + 1;
            $this->startDate = Carbon::createFromDate($this->filters['year'], $quarterStartMonth, 1)->startOfMonth();
            $this->endDate = $this->startDate->copy()->addMonths(2)->endOfMonth();
            $this->periodName = 'Kuartal ' . $this->filters['quarter'] . ' ' . $this->filters['year'] . ' (' . 
                $this->startDate->translatedFormat('F') . ' - ' . 
                $this->endDate->translatedFormat('F Y') . ')';
        } elseif ($this->filters['period_type'] === 'range') {
            $this->startDate = Carbon::createFromDate($this->filters['range_from_year'], $this->filters['range_from_month'], 1)->startOfMonth();
            $this->endDate = Carbon::createFromDate($this->filters['range_to_year'], $this->filters['range_to_month'], 1)->endOfMonth();
            $this->periodName = $this->startDate->translatedFormat('F Y') . ' - ' . $this->endDate->translatedFormat('F Y');
        } else {
            $this->startDate = Carbon::createFromDate($this->filters['year'], $this->filters['month'], 1);
            $this->endDate = $this->startDate->copy()->endOfMonth();
            $this->periodName = $this->startDate->translatedFormat('F Y');
        }
    }

    public function collection()
    {
        $employees = Karyawans::where('status', 'active')
            ->with(['department', 'position', 'workSchedule'])
            ->when($this->filters['employee_id'], function($q) {
                return $q->where('id', $this->filters['employee_id']);
            })
            ->when($this->filters['department_id'], function($q) {
                return $q->where('department_id', $this->filters['department_id']);
            })
            ->when($this->filters['position_id'], function($q) {
                return $q->where('position_id', $this->filters['position_id']);
            })
            ->when($this->filters['join_date_from'], function($q) {
                return $q->whereDate('join_date', '>=', $this->filters['join_date_from']);
            })
            ->when($this->filters['join_date_to'], function($q) {
                return $q->whereDate('join_date', '<=', $this->filters['join_date_to']);
            })
            ->orderBy('employee_code')
            ->get();
        
        $rekapitulasi = collect();
        
        foreach ($employees as $employee) {
            $attendances = Attendance::where('employee_id', $employee->id)
                ->whereBetween('attendance_date', [$this->startDate->format('Y-m-d'), $this->endDate->format('Y-m-d')])
                ->get();

            $stats = [
                'hadir' => $attendances->where('status', 'hadir')->count(),
                'terlambat' => $attendances->where('status', 'terlambat')->count(),
                'izin' => $attendances->where('status', 'izin')->count(),
                'sakit' => $attendances->where('status', 'sakit')->count(),
                'cuti' => $attendances->where('status', 'cuti')->count(),
                'alpha' => $attendances->where('status', 'alpha')->count(),
            ];

            $workingDays = $this->calculateWorkingDays($employee, $this->startDate, $this->endDate);
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
            ['Periode: ' . $this->periodName],
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
        if ($this->filters['period_type'] === 'quarterly') {
            return 'Rekap Q' . $this->filters['quarter'] . ' ' . $this->filters['year'];
        } elseif ($this->filters['period_type'] === 'range') {
            return 'Rekap ' . $this->startDate->format('M Y') . ' - ' . $this->endDate->format('M Y');
        }
        return 'Rekapitulasi ' . $this->startDate->format('M Y');
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
