<?php

namespace App\Exports;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Request;

class PayrollExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Query data with filters
     */
    public function query()
    {
        $search = $this->request->get('search', '');
        $period = $this->request->get('period');
        $status = $this->request->get('status');

        return Payroll::with(['employee.department', 'employee.position'])
            ->when($search, function ($query, $search) {
                return $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%");
                })->orWhere('payroll_code', 'like', "%{$search}%");
            })
            ->when($period, function ($query, $period) {
                return $query->where('period_month', $period);
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('period_month', 'desc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Map data for each row
     */
    public function map($payroll): array
    {
        return [
            $payroll->payroll_code,
            $payroll->employee->employee_code,
            $payroll->employee->name,
            $payroll->employee->department->name ?? '-',
            $payroll->employee->subDepartment->name ?? ($payroll->employee->sub_department ?? '-'),
            $payroll->employee->position->name ?? '-',
            $payroll->employee->gender ?? '-',
            $payroll->employee->marital_status ?? '-',
            $payroll->employee->ktp ?? '-',
            $payroll->employee->tax_npwp ?? '-',
            $payroll->employee->bpjs_kesehatan ?? '-',
            $payroll->employee->bpjs_ketenagakerjaan ?? '-',
            $payroll->employee->nomor_rekening ?? '-',
            $payroll->basic_salary,
            $payroll->allowance_position,
            $payroll->bonus,
            $payroll->basic_salary + $payroll->allowance_position + $payroll->bonus, // Total Gaji
            $payroll->hourly_rate,
            $payroll->daily_rate,
            $payroll->days_leave ?? 0,
            $payroll->days_sick ?? 0,
            $payroll->total_days_present,
            $payroll->total_days_present + ($payroll->days_leave ?? 0) + ($payroll->days_sick ?? 0), // Total hari dibayar
            $payroll->meal_allowance ?? $payroll->allowance_meal,
            $payroll->overtime_150_minutes ?? 0,
            round(($payroll->overtime_150_minutes ?? 0) / 60 * 1.5, 2), // Jam x rate
            $payroll->overtime_150_pay ?? 0,
            $payroll->overtime_200_minutes ?? 0,
            round(($payroll->overtime_200_minutes ?? 0) / 60 * 2, 2),
            $payroll->overtime_200_pay ?? 0,
            $payroll->overtime_pay,
            $payroll->company_bpjs_total ?? 0,
            $payroll->deduction_absent,
            $payroll->deduction_permission ?? 0,
            $payroll->deduction_late,
            $payroll->late_minutes_total ?? 0,
            $payroll->total_earnings,
            $payroll->deduction_tax,
            $payroll->deduction_bpjs,
            $payroll->deduction_union,
            $payroll->total_deductions,
            $payroll->net_salary,
            $payroll->employee->bank ?? '-',
            $this->formatStatus($payroll->status),
            $payroll->generated_by ?? 'manual',
            $payroll->notes ?? '-',
        ];
    }

    /**
     * Column headings
     */
    public function headings(): array
    {
        return [
            'Kode Payroll',
            'NIK',
            'Nama',
            'Departemen',
            'Bagian',
            'Jabatan',
            'Gender',
            'Status Kawin',
            'KTP',
            'NPWP',
            'BPJS Kesehatan',
            'BPJS Ketenagakerjaan',
            'No Rekening',
            'Upah Pokok',
            'Tunj. Jabatan',
            'Tunj. Bonus',
            'Total Gaji',
            'Biaya Lembur/Jam',
            'Gaji/Hari',
            'Cuti',
            'Sakit',
            'Hadir Kerja Normal',
            'Total Hari Dibayar',
            'Tunj. Makan',
            'OT Jam ke-1 (menit)',
            'OT Jam ke-1 (jam x 1.5)',
            'OT Jam ke-1 (Rp)',
            'OT Jam ke-2+ (menit)',
            'OT Jam ke-2+ (jam x 2)',
            'OT Jam ke-2+ (Rp)',
            'Total Tunj. Lembur',
            'Tunj. BPJS Perusahaan',
            'Pot. Alpha',
            'Pot. Izin',
            'Pot. Terlambat',
            'Menit Terlambat',
            'Total Pendapatan',
            'Pot. PPh21',
            'Pot. BPJS Karyawan',
            'Pot. Serikat',
            'Total Potongan',
            'Gaji Bersih',
            'Bank',
            'Status',
            'Metode',
            'Catatan',
        ];
    }

    /**
     * Apply styles to worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ],
        ];
    }

    /**
     * Format period
     */
    private function formatPeriod($period)
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        list($year, $month) = explode('-', $period);
        return $months[intval($month) - 1] . ' ' . $year;
    }

    /**
     * Format status
     */
    private function formatStatus($status)
    {
        $statusMap = [
            'draft' => 'Draft',
            'sent' => 'Terkirim',
            'paid' => 'Dibayar'
        ];
        return $statusMap[$status] ?? $status;
    }

    /**
     * Register events untuk menambahkan total row
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $totalRow = $highestRow + 1;

                // Set label "TOTAL" di kolom pertama
                $sheet->setCellValue('A' . $totalRow, 'TOTAL');
                $sheet->mergeCells('A' . $totalRow . ':N' . $totalRow);

                // Kolom numerik: O=Upah Pokok, P=Tunj. Jabatan, Q=Tunj. Bonus, R=Total Gaji,
                // S=Gaji/Jam, T=Gaji/Hari, U=Cuti, V=Sakit, W=Hadir Normal, X=Total Hari Dibayar,
                // Y=Tunj. Makan, Z=OT1 menit, AA=OT1 jam, AB=OT1 Rp, AC=OT2+ menit, AD=OT2+ jam,
                // AE=OT2+ Rp, AF=Total Lembur, AG=BPJS Perusahaan, AH=Pot. Alpha, AI=Pot. Izin,
                // AJ=Pot. Terlambat, AK=Menit Terlambat, AL=Total Pendapatan, AM=Pot. PPh21,
                // AN=Pot. BPJS Karyawan, AO=Pot. Serikat, AP=Total Potongan, AQ=Gaji Bersih

                $numericCols = ['O','P','Q','R','S','T','U','V','W','X','Y','Z',
                    'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ'];

                foreach ($numericCols as $col) {
                    $sheet->setCellValue($col . $totalRow, "=SUM({$col}2:{$col}{$highestRow})");
                }

                // Style total row
                $lastCol = 'AQ';
                $sheet->getStyle('A' . $totalRow . ':' . $lastCol . $totalRow)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEF3C7']
                    ],
                    'borders' => [
                        'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK, 'color' => ['rgb' => '000000']]
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ]
                ]);

                foreach ($numericCols as $col) {
                    $sheet->getStyle($col . $totalRow)->getNumberFormat()->setFormatCode('#,##0');
                }
            }
        ];
    }
}
