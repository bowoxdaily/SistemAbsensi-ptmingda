<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class GeographicRekapExport implements WithHeadings, WithStyles, WithTitle, FromArray, WithMapping
{
    protected $groupedData;
    protected $groupLevel;
    protected $filters;
    protected $rowNumber = 0;
    protected $currentGroup = null;
    protected $flattenedData = [];

    public function __construct($data = [])
    {
        $this->groupedData = $data['grouped_data'] ?? collect();
        $this->groupLevel = $data['group_level'] ?? 'kabupaten';
        $this->filters = $data['filters'] ?? [];

        // Flatten data for export
        $this->flattenedData = $this->flattenGroupedData();
    }

    /**
     * Flatten grouped data into a single array
     */
    private function flattenGroupedData()
    {
        $result = [];

        foreach ($this->groupedData as $locationName => $employees) {
            // Add location header row
            $result[] = [
                'location_header' => $locationName,
                'employee_code' => '',
                'employee_name' => '',
                'department' => '',
                'position' => '',
                'province' => '',
                'kabupaten' => '',
                'kecamatan' => '',
                'desa' => '',
                'join_date' => '',
                'status' => '',
            ];

            // Add employee rows
            foreach ($employees as $employee) {
                // Format join_date - handle both string and Carbon object
                $joinDate = $employee->join_date ?? null;
                if ($joinDate) {
                    if (is_string($joinDate)) {
                        $joinDateFormatted = $joinDate; // Already formatted
                    } else {
                        $joinDateFormatted = $joinDate->format('Y-m-d'); // Carbon object
                    }
                } else {
                    $joinDateFormatted = '-';
                }

                $result[] = [
                    'location_header' => '',
                    'employee_code' => $employee->employee_code ?? '',
                    'employee_name' => $employee->name ?? '',
                    'department' => $employee->department->name ?? '-',
                    'position' => $employee->position->name ?? '-',
                    'province' => $employee->province ?? '-',
                    'kabupaten' => $employee->kabupaten ?? '-',
                    'kecamatan' => $employee->kecamatan ?? '-',
                    'desa' => $employee->desa ?? '-',
                    'join_date' => $joinDateFormatted,
                    'status' => ucfirst($employee->status ?? '-'),
                ];
            }

            // Add summary row
            $result[] = [
                'location_header' => 'Total: ' . count($employees) . ' karyawan',
                'employee_code' => '',
                'employee_name' => '',
                'department' => '',
                'position' => '',
                'province' => '',
                'kabupaten' => '',
                'kecamatan' => '',
                'desa' => '',
                'join_date' => '',
                'status' => '',
            ];

            // Add blank row
            $result[] = [
                'location_header' => '',
                'employee_code' => '',
                'employee_name' => '',
                'department' => '',
                'position' => '',
                'province' => '',
                'kabupaten' => '',
                'kecamatan' => '',
                'desa' => '',
                'join_date' => '',
                'status' => '',
            ];
        }

        return $result;
    }

    public function array(): array
    {
        return $this->flattenedData;
    }

    public function map($row): array
    {
        return [
            $row['location_header'] ?? '',
            $row['employee_code'] ?? '',
            $row['employee_name'] ?? '',
            $row['department'] ?? '',
            $row['position'] ?? '',
            $row['province'] ?? '',
            $row['kabupaten'] ?? '',
            $row['kecamatan'] ?? '',
            $row['desa'] ?? '',
            $row['join_date'] ?? '',
            $row['status'] ?? '',
        ];
    }

    public function headings(): array
    {
        return [
            'Lokasi / Kode',
            'Kode Karyawan',
            'Nama Karyawan',
            'Departemen',
            'Posisi',
            'Provinsi',
            'Kabupaten/Kota',
            'Kecamatan',
            'Desa/Kelurahan',
            'Tanggal Bergabung',
            'Status',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // Style header row
        $sheet->getStyle('1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '366092'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Apply lightweight range-based styles to reduce memory overhead.
        if ($highestRow >= 2) {
            $sheet->getStyle('A2:K' . $highestRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(18);
        $sheet->getColumnDimension('K')->setWidth(15);

        return $sheet;
    }

    public function title(): string
    {
        return 'Rekap ' . ucfirst($this->groupLevel);
    }
}
