<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OvertimeExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents, WithTitle
{
    protected string $dateFrom;
    protected string $dateTo;
    protected ?string $category;
    protected ?string $search;
    protected int $rowNumber = 0;

    public function __construct(string $dateFrom, string $dateTo, ?string $category = null, ?string $search = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->category = $category;
        $this->search = $search;
    }

    public function query()
    {
        return Attendance::with('employee')
            ->whereBetween('attendance_date', [$this->dateFrom, $this->dateTo])
            ->whereNotNull('check_out')
            ->whereNotNull('overtime_category')
            ->when($this->search, function ($query) {
                $query->whereHas('employee', function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('employee_code', 'like', "%{$this->search}%");
                });
            })
            ->when($this->category, fn ($query) => $query->where('overtime_category', $this->category))
            ->orderByDesc('attendance_date')
            ->orderBy('overtime_category');
    }

    public function map($attendance): array
    {
        $this->rowNumber++;
        $hours = intdiv($attendance->overtime_minutes, 60);
        $minutes = $attendance->overtime_minutes % 60;

        return [
            $this->rowNumber,
            $attendance->employee->employee_code ?? '-',
            $attendance->employee->name ?? '-',
            $attendance->attendance_date->format('d-m-Y'),
            substr($attendance->check_out, 0, 5),
            $attendance->overtime_minutes,
            sprintf('%d:%02d', $hours, $minutes),
            $attendance->overtime_category,
        ];
    }

    public function headings(): array
    {
        return [
            'No',
            'NIP',
            'Nama Karyawan',
            'Tanggal',
            'Jam Pulang',
            'Durasi (menit)',
            'Durasi (jam:menit)',
            'Kategori',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
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
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $totalRow = $highestRow + 1;

                $sheet->setCellValue('A' . $totalRow, 'TOTAL');
                $sheet->mergeCells('A' . $totalRow . ':E' . $totalRow);
                $sheet->setCellValue('F' . $totalRow, '=SUM(F2:F' . $highestRow . ')');

                $sheet->getStyle('A' . $totalRow . ':H' . $totalRow)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEF3C7'],
                    ],
                    'borders' => [
                        'top' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
            },
        ];
    }

    public function title(): string
    {
        return 'Laporan Overtime';
    }
}
