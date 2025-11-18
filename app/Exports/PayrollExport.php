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
            $payroll->employee->position->name ?? '-',
            $this->formatPeriod($payroll->period_month),
            $payroll->payment_date ? date('d-m-Y', strtotime($payroll->payment_date)) : '-',
            $payroll->basic_salary,
            $payroll->allowance_transport,
            $payroll->allowance_meal,
            $payroll->allowance_position,
            $payroll->allowance_others,
            $payroll->overtime_pay,
            $payroll->bonus,
            $payroll->total_earnings,
            $payroll->deduction_late,
            $payroll->deduction_absent,
            $payroll->deduction_loan,
            $payroll->deduction_bpjs,
            $payroll->deduction_tax,
            $payroll->deduction_others,
            $payroll->total_deductions,
            $payroll->net_salary,
            $payroll->employee->bank ?? '-',
            $payroll->employee->nomor_rekening ?? '-',
            $this->formatStatus($payroll->status),
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
            'NIP',
            'Nama Karyawan',
            'Departemen',
            'Jabatan',
            'Periode',
            'Tanggal Bayar',
            'Gaji Pokok',
            'Tunj. Transport',
            'Tunj. Makan',
            'Tunj. Jabatan',
            'Tunj. Lainnya',
            'Uang Lembur',
            'Bonus',
            'Total Pendapatan',
            'Pot. Terlambat',
            'Pot. Alpha',
            'Pot. Pinjaman',
            'Pot. BPJS',
            'Pot. Pajak',
            'Pot. Lainnya',
            'Total Potongan',
            'Gaji Bersih',
            'Bank',
            'No. Rekening',
            'Status',
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
                $sheet->mergeCells('A' . $totalRow . ':G' . $totalRow);

                // Sum formula untuk kolom-kolom numerik
                // Kolom H-N: Gaji Pokok (H) sampai Bonus (N)
                $sheet->setCellValue('H' . $totalRow, '=SUM(H2:H' . $highestRow . ')');
                $sheet->setCellValue('I' . $totalRow, '=SUM(I2:I' . $highestRow . ')');
                $sheet->setCellValue('J' . $totalRow, '=SUM(J2:J' . $highestRow . ')');
                $sheet->setCellValue('K' . $totalRow, '=SUM(K2:K' . $highestRow . ')');
                $sheet->setCellValue('L' . $totalRow, '=SUM(L2:L' . $highestRow . ')');
                $sheet->setCellValue('M' . $totalRow, '=SUM(M2:M' . $highestRow . ')');
                $sheet->setCellValue('N' . $totalRow, '=SUM(N2:N' . $highestRow . ')');

                // Kolom O: Total Pendapatan
                $sheet->setCellValue('O' . $totalRow, '=SUM(O2:O' . $highestRow . ')');

                // Kolom P-U: Pot. Terlambat (P) sampai Pot. Lainnya (U)
                $sheet->setCellValue('P' . $totalRow, '=SUM(P2:P' . $highestRow . ')');
                $sheet->setCellValue('Q' . $totalRow, '=SUM(Q2:Q' . $highestRow . ')');
                $sheet->setCellValue('R' . $totalRow, '=SUM(R2:R' . $highestRow . ')');
                $sheet->setCellValue('S' . $totalRow, '=SUM(S2:S' . $highestRow . ')');
                $sheet->setCellValue('T' . $totalRow, '=SUM(T2:T' . $highestRow . ')');
                $sheet->setCellValue('U' . $totalRow, '=SUM(U2:U' . $highestRow . ')');

                // Kolom V-W: Total Potongan (V), Gaji Bersih (W)
                $sheet->setCellValue('V' . $totalRow, '=SUM(V2:V' . $highestRow . ')');
                $sheet->setCellValue('W' . $totalRow, '=SUM(W2:W' . $highestRow . ')');

                // Style total row: Bold, background kuning, border atas
                $sheet->getStyle('A' . $totalRow . ':AA' . $totalRow)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEF3C7'] // Yellow-100
                    ],
                    'borders' => [
                        'top' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                            'color' => ['rgb' => '000000']
                        ]
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ]
                ]);

                // Format currency untuk kolom total (H-W adalah kolom angka)
                $currencyColumns = ['H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W'];
                foreach ($currencyColumns as $col) {
                    $sheet->getStyle($col . $totalRow)->getNumberFormat()
                        ->setFormatCode('#,##0');
                }
            }
        ];
    }
}
