<?php

namespace App\Imports;

use App\Exports\KaryawanDataKosongExport;
use App\Models\Karyawans;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class KaryawanDataKosongImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    use SkipsFailures;

    private const HEADER_MAP = [
        'nama_lengkap' => 'name',
        'jenis_kelamin' => 'gender',
        'tempat_lahir' => 'birth_place',
        'tanggal_lahir' => 'birth_date',
        'status_pernikahan' => 'marital_status',
        'pendidikan_terakhir' => 'lulusan_sekolah',
        'kebangsaan' => 'bangsa',
        'status_kependudukan' => 'status_kependudukan',
        'nama_ibu_kandung' => 'nama_ibu_kandung',
        'nomor_ktp' => 'ktp',
        'nomor_kartu_keluarga' => 'kartu_keluarga',
        'id_departemen' => 'department_id',
        'id_jabatan' => 'position_id',
        'tanggal_masuk' => 'join_date',
        'status_kepegawaian' => 'employment_status',
        'id_jadwal_kerja' => 'work_schedule_id',
        'alamat_lengkap' => 'address',
        'kota' => 'city',
        'provinsi' => 'province',
        'desa' => 'desa',
        'kecamatan' => 'kecamatan',
        'kabupaten' => 'kabupaten',
        'kode_pos' => 'postal_code',
        'nomor_hp' => 'phone',
        'email' => 'email',
        'bank' => 'bank',
        'npwp' => 'tax_npwp',
        'nama_kontak_darurat' => 'emergency_contact_name',
        'nomor_kontak_darurat' => 'emergency_contact_phone',
        'nomor_rekening' => 'nomor_rekening',
        'bpjs_kesehatan' => 'bpjs_kesehatan',
        'bpjs_ketenagakerjaan' => 'bpjs_ketenagakerjaan',
    ];

    public function model(array $row)
    {
        foreach (self::HEADER_MAP as $header => $field) {
            if (array_key_exists($header, $row)) {
                $row[$field] = $row[$header];
            }
        }

        $code = trim((string) ($row['kode_karyawan'] ?? ''));
        if ($code === '') {
            return null;
        }

        $employee = Karyawans::where('employee_code', $code)->where('status', 'active')->first();
        if (!$employee) {
            return null;
        }

        $updates = [];
        foreach (array_combine(KaryawanDataKosongExport::FIELDS, KaryawanDataKosongExport::FIELDS) + [
            'npwp' => 'tax_npwp',
        ] as $column => $field) {
            $rawValue = $row[$column] ?? '';
            $value = is_string($rawValue) ? trim($rawValue) : $rawValue;
            if ($value === null || $value === '' || (is_string($value) && preg_match('/^\s*-\s*$/', $value))) {
                continue;
            }
            if (in_array($field, ['birth_date', 'join_date'], true)) {
                $value = $this->normalizeDate($value);
                if ($value === null) {
                    continue;
                }
            }
            $updates[$field] = $value;
        }

        if ($updates) {
            $employee->update($updates);
        }

        return null;
    }

    private function normalizeDate(mixed $value): ?string
    {
        try {
            return is_numeric($value)
                ? Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->format('Y-m-d')
                : Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function rules(): array
    {
        $rules = ['kode_karyawan' => 'nullable|exists:employees,employee_code'];
        foreach (KaryawanDataKosongExport::FIELDS as $field) {
            $rules[$field] = match ($field) {
                'birth_date', 'join_date' => 'nullable|date',
                'department_id' => 'nullable|integer|exists:departments,id',
                'position_id' => 'nullable|integer|exists:positions,id',
                'work_schedule_id' => 'nullable|integer|exists:work_schedules,id',
                default => 'nullable|max:255',
            };
        }
        $rules['npwp'] = 'nullable|max:255';

        return $rules;
    }

    public function customValidationMessages(): array
    {
        return [
            'kode_karyawan.exists' => 'Kode karyawan pada file tidak ditemukan di database.',
        ];
    }
}