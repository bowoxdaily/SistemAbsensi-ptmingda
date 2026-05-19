<?php

namespace App\Exports;

use App\Models\Employee;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BirthdayEmployeeExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private readonly int $year,
        private readonly int $month
    ) {
    }

    public function collection()
    {
        $daysInSelectedMonth = Carbon::create($this->year, $this->month, 1)->daysInMonth;

        return Employee::query()
            ->with(['subDepartment:id,name', 'position:id,name'])
            ->where('status', 'active')
            ->whereNotNull('birth_date')
            ->whereMonth('birth_date', $this->month)
            ->orderByRaw('DAY(birth_date) ASC')
            ->orderBy('name')
            ->get()
            ->map(function (Employee $employee) use ($daysInSelectedMonth) {
                $birthDate = Carbon::parse($employee->birth_date);
                $day = min((int) $birthDate->day, $daysInSelectedMonth);
                $birthdayThisMonth = Carbon::create($this->year, $this->month, $day);

                return [
                    'employee_code' => $employee->employee_code,
                    'name' => $employee->name,
                    'sub_department' => optional($employee->subDepartment)->name,
                    'position' => optional($employee->position)->name,
                    'birth_date' => $birthDate->format('Y-m-d'),
                    'birthday_this_month' => $birthdayThisMonth->format('Y-m-d'),
                    'age' => max(0, $this->year - (int) $birthDate->year),
                    'phone' => $employee->phone,
                    'email' => $employee->email,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Kode Karyawan',
            'Nama Karyawan',
            'Sub Departemen',
            'Jabatan',
            'Tanggal Lahir',
            'Tanggal Ulang Tahun (Bulan Terpilih)',
            'Usia',
            'No. HP',
            'Email',
        ];
    }

    public function map($row): array
    {
        return [
            $row['employee_code'] ?? '-',
            $row['name'] ?? '-',
            $row['sub_department'] ?? '-',
            $row['position'] ?? '-',
            $row['birth_date'] ?? '-',
            $row['birthday_this_month'] ?? '-',
            ($row['age'] ?? 0) . ' tahun',
            $row['phone'] ?? '-',
            $row['email'] ?? '-',
        ];
    }
}
