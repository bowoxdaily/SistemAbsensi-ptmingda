<?php

namespace App\Exports;

use App\Models\Karyawans;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class KaryawanDataKosongExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Karyawans::query()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('bank')->orWhere('bank', '')
                    ->orWhereNull('nomor_rekening')->orWhere('nomor_rekening', '')
                    ->orWhereNull('tax_npwp')->orWhere('tax_npwp', '')
                    ->orWhereNull('bpjs_kesehatan')->orWhere('bpjs_kesehatan', '')
                    ->orWhereNull('bpjs_ketenagakerjaan')->orWhere('bpjs_ketenagakerjaan', '');
            })
            ->orderBy('employee_code')
            ->get(['employee_code', 'name', 'bank', 'nomor_rekening', 'tax_npwp', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan']);
    }

    public function headings(): array
    {
        return ['Kode Karyawan', 'Nama Karyawan', 'Bank', 'Nomor Rekening', 'NPWP', 'BPJS Kesehatan', 'BPJS Ketenagakerjaan'];
    }

    public function map($employee): array
    {
        return [$employee->employee_code, $employee->name, $employee->bank, $employee->nomor_rekening,
            $employee->tax_npwp, $employee->bpjs_kesehatan, $employee->bpjs_ketenagakerjaan];
    }
}