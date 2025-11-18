<?php

namespace App\Exports;

use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Position;
use App\Models\WorkSchedule;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class KaryawanTemplateExport implements WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected $departments;
    protected $subDepartments;
    protected $positions;
    protected $workSchedules;

    public function __construct()
    {
        // Get all departments, sub departments, and active positions
        $this->departments = Department::orderBy('name')
            ->pluck('name')
            ->toArray();

        // Get sub departments with department name for clarity
        $this->subDepartments = SubDepartment::where('is_active', true)
            ->with('department')
            ->orderBy('name')
            ->get()
            ->map(function ($subDept) {
                return $subDept->department->name . ' - ' . $subDept->name;
            })
            ->toArray();

        $this->positions = Position::where('status', 'active')
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        $this->workSchedules = WorkSchedule::where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
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
        // Add example data in row 2
        $sheet->setCellValue('A2', 'EMP001');
        $sheet->setCellValue('B2', '1234567890123456');
        $sheet->setCellValue('C2', 'John Doe');
        $sheet->setCellValue('D2', 'Laki-laki');
        $sheet->setCellValue('E2', 'Jakarta');
        $sheet->setCellValue('F2', '1990-01-15');
        $sheet->setCellValue('G2', 'Menikah');
        $sheet->setCellValue('H2', '2');
        $sheet->setCellValue('I2', 'Islam');
        $sheet->setCellValue('J2', 'Indonesia');
        $sheet->setCellValue('K2', 'WNI');
        $sheet->setCellValue('L2', 'Siti Aminah');
        $sheet->setCellValue('M2', '1234567890123456');
        $sheet->setCellValue('N2', 'IT & Development');
        $sheet->setCellValue('O2', 'IT & Development - Development');
        $sheet->setCellValue('P2', 'Staff IT');
        $sheet->setCellValue('Q2', 'S1 Informatika');
        $sheet->setCellValue('R2', '2020-01-01');
        $sheet->setCellValue('S2', 'Tetap');
        $sheet->setCellValue('T2', 'Shift Pagi');
        $sheet->setCellValue('U2', '');
        $sheet->setCellValue('V2', 'BCA');
        $sheet->setCellValue('W2', '1234567890');
        $sheet->setCellValue('X2', '12.345.678.9-012.000');
        $sheet->setCellValue('Y2', '0001234567890');
        $sheet->setCellValue('Z2', '0001234567890');
        $sheet->setCellValue('AA2', 'Aktif');
        $sheet->setCellValue('AB2', 'Jl. Contoh No. 123');
        $sheet->setCellValue('AC2', 'Jakarta Selatan');
        $sheet->setCellValue('AD2', 'DKI Jakarta');
        $sheet->setCellValue('AE2', '12345');
        $sheet->setCellValue('AF2', '081234567890');
        $sheet->setCellValue('AG2', 'john.doe@example.com');
        $sheet->setCellValue('AH2', 'Jane Doe');
        $sheet->setCellValue('AI2', '081234567891');

        // Add notes in row 3
        $sheet->setCellValue('A3', 'Contoh: EMP002');
        $sheet->setCellValue('D3', 'Pilih dari dropdown ⬇');
        $sheet->setCellValue('F3', 'Format: YYYY-MM-DD');
        $sheet->setCellValue('G3', 'Pilih dari dropdown ⬇');
        $sheet->setCellValue('H3', 'Angka, contoh: 0, 1, 2');
        $sheet->setCellValue('I3', 'Islam/Kristen/Katolik/Hindu/Buddha/Konghucu');
        $sheet->setCellValue('K3', 'WNI/WNA');
        $sheet->setCellValue('N3', 'Pilih dari dropdown ⬇');
        $sheet->setCellValue('O3', 'Pilih dari dropdown ⬇ (opsional)');
        $sheet->setCellValue('P3', 'Pilih dari dropdown ⬇');
        $sheet->setCellValue('R3', 'Format: YYYY-MM-DD');
        $sheet->setCellValue('S3', 'Pilih dari dropdown ⬇');
        $sheet->setCellValue('T3', 'Pilih dari dropdown ⬇');
        $sheet->setCellValue('U3', 'Kosongkan jika belum resign');
        $sheet->setCellValue('AA3', 'Pilih dari dropdown ⬇');
        $sheet->setCellValue('AG3', 'Harus unique');

        return [
            // Style header row
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4CAF50']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
            // Style example row
            2 => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8F5E9']
                ],
            ],
            // Style notes row
            3 => [
                'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '666666']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFF9C4']
                ],
            ],
        ];
    }

    /**
     * Register events untuk menambahkan data validation (dropdown)
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Set dropdown untuk Jenis Kelamin (Column D) - dari row 2 sampai 1000
                $genderValidation = $sheet->getCell('D2')->getDataValidation();
                $genderValidation->setType(DataValidation::TYPE_LIST);
                $genderValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $genderValidation->setAllowBlank(false);
                $genderValidation->setShowInputMessage(true);
                $genderValidation->setShowErrorMessage(true);
                $genderValidation->setShowDropDown(true);
                $genderValidation->setErrorTitle('Input error');
                $genderValidation->setError('Pilih dari dropdown');
                $genderValidation->setPromptTitle('Jenis Kelamin');
                $genderValidation->setPrompt('Pilih Laki-laki atau Perempuan');
                $genderValidation->setFormula1('"Laki-laki,Perempuan"');

                // Copy validation ke row lainnya
                for ($i = 2; $i <= 1000; $i++) {
                    $sheet->getCell('D' . $i)->setDataValidation(clone $genderValidation);
                }

                // Set dropdown untuk Status Perkawinan (Column G)
                $maritalValidation = $sheet->getCell('G2')->getDataValidation();
                $maritalValidation->setType(DataValidation::TYPE_LIST);
                $maritalValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $maritalValidation->setAllowBlank(false);
                $maritalValidation->setShowInputMessage(true);
                $maritalValidation->setShowErrorMessage(true);
                $maritalValidation->setShowDropDown(true);
                $maritalValidation->setErrorTitle('Input error');
                $maritalValidation->setError('Pilih dari dropdown');
                $maritalValidation->setPromptTitle('Status Perkawinan');
                $maritalValidation->setPrompt('Pilih status perkawinan');
                $maritalValidation->setFormula1('"Belum Menikah,Menikah,Duda,Janda"');

                for ($i = 2; $i <= 1000; $i++) {
                    $sheet->getCell('G' . $i)->setDataValidation(clone $maritalValidation);
                }

                // Set dropdown untuk Departemen (Column N) - dari database
                if (count($this->departments) > 0) {
                    $departmentList = '"' . implode(',', $this->departments) . '"';
                    $deptValidation = $sheet->getCell('N2')->getDataValidation();
                    $deptValidation->setType(DataValidation::TYPE_LIST);
                    $deptValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $deptValidation->setAllowBlank(false);
                    $deptValidation->setShowInputMessage(true);
                    $deptValidation->setShowErrorMessage(true);
                    $deptValidation->setShowDropDown(true);
                    $deptValidation->setErrorTitle('Input error');
                    $deptValidation->setError('Pilih departemen yang valid dari dropdown');
                    $deptValidation->setPromptTitle('Departemen');
                    $deptValidation->setPrompt('Pilih departemen yang sudah terdaftar');
                    $deptValidation->setFormula1($departmentList);

                    for ($i = 2; $i <= 1000; $i++) {
                        $sheet->getCell('N' . $i)->setDataValidation(clone $deptValidation);
                    }
                }

                // Set dropdown untuk Sub Departemen (Column O) - dari database (optional)
                if (count($this->subDepartments) > 0) {
                    $subDeptList = '"' . implode(',', $this->subDepartments) . '"';
                    $subDeptValidation = $sheet->getCell('O2')->getDataValidation();
                    $subDeptValidation->setType(DataValidation::TYPE_LIST);
                    $subDeptValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $subDeptValidation->setAllowBlank(true); // Allow blank karena optional
                    $subDeptValidation->setShowInputMessage(true);
                    $subDeptValidation->setShowErrorMessage(true);
                    $subDeptValidation->setShowDropDown(true);
                    $subDeptValidation->setErrorTitle('Input error');
                    $subDeptValidation->setError('Pilih sub departemen yang valid dari dropdown');
                    $subDeptValidation->setPromptTitle('Sub Departemen');
                    $subDeptValidation->setPrompt('Pilih sub departemen (format: Departemen - Sub Departemen). Kosongkan jika tidak ada.');
                    $subDeptValidation->setFormula1($subDeptList);

                    for ($i = 2; $i <= 1000; $i++) {
                        $sheet->getCell('O' . $i)->setDataValidation(clone $subDeptValidation);
                    }
                }

                // Set dropdown untuk Posisi/Jabatan (Column P) - dari database
                if (count($this->positions) > 0) {
                    $positionList = '"' . implode(',', $this->positions) . '"';
                    $posValidation = $sheet->getCell('P2')->getDataValidation();
                    $posValidation->setType(DataValidation::TYPE_LIST);
                    $posValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $posValidation->setAllowBlank(false);
                    $posValidation->setShowInputMessage(true);
                    $posValidation->setShowErrorMessage(true);
                    $posValidation->setShowDropDown(true);
                    $posValidation->setErrorTitle('Input error');
                    $posValidation->setError('Pilih posisi yang valid dari dropdown');
                    $posValidation->setPromptTitle('Posisi/Jabatan');
                    $posValidation->setPrompt('Pilih posisi yang sudah terdaftar');
                    $posValidation->setFormula1($positionList);

                    for ($i = 2; $i <= 1000; $i++) {
                        $sheet->getCell('P' . $i)->setDataValidation(clone $posValidation);
                    }
                }

                // Set dropdown untuk Status Kerja (Column S)
                $empStatusValidation = $sheet->getCell('S2')->getDataValidation();
                $empStatusValidation->setType(DataValidation::TYPE_LIST);
                $empStatusValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $empStatusValidation->setAllowBlank(false);
                $empStatusValidation->setShowInputMessage(true);
                $empStatusValidation->setShowErrorMessage(true);
                $empStatusValidation->setShowDropDown(true);
                $empStatusValidation->setErrorTitle('Input error');
                $empStatusValidation->setError('Pilih dari dropdown');
                $empStatusValidation->setPromptTitle('Status Kerja');
                $empStatusValidation->setPrompt('Pilih status kerja karyawan');
                $empStatusValidation->setFormula1('"Tetap,Kontrak,Probation"');

                for ($i = 2; $i <= 1000; $i++) {
                    $sheet->getCell('S' . $i)->setDataValidation(clone $empStatusValidation);
                }

                // Set dropdown untuk Jadwal Kerja (Column T) - dari database
                if (!empty($this->workSchedules)) {
                    $scheduleValidation = $sheet->getCell('T2')->getDataValidation();
                    $scheduleValidation->setType(DataValidation::TYPE_LIST);
                    $scheduleValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $scheduleValidation->setAllowBlank(false);
                    $scheduleValidation->setShowInputMessage(true);
                    $scheduleValidation->setShowErrorMessage(true);
                    $scheduleValidation->setShowDropDown(true);
                    $scheduleValidation->setErrorTitle('Input error');
                    $scheduleValidation->setError('Pilih dari dropdown');
                    $scheduleValidation->setPromptTitle('Jadwal Kerja');
                    $scheduleValidation->setPrompt('Pilih jadwal kerja dari list');
                    $scheduleValidation->setFormula1('"' . implode(',', $this->workSchedules) . '"');

                    for ($i = 2; $i <= 1000; $i++) {
                        $sheet->getCell('T' . $i)->setDataValidation(clone $scheduleValidation);
                    }
                }

                // Set dropdown untuk Status Karyawan (Column AA)
                $statusValidation = $sheet->getCell('AA2')->getDataValidation();
                $statusValidation->setType(DataValidation::TYPE_LIST);
                $statusValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $statusValidation->setAllowBlank(false);
                $statusValidation->setShowInputMessage(true);
                $statusValidation->setShowErrorMessage(true);
                $statusValidation->setShowDropDown(true);
                $statusValidation->setErrorTitle('Input error');
                $statusValidation->setError('Pilih dari dropdown');
                $statusValidation->setPromptTitle('Status Karyawan');
                $statusValidation->setPrompt('Pilih status aktif karyawan');
                $statusValidation->setFormula1('"Aktif,Tidak Aktif,Resign"');

                for ($i = 2; $i <= 1000; $i++) {
                    $sheet->getCell('AA' . $i)->setDataValidation(clone $statusValidation);
                }
            },
        ];
    }
}
