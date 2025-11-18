<?php

namespace App\Exports;

use App\Models\Karyawans;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KaryawanExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
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
        return Karyawans::with(['department', 'subDepartment', 'position', 'workSchedule'])
            ->when(!empty($this->filters['search']), function ($query) {
                $search = $this->filters['search'];
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(!empty($this->filters['department_id']), function ($query) {
                return $query->where('department_id', $this->filters['department_id']);
            })
            ->when(!empty($this->filters['position_id']), function ($query) {
                return $query->where('position_id', $this->filters['position_id']);
            })
            ->when(!empty($this->filters['status']), function ($query) {
                return $query->where('status', $this->filters['status']);
            })
            ->orderBy('employee_code', 'asc')
            ->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Kode Karyawan',
            'NIK',
            'Nama Lengkap',
            'Jenis Kelamin',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Status Perkawinan',
            'Tanggungan Anak',
            'Agama',
            'Bangsa',
            'Status Kependudukan',
            'Nama Ibu Kandung',
            'Kartu Keluarga',
            'Departemen',
            'Sub Departemen',
            'Posisi',
            'Lulusan Sekolah',
            'Tanggal Bergabung',
            'Status Kerja',
            'Jadwal Kerja',
            'Tanggal Resign',
            'Bank',
            'Nomor Rekening',
            'NPWP',
            'BPJS Kesehatan',
            'BPJS Ketenagakerjaan',
            'Status',
            'Alamat',
            'Kota',
            'Provinsi',
            'Kode Pos',
            'No. HP',
            'Email',
            'Kontak Darurat (Nama)',
            'Kontak Darurat (No)',
        ];
    }

    /**
     * @param mixed $karyawan
     * @return array
     */
    public function map($karyawan): array
    {
        return [
            $karyawan->employee_code,
            $karyawan->nik ?? '-',
            $karyawan->name,
            $karyawan->gender === 'L' ? 'Laki-laki' : 'Perempuan',
            $karyawan->birth_place,
            $karyawan->birth_date ? $karyawan->birth_date->format('Y-m-d') : '-',
            $karyawan->marital_status,
            $karyawan->tanggungan_anak ?? 0,
            $karyawan->agama ?? '-',
            $karyawan->bangsa ?? '-',
            $karyawan->status_kependudukan ?? '-',
            $karyawan->nama_ibu_kandung ?? '-',
            $karyawan->kartu_keluarga ?? '-',
            $karyawan->department ? $karyawan->department->name : '-',
            $karyawan->subDepartment ? $karyawan->subDepartment->name : '-',
            $karyawan->position ? $karyawan->position->name : '-',
            $karyawan->lulusan_sekolah ?? '-',
            $karyawan->join_date ? $karyawan->join_date->format('Y-m-d') : '-',
            $karyawan->employment_status,
            $karyawan->workSchedule ? $karyawan->workSchedule->name : '-',
            $karyawan->tanggal_resign ? $karyawan->tanggal_resign->format('Y-m-d') : '-',
            $karyawan->bank ?? '-',
            $karyawan->nomor_rekening ?? '-',
            $karyawan->tax_npwp ?? '-',
            $karyawan->bpjs_kesehatan ?? '-',
            $karyawan->bpjs_ketenagakerjaan ?? '-',
            $this->getStatusLabel($karyawan->status),
            $karyawan->address,
            $karyawan->city,
            $karyawan->province,
            $karyawan->postal_code,
            $karyawan->phone,
            $karyawan->email,
            $karyawan->emergency_contact_name,
            $karyawan->emergency_contact_phone,
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Kode Karyawan
            'B' => 18,  // NIK
            'C' => 25,  // Nama
            'D' => 15,  // Jenis Kelamin
            'E' => 20,  // Tempat Lahir
            'F' => 15,  // Tanggal Lahir
            'G' => 18,  // Status Perkawinan
            'H' => 15,  // Tanggungan Anak
            'I' => 15,  // Agama
            'J' => 15,  // Bangsa
            'K' => 20,  // Status Kependudukan
            'L' => 25,  // Nama Ibu Kandung
            'M' => 18,  // Kartu Keluarga
            'N' => 20,  // Departemen
            'O' => 20,  // Sub Departemen
            'P' => 20,  // Posisi
            'Q' => 20,  // Lulusan Sekolah
            'R' => 18,  // Tanggal Bergabung
            'S' => 15,  // Status Kerja
            'T' => 18,  // Jadwal Kerja
            'U' => 15,  // Tanggal Resign
            'V' => 20,  // Bank
            'W' => 20,  // Nomor Rekening
            'X' => 20,  // NPWP
            'Y' => 20,  // BPJS Kesehatan
            'Z' => 20,  // BPJS Ketenagakerjaan
            'AA' => 12, // Status
            'AB' => 35, // Alamat
            'AC' => 15, // Kota
            'AD' => 15, // Provinsi
            'AE' => 12, // Kode Pos
            'AF' => 15, // No HP
            'AG' => 25, // Email
            'AH' => 25, // Kontak Darurat Nama
            'AI' => 15, // Kontak Darurat No
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
     * Get status label
     */
    private function getStatusLabel($status)
    {
        $labels = [
            'active' => 'Aktif',
            'inactive' => 'Tidak Aktif',
            'resign' => 'Resign'
        ];
        return $labels[$status] ?? $status;
    }
}
