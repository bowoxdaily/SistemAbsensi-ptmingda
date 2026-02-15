<?php

namespace App\Exports;

use App\Models\Position;
use App\Models\InterviewMessageTemplate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Collection;

class InterviewTemplateExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected $positions;
    protected $templates;

    public function __construct()
    {
        // Get active positions
        $this->positions = Position::where('status', 'active')
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        // Get active message templates
        $this->templates = InterviewMessageTemplate::where('is_active', true)
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
            'John Doe',                    // Nama Kandidat
            '08123456789',                 // No. HP
            'johndoe@example.com',         // Email
            !empty($this->positions) ? $this->positions[0] : 'Staff IT',  // Posisi
            '2026-02-20',                  // Tanggal Interview (YYYY-MM-DD)
            '09:00',                       // Waktu Interview (HH:MM)
            'Kantor Pusat PT Mingda, Ruang Meeting Lt. 2',  // Lokasi
            'Mohon membawa CV dan portofolio',  // Catatan (optional)
            '',  // Template Notifikasi (optional - kosongkan untuk pakai default)
        ];

        // Create 20 empty rows for data entry
        $rows = collect([$exampleRow]);
        for ($i = 0; $i < 20; $i++) {
            $rows->push([
                '',  // Nama Kandidat
                '',  // No. HP
                '',  // Email
                '',  // Posisi
                '',  // Tanggal Interview
                '',  // Waktu Interview
                '',  // Lokasi
                '',  // Catatan
                '',  // Template Notifikasi
            ]);
        }

        return $rows;
    }

    /**
     * Column headings
     */
    public function headings(): array
    {
        return [
            'Nama Kandidat',
            'No. HP',
            'Email',
            'Posisi',
            'Tanggal Interview',
            'Waktu Interview',
            'Lokasi',
            'Catatan',
            'Template Notifikasi',
        ];
    }

    /**
     * Set column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 25,  // Nama Kandidat
            'B' => 18,  // No. HP
            'C' => 30,  // Email
            'D' => 20,  // Posisi
            'E' => 20,  // Tanggal Interview
            'F' => 18,  // Waktu Interview
            'G' => 40,  // Lokasi
            'H' => 35,  // Catatan
            'I' => 50,  // Template Notifikasi
        ];
    }

    /**
     * Apply styles to the sheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
            // Example row styling (row 2)
            2 => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFF2CC'],
                ],
                'font' => [
                    'italic' => true,
                    'color' => ['rgb' => '666666'],
                ],
            ],
        ];
    }

    /**
     * Register events for dropdown validations
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Set row height for header
                $sheet->getRowDimension(1)->setRowHeight(25);
                
                // Auto-filter
                $sheet->setAutoFilter('A1:I1');

                // Add dropdown for Posisi column (D)
                if (!empty($this->positions)) {
                    $positionList = '"' . implode(',', $this->positions) . '"';
                    for ($row = 2; $row <= 21; $row++) {
                        $validation = $sheet->getCell("D{$row}")->getDataValidation();
                        $validation->setType(DataValidation::TYPE_LIST);
                        $validation->setErrorStyle(DataValidation::STYLE_STOP);
                        $validation->setAllowBlank(false);
                        $validation->setShowInputMessage(true);
                        $validation->setShowErrorMessage(true);
                        $validation->setShowDropDown(true);
                        $validation->setErrorTitle('Input Error');
                        $validation->setError('Pilih posisi dari dropdown');
                        $validation->setPromptTitle('Posisi');
                        $validation->setPrompt('Pilih posisi yang tersedia');
                        $validation->setFormula1($positionList);
                    }
                }

                // Add dropdown for Template Notifikasi column (I)
                if (!empty($this->templates)) {
                    $templateList = '"' . implode(',', $this->templates) . '"';
                    for ($row = 2; $row <= 21; $row++) {
                        $validation = $sheet->getCell("I{$row}")->getDataValidation();
                        $validation->setType(DataValidation::TYPE_LIST);
                        $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                        $validation->setAllowBlank(true);
                        $validation->setShowInputMessage(true);
                        $validation->setShowErrorMessage(true);
                        $validation->setShowDropDown(true);
                        $validation->setErrorTitle('Info');
                        $validation->setError('Pilih template dari dropdown atau kosongkan untuk pakai default');
                        $validation->setPromptTitle('Template Notifikasi');
                        $validation->setPrompt('Pilih template pesan WhatsApp yang tersedia. Kosongkan untuk pakai template default.');
                        $validation->setFormula1($templateList);
                    }
                }

                // Add border to all data cells
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                ];
                $sheet->getStyle('A2:I21')->applyFromArray($styleArray);

                // Add note at the bottom
                $sheet->setCellValue('A23', 'CATATAN PENTING:');
                $sheet->setCellValue('A24', '1. Baris ke-2 (warna kuning) adalah CONTOH - akan diabaikan otomatis saat import');
                $sheet->setCellValue('A25', '2. Mulai isi data dari BARIS KE-3 dan seterusnya');
                $sheet->setCellValue('A26', '3. Kolom WAJIB diisi: Nama Kandidat, No. HP, Posisi, Tanggal Interview, Waktu Interview');
                $sheet->setCellValue('A27', '4. Kolom OPSIONAL: Email, Lokasi (default: Kantor PT Mingda), Catatan, Template Notifikasi');
                $sheet->setCellValue('A28', '5. Format tanggal: YYYY-MM-DD, M/D/YYYY, atau biarkan Excel auto-format (contoh: 2026-02-20, 2/15/2026)');
                $sheet->setCellValue('A29', '6. Format waktu: HH:MM atau angka jam (contoh: 09:00, 9:30, atau 9 untuk 09:00). Biarkan Excel format otomatis.');
                $sheet->setCellValue('A30', '7. No. HP akan otomatis diformat menjadi 628xxx (bisa tulis 08xxx)');
                $sheet->setCellValue('A31', '8. Template Notifikasi: Pilih dari dropdown template yang tersedia atau kosongkan untuk pakai template default.');
                $sheet->setCellValue('A32', '9. Hapus baris yang tidak digunakan atau biarkan kosong - akan di-skip otomatis');
                
                $sheet->getStyle('A23:A32')->getFont()->setSize(9)->setItalic(true);
                $sheet->getStyle('A23')->getFont()->setBold(true);
                $sheet->getStyle('A23:A32')->getAlignment()->setWrapText(true);
            },
        ];
    }
}
