<?php

namespace App\Exports;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DepartmentHierarchyExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected ?string $search = null;
    protected array $data = [];
    protected array $parentRows = [];
    protected array $childRows = [];
    protected int $summaryRow = 0;

    public function __construct(?string $search = null)
    {
        $this->search = $search;
        $this->buildData();
    }

    protected function buildData(): void
    {
        $query = Department::with([
            'subDepartments' => function ($q) {
                $q->withCount([
                    'employees as employees_count' => function ($sq) {
                        $sq->where('status', 'active');
                    }
                ])->orderBy('name', 'asc');
            }
        ])
        ->withCount([
            'employees as employees_count' => function ($q) {
                $q->where('status', 'active');
            }
        ]);

        if (!empty($this->search)) {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhereHas('subDepartments', function ($sq) use ($term) {
                      $sq->where('name', 'like', "%{$term}%");
                  });
            });
        }

        $departments = $query->orderBy('name', 'asc')->get();

        $rows = [];
        $currentRow = 2; // Baris 1: header
        $no = 1;
        $totalSubDepartments = 0;
        $totalEmployees = 0;

        foreach ($departments as $dept) {
            $parentEmployees = $dept->employees_count ?? 0;
            $totalEmployees += $parentEmployees;

            // Baris Parent (Departemen)
            $rows[] = [
                'no' => $no++,
                'name' => strtoupper($dept->name),
                'type' => 'Departemen (Parent)',
                'status' => '-',
                'employees_count' => $parentEmployees,
                'description' => $dept->description ?: '-',
            ];
            $this->parentRows[] = $currentRow;
            $currentRow++;

            // Baris Child (Sub Departemen)
            if ($dept->subDepartments && $dept->subDepartments->count() > 0) {
                foreach ($dept->subDepartments as $sub) {
                    $totalSubDepartments++;
                    $rows[] = [
                        'no' => '',
                        'name' => '   ↳ ' . $sub->name,
                        'type' => 'Sub Departemen',
                        'status' => $sub->is_active ? 'Aktif' : 'Tidak Aktif',
                        'employees_count' => $sub->employees_count ?? 0,
                        'description' => $sub->description ?: '-',
                    ];
                    $this->childRows[] = $currentRow;
                    $currentRow++;
                }
            } else {
                $rows[] = [
                    'no' => '',
                    'name' => '   ↳ (Belum ada sub departemen)',
                    'type' => '-',
                    'status' => '-',
                    'employees_count' => 0,
                    'description' => '-',
                ];
                $this->childRows[] = $currentRow;
                $currentRow++;
            }
        }

        // Baris Total / Summary
        $rows[] = [
            'no' => '',
            'name' => 'TOTAL (' . count($departments) . ' Departemen)',
            'type' => count($departments) . ' Departemen',
            'status' => $totalSubDepartments . ' Sub Dept',
            'employees_count' => $totalEmployees,
            'description' => '',
        ];
        $this->summaryRow = $currentRow;

        $this->data = $rows;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Departemen / Sub Departemen',
            'Tipe',
            'Status Sub',
            'Karyawan Aktif',
            'Deskripsi',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 45,
            'C' => 24,
            'D' => 16,
            'E' => 18,
            'F' => 45,
        ];
    }

    public function title(): string
    {
        return 'Struktur Departemen';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setShowGridlines(true);
        $sheet->setShowSummaryBelow(false);
        $sheet->freezePane('A2');

        $highestRow = $sheet->getHighestRow();

        // 1. Header Styling
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E79'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // General styling data
        if ($highestRow >= 2) {
            $sheet->getStyle("A2:F{$highestRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E0E0E0'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet->getStyle("A2:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C2:D{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E2:E{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // 2. Parent Rows Styling
        foreach ($this->parentRows as $r) {
            $sheet->getRowDimension($r)->setRowHeight(24);
            $sheet->getStyle("A{$r}:F{$r}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 10.5,
                    'color' => ['rgb' => '0F2A4A'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9E1F2'],
                ],
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '8EA9DB'],
                    ],
                    'bottom' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '8EA9DB'],
                    ],
                ],
            ]);
        }

        // 3. Child Rows Styling & Outline Grouping
        foreach ($this->childRows as $r) {
            $sheet->getRowDimension($r)->setRowHeight(20);
            $sheet->getRowDimension($r)->setOutlineLevel(1);
            $sheet->getRowDimension($r)->setVisible(true);
            $sheet->getRowDimension($r)->setCollapsed(false);

            $statusCell = $sheet->getCell("D{$r}")->getValue();
            if ($statusCell === 'Aktif') {
                $sheet->getStyle("D{$r}")->getFont()->getColor()->setRGB('1E7E34');
            } elseif ($statusCell === 'Tidak Aktif') {
                $sheet->getStyle("D{$r}")->getFont()->getColor()->setRGB('D9534F');
            }
        }

        // 4. Summary Row Styling
        if ($this->summaryRow > 0) {
            $s = $this->summaryRow;
            $sheet->getRowDimension($s)->setRowHeight(26);
            $sheet->getStyle("A{$s}:F{$s}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['rgb' => '000000'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'B4C6E7'],
                ],
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                    'bottom' => [
                        'borderStyle' => Border::BORDER_DOUBLE,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);
            $sheet->getStyle("B{$s}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        return $sheet;
    }
}
