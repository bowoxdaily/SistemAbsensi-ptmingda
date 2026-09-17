<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OvertimeExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithEvents, WithTitle
{
    protected string $dateFrom;
    protected string $dateTo;
    protected ?string $category;
    protected ?string $search;
    protected ?int $employeeId;
    protected array $subtotalRows = [];
    protected int $grandTotalRow = 0;
    protected array $builtData = [];

    public function __construct(string $dateFrom, string $dateTo, ?string $category = null, ?string $search = null, ?int $employeeId = null)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->category = $category;
        $this->search = $search;
        $this->employeeId = $employeeId;
        $this->builtData = $this->buildRows();
    }

    public function array(): array
    {
        return $this->builtData;
    }

    protected function buildRows(): array
    {
        $attendances = $this->queryData();
        if ($attendances->isEmpty()) {
            return [];
        }
        $grouped = $attendances->groupBy('employee_id');
        $rows = [];
        $no = 0;
        $grandJam = 0;
        $grandMenit = 0;
        $excelRow = 1;
        foreach ($grouped as $items) {
            $subJam = 0;
            $subMenit = 0;
            $empName = '';
            foreach ($items as $att) {
                $no++;
                $excelRow++;
                $h = intdiv($att->overtime_minutes, 60);
                $m = $att->overtime_minutes % 60;
                $otH = 0;
                if (preg_match('/^OT(\d+)$/', (string) $att->overtime_category, $match)) {
                    $otH = (int) $match[1];
                }
                $empName = $att->employee->name ?? '-';
                $rows[] = [$no, $att->employee->employee_code ?? '-', $empName, $att->attendance_date->format('d-m-Y'), substr($att->check_out, 0, 5), sprintf('%d:%02d', $h, $m), $att->overtime_category, $otH, $otH * 60];
                $subJam += $otH;
                $subMenit += $otH * 60;
            }
            $excelRow++;
            $rows[] = ['', '', 'SUBTOTAL ' . mb_strtoupper($empName), '', '', '', '', $subJam, $subMenit];
            $this->subtotalRows[] = $excelRow;
            $grandJam += $subJam;
            $grandMenit += $subMenit;
        }
        $excelRow++;
        $rows[] = ['', '', 'GRAND TOTAL', '', '', '', '', $grandJam, $grandMenit];
        $this->grandTotalRow = $excelRow;
        return $rows;
    }

    protected function queryData()
    {
        return Attendance::with('employee')
            ->whereBetween('attendance_date', [$this->dateFrom, $this->dateTo])
            ->whereNotNull('check_out')
            ->whereNotNull('overtime_category')
            ->when($this->employeeId, fn ($q) => $q->where('employee_id', $this->employeeId))
            ->when($this->search, function ($q) {
                $q->whereHas('employee', fn ($e) => $e->where('name', 'like', "%{$this->search}%")->orWhere('employee_code', 'like', "%{$this->search}%"));
            })
            ->when($this->category, fn ($q) => $q->where('overtime_category', $this->category))
            ->orderBy('employee_id')->orderBy('attendance_date')->orderBy('overtime_category')
            ->get();
    }

    public static function countRows(string $dateFrom, string $dateTo, ?string $category = null, ?string $search = null, ?int $employeeId = null): int
    {
        return Attendance::whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->whereNotNull('check_out')->whereNotNull('overtime_category')
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($search, fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('name', 'like', "%{$search}%")->orWhere('employee_code', 'like', "%{$search}%")))
            ->when($category, fn ($q) => $q->where('overtime_category', $category))
            ->count();
    }

    public function headings(): array
    {
        return ['No', 'NIP', 'Nama Karyawan', 'Tanggal', 'Jam Pulang', 'Durasi Riil (jam:menit)', 'Kategori', 'Lembur Diakui (Jam)', 'Lembur Diakui (Menit)'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4CAF50']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        $subtotalRows = $this->subtotalRows;
        $grandTotalRow = $this->grandTotalRow;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($subtotalRows, $grandTotalRow) {
                $sheet = $event->sheet->getDelegate();
                $subStyle = [
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                    ],
                ];
                foreach ($subtotalRows as $row) {
                    $sheet->mergeCells("A{$row}:G{$row}");
                    $sheet->getStyle("A{$row}:I{$row}")->applyFromArray($subStyle);
                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
                if ($grandTotalRow > 0) {
                    $sheet->mergeCells("A{$grandTotalRow}:G{$grandTotalRow}");
                    $sheet->getStyle("A{$grandTotalRow}:I{$grandTotalRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '81C784']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => [
                            'top' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']],
                            'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '000000']],
                        ],
                    ]);
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Laporan Overtime';
    }
}

