<?php

namespace App\Exports;

use App\Models\WarningLetter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WarningLetterExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = WarningLetter::with(['employee.department', 'employee.position', 'issuer'])
            ->orderBy('issue_date', 'desc');

        // Filter by employee
        if (!empty($this->filters['employee_id'])) {
            $query->where('employee_id', $this->filters['employee_id']);
        }

        // Filter by SP type
        if (!empty($this->filters['sp_type'])) {
            $query->where('sp_type', $this->filters['sp_type']);
        }

        // Filter by status
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        // Filter by date range (issue_date)
        if (!empty($this->filters['start_date'])) {
            $query->whereDate('issue_date', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('issue_date', '<=', $this->filters['end_date']);
        }

        // Search by SP number or violation
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('sp_number', 'like', "%{$search}%")
                    ->orWhere('violation', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No',
            'Nama Karyawan',
            'NIK',
            'Departemen',
            'Jabatan',
            'Jenis SP',
            'Nomor SP',
            'Tanggal Dikeluarkan',
            'Tanggal Berlaku',
            'Pelanggaran',
            'Deskripsi',
            'Status',
            'Tanggal Selesai',
            'Alasan Pembatalan',
            'Dikeluarkan Oleh',
            'Dibuat Pada',
        ];
    }

    /**
     * @param mixed $sp
     * @return array
     */
    public function map($sp): array
    {
        static $rowNumber = 1;
        
        return [
            $rowNumber++,
            $sp->employee->name ?? '-',
            $sp->employee->nik ?? ($sp->employee->employee_code ?? '-'),
            $sp->employee->department->name ?? '-',
            $sp->employee->position->name ?? '-',
            $sp->sp_type_label,
            $sp->sp_number,
            $this->formatDate($sp->issue_date),
            $this->formatDate($sp->effective_date),
            $sp->violation,
            $sp->description ?? '-',
            ucfirst($sp->status),
            $this->formatDate($sp->completion_date),
            $sp->cancellation_reason ?? '-',
            $sp->issuer->name ?? '-',
            $sp->created_at ? $sp->created_at->format('Y-m-d H:i') : '-',
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 5,   // No
            'B' => 25,  // Nama Karyawan
            'C' => 15,  // NIK
            'D' => 20,  // Departemen
            'E' => 20,  // Jabatan
            'F' => 25,  // Jenis SP
            'G' => 25,  // Nomor SP
            'H' => 20,  // Tanggal Dikeluarkan
            'I' => 20,  // Tanggal Berlaku
            'J' => 30,  // Pelanggaran
            'K' => 40,  // Deskripsi
            'L' => 15,  // Status
            'M' => 20,  // Tanggal Selesai
            'N' => 30,  // Alasan Pembatalan
            'O' => 20,  // Dikeluarkan Oleh
            'P' => 20,  // Dibuat Pada
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => [
                'font' => ['bold' => true, 'size' => 11],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8F5E9']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Format date
     */
    private function formatDate($date)
    {
        if (!$date) {
            return '-';
        }
        
        if (is_string($date)) {
            return $date;
        }
        
        if (is_object($date) && method_exists($date, 'format')) {
            return $date->format('Y-m-d');
        }
        
        return '-';
    }
}
