<?php

namespace App\Exports;

use App\Models\Karyawans;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KaryawanDataKosongExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public const FIELDS = [
        'name', 'nik', 'gender', 'birth_place', 'birth_date', 'marital_status', 'agama', 'bangsa',
        'status_kependudukan', 'nama_ibu_kandung', 'ktp', 'kartu_keluarga', 'lulusan_sekolah',
        'department_id', 'position_id', 'join_date', 'employment_status', 'work_schedule_id',
        'address', 'city', 'province', 'desa', 'kecamatan', 'kabupaten', 'postal_code', 'phone',
        'email', 'emergency_contact_name', 'emergency_contact_phone', 'bank', 'nomor_rekening',
        'tax_npwp', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan',
    ];

    public function collection()
    {
        return Karyawans::query()
            ->where('status', 'active')
            ->where(function ($query) {
                foreach (self::FIELDS as $field) {
                    $query->orWhereNull($field)->orWhereRaw("TRIM(CAST(`{$field}` AS CHAR)) = ''");
                }
            })
            ->orderBy('employee_code')
            ->get(array_merge(['employee_code'], self::FIELDS));
    }

    public function headings(): array
    {
        return [
            'Kode Karyawan', 'Nama Lengkap', 'NIK', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir',
            'Status Pernikahan', 'Agama', 'Kebangsaan', 'Status Kependudukan', 'Nama Ibu Kandung',
            'Nomor KTP', 'Nomor Kartu Keluarga', 'Pendidikan Terakhir', 'ID Departemen', 'ID Jabatan',
            'Tanggal Masuk', 'Status Kepegawaian', 'ID Jadwal Kerja', 'Alamat Lengkap', 'Kota',
            'Provinsi', 'Desa', 'Kecamatan', 'Kabupaten', 'Kode Pos', 'Nomor HP', 'Email',
            'Nama Kontak Darurat', 'Nomor Kontak Darurat', 'Bank', 'Nomor Rekening', 'NPWP',
            'BPJS Kesehatan', 'BPJS Ketenagakerjaan',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1F4E78']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            ],
        ];
    }

    public function map($employee): array
    {
        return array_merge([$employee->employee_code], array_map(fn ($field) => $employee->{$field}, self::FIELDS));
    }
}