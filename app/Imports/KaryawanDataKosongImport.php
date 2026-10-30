<?php

namespace App\Imports;

use App\Models\Karyawans;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class KaryawanDataKosongImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    use SkipsFailures;

    public function model(array $row)
    {
        $code = trim((string) ($row['kode_karyawan'] ?? ''));
        if ($code === '') {
            return null;
        }

        $employee = Karyawans::where('employee_code', $code)->where('status', 'active')->first();
        if (!$employee) {
            return null;
        }

        $updates = [];
        foreach ([
            'bank' => 'bank',
            'nomor_rekening' => 'nomor_rekening',
            'npwp' => 'tax_npwp',
            'tax_npwp' => 'tax_npwp',
            'bpjs_kesehatan' => 'bpjs_kesehatan',
            'bpjs_ketenagakerjaan' => 'bpjs_ketenagakerjaan',
        ] as $column => $field) {
            $value = trim((string) ($row[$column] ?? ''));
            if ($value !== '' && $value !== '-') {
                $updates[$field] = $value;
            }
        }

        if ($updates) {
            $employee->update($updates);
        }

        return null;
    }

    public function rules(): array
    {
        return [
            'kode_karyawan' => 'nullable|exists:employees,employee_code',
            'bank' => 'nullable|max:100',
            'nomor_rekening' => 'nullable|max:100',
            'npwp' => 'nullable|max:100',
            'tax_npwp' => 'nullable|max:100',
            'bpjs_kesehatan' => 'nullable|max:100',
            'bpjs_ketenagakerjaan' => 'nullable|max:100',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'kode_karyawan.exists' => 'Kode karyawan pada file tidak ditemukan di database.',
        ];
    }
}