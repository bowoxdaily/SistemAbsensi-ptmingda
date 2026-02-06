<?php

namespace App\Exports;

use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Position;
use App\Models\WorkSchedule;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Collection;

class KaryawanTemplateExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithEvents
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
     * Generate collection with example row and empty rows
     */
    public function collection()
    {
        // Create example row
        $exampleRow = [
            'EMP001',  // Kode Karyawan
            '1234567890123456',  // NIK
            'John Doe',  // Nama Lengkap
            'Laki-laki',  // Jenis Kelamin
            'Jakarta',  // Tempat Lahir
            '1990-01-15',  // Tanggal Lahir
            'Menikah',  // Status Perkawinan
            '2',  // Tanggungan Anak
            'Islam',  // Agama
            'Indonesia',  // Bangsa
            'WNI',  // Status Kependudukan
            'Siti Aminah',  // Nama Ibu Kandung
            '1234567890123456',  // Kartu Keluarga
            !empty($this->departments) ? $this->departments[0] : 'IT & Development',  // Departemen
            !empty($this->subDepartments) ? $this->subDepartments[0] : '',  // Sub Departemen
            !empty($this->positions) ? $this->positions[0] : 'Staff IT',  // Posisi
            'S1 Informatika',  // Lulusan Sekolah
            '2023-01-10',  // Tanggal Bergabung
            'Tetap',  // Status Kerja
            'Non Serikat',  // Status Serikat
            !empty($this->workSchedules) ? $this->workSchedules[0] : 'Shift Pagi',  // Jadwal Kerja
            '',  // Tanggal Resign
            'BCA',  // Bank
            '1234567890',  // Nomor Rekening
            '12.345.678.9-012.000',  // NPWP
            '0001234567890',  // BPJS Kesehatan
            '0001234567890',  // BPJS Ketenagakerjaan
            'Aktif',  // Status
            'Jl. Contoh No. 123',  // Alamat
            'Jakarta Selatan',  // Kota
            'DKI Jakarta',  // Provinsi
            '12345',  // Kode Pos
            '081234567890',  // No. HP
            'john.doe@example.com',  // Email
            'Jane Doe',  // Kontak Darurat (Nama)
            '081234567891',  // Kontak Darurat (No)
        ];

        // Create collection with example row and 10 empty rows for data entry
        $collection = new Collection();
        $collection->push($exampleRow);
        
        // Add 10 empty rows for user to fill
        for ($i = 0; $i < 10; $i++) {
            $collection->push(array_fill(0, 36, ''));
        }

        return $collection;
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
            'Status Serikat',
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
            'T' => 18,  // Status Serikat
            'U' => 18,  // Jadwal Kerja
            'V' => 15,  // Tanggal Resign
            'W' => 20,  // Bank
            'X' => 20,  // Nomor Rekening
            'Y' => 20,  // NPWP
            'Z' => 20,  // BPJS Kesehatan
            'AA' => 20,  // BPJS Ketenagakerjaan
            'AB' => 12, // Status
            'AC' => 35, // Alamat
            'AD' => 15, // Kota
            'AE' => 15, // Provinsi
            'AF' => 12, // Kode Pos
            'AG' => 15, // No HP
            'AH' => 25, // Email
            'AI' => 25, // Kontak Darurat Nama
            'AJ' => 15, // Kontak Darurat No
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
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
                $spreadsheet = $sheet->getParent();

                // Create a hidden sheet for dropdown data to avoid 255 char limit
                $dataSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, '_DropdownData');
                $spreadsheet->addSheet($dataSheet);
                $dataSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

                // Add dropdown data to hidden sheet
                // Departments
                $deptRow = 1;
                if (!empty($this->departments)) {
                    foreach ($this->departments as $dept) {
                        $dataSheet->setCellValue('A' . $deptRow, $dept);
                        $deptRow++;
                    }
                }

                // Sub Departments
                $subDeptRow = 1;
                if (!empty($this->subDepartments)) {
                    foreach ($this->subDepartments as $subDept) {
                        $dataSheet->setCellValue('B' . $subDeptRow, $subDept);
                        $subDeptRow++;
                    }
                }

                // Positions
                $posRow = 1;
                if (!empty($this->positions)) {
                    foreach ($this->positions as $pos) {
                        $dataSheet->setCellValue('C' . $posRow, $pos);
                        $posRow++;
                    }
                }

                // Work Schedules
                $scheduleRow = 1;
                if (!empty($this->workSchedules)) {
                    foreach ($this->workSchedules as $schedule) {
                        $dataSheet->setCellValue('D' . $scheduleRow, $schedule);
                        $scheduleRow++;
                    }
                }

                // Set dropdown untuk Jenis Kelamin (Column D)
                $validation = $sheet->getCell('D2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Pilih dari dropdown');
                $validation->setPromptTitle('Jenis Kelamin');
                $validation->setPrompt('Pilih Laki-laki atau Perempuan');
                $validation->setFormula1('"Laki-laki,Perempuan"');
                
                for ($i = 3; $i <= 100; $i++) {
                    $sheet->getCell('D' . $i)->setDataValidation(clone $validation);
                }

                // Set dropdown untuk Status Perkawinan (Column G)
                $validation = $sheet->getCell('G2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Pilih dari dropdown');
                $validation->setPromptTitle('Status Perkawinan');
                $validation->setPrompt('Pilih status perkawinan');
                $validation->setFormula1('"Belum Menikah,Menikah,Duda,Janda"');
                
                for ($i = 3; $i <= 100; $i++) {
                    $sheet->getCell('G' . $i)->setDataValidation(clone $validation);
                }

                // Set dropdown untuk Departemen (Column N)
                if (!empty($this->departments) && $deptRow > 1) {
                    $validation = $sheet->getCell('N2')->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(false);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Input error');
                    $validation->setError('Pilih departemen yang valid');
                    $validation->setPromptTitle('Departemen');
                    $validation->setPrompt('Pilih departemen dari list');
                    $validation->setFormula1('_DropdownData!$A$1:$A$' . ($deptRow - 1));
                    
                    for ($i = 3; $i <= 100; $i++) {
                        $sheet->getCell('N' . $i)->setDataValidation(clone $validation);
                    }
                }

                // Set dropdown untuk Sub Departemen (Column O)
                if (!empty($this->subDepartments) && $subDeptRow > 1) {
                    $validation = $sheet->getCell('O2')->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true); // Allow blank - optional
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Input error');
                    $validation->setError('Pilih sub departemen yang valid');
                    $validation->setPromptTitle('Sub Departemen');
                    $validation->setPrompt('Pilih sub departemen (opsional)');
                    $validation->setFormula1('_DropdownData!$B$1:$B$' . ($subDeptRow - 1));
                    
                    for ($i = 3; $i <= 100; $i++) {
                        $sheet->getCell('O' . $i)->setDataValidation(clone $validation);
                    }
                }

                // Set dropdown untuk Posisi/Jabatan (Column P)
                if (!empty($this->positions) && $posRow > 1) {
                    $validation = $sheet->getCell('P2')->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(false);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Input error');
                    $validation->setError('Pilih posisi yang valid');
                    $validation->setPromptTitle('Posisi/Jabatan');
                    $validation->setPrompt('Pilih posisi dari list');
                    $validation->setFormula1('_DropdownData!$C$1:$C$' . ($posRow - 1));
                    
                    for ($i = 3; $i <= 100; $i++) {
                        $sheet->getCell('P' . $i)->setDataValidation(clone $validation);
                    }
                }

                // Set dropdown untuk Status Kerja (Column S)
                $validation = $sheet->getCell('S2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Pilih dari dropdown');
                $validation->setPromptTitle('Status Kerja');
                $validation->setPrompt('Pilih status kerja');
                $validation->setFormula1('"Tetap,Kontrak,Probation"');
                
                for ($i = 3; $i <= 100; $i++) {
                    $sheet->getCell('S' . $i)->setDataValidation(clone $validation);
                }

                // Set dropdown untuk Status Serikat (Column T)
                $validation = $sheet->getCell('T2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Pilih dari dropdown');
                $validation->setPromptTitle('Status Serikat');
                $validation->setPrompt('Pilih status serikat');
                $validation->setFormula1('"Serikat GARTEKS,Non Serikat"');
                
                for ($i = 3; $i <= 100; $i++) {
                    $sheet->getCell('T' . $i)->setDataValidation(clone $validation);
                }

                // Set dropdown untuk Jadwal Kerja (Column U)
                if (!empty($this->workSchedules) && $scheduleRow > 1) {
                    $validation = $sheet->getCell('U2')->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(false);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Input error');
                    $validation->setError('Pilih jadwal kerja yang valid');
                    $validation->setPromptTitle('Jadwal Kerja');
                    $validation->setPrompt('Pilih jadwal kerja dari list');
                    $validation->setFormula1('_DropdownData!$D$1:$D$' . ($scheduleRow - 1));
                    
                    for ($i = 3; $i <= 100; $i++) {
                        $sheet->getCell('U' . $i)->setDataValidation(clone $validation);
                    }
                }

                // Set dropdown untuk Status Karyawan (Column AB)
                $validation = $sheet->getCell('AB2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Input error');
                $validation->setError('Pilih dari dropdown');
                $validation->setPromptTitle('Status Karyawan');
                $validation->setPrompt('Pilih status karyawan');
                $validation->setFormula1('"Aktif,Tidak Aktif,Resign"');
                
                for ($i = 3; $i <= 100; $i++) {
                    $sheet->getCell('AB' . $i)->setDataValidation(clone $validation);
                }

                // Add comment/note to first data row for guidance
                $sheet->getComment('A3')->getText()->createTextRun('Hapus baris contoh (baris 2) sebelum import. Mulai isi data dari baris ini.');
                $sheet->getComment('A3')->setWidth('250pt');
                $sheet->getComment('A3')->setHeight('50pt');
            },
        ];
    }
}
