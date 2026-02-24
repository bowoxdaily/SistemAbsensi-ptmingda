<?php

namespace App\Exports;

use App\Models\Karyawans;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StatusKaryawanExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $search      = $this->filters['search'] ?? null;
        $status      = $this->filters['status'] ?? null;
        $deptId      = $this->filters['department_id'] ?? null;
        $joinFrom    = $this->filters['join_from'] ?? null;
        $joinTo      = $this->filters['join_to'] ?? null;

        return Karyawans::with(['department', 'subDepartment', 'position'])
            ->when($search, fn($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                   ->orWhere('employee_code', 'like', "%{$search}%")
                   ->orWhere('nik', 'like', "%{$search}%");
            }))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($deptId, fn($q) => $q->where('department_id', $deptId))
            ->when($joinFrom, fn($q) => $q->whereDate('join_date', '>=', $joinFrom))
            ->when($joinTo, fn($q) => $q->whereDate('join_date', '<=', $joinTo))
            ->orderBy('employee_code')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode',
            'NIK',
            'Nama',
            'Departemen',
            'Sub Departemen',
            'Jabatan',
            'Status Kerja',
            'Tipe Karyawan',
            'Tanggal Bergabung',
            'Tanggal Resign',
            'Tanggal Mangkir',
            'Tanggal Gagal Probation',
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;

        $statusLabel = [
            'active'          => 'Aktif',
            'inactive'        => 'Tidak Aktif',
            'resign'          => 'Resign',
            'mangkir'         => 'Mangkir',
            'gagal_probation' => 'Gagal Probation',
        ][$row->status] ?? $row->status;

        return [
            $no,
            $row->employee_code,
            $row->nik,
            $row->name,
            $row->department->name ?? '-',
            $row->subDepartment->name ?? '-',
            $row->position->name ?? '-',
            $statusLabel,
            $row->employment_status,
            $row->join_date ? \Carbon\Carbon::parse($row->join_date)->format('d/m/Y') : '-',
            $row->tanggal_resign ? \Carbon\Carbon::parse($row->tanggal_resign)->format('d/m/Y') : '-',
            $row->tanggal_mangkir ? \Carbon\Carbon::parse($row->tanggal_mangkir)->format('d/m/Y') : '-',
            $row->tanggal_gagal_probation ? \Carbon\Carbon::parse($row->tanggal_gagal_probation)->format('d/m/Y') : '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4e73df']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
