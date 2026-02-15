<?php

namespace App\Imports;

use App\Models\Interview;
use App\Models\Position;
use App\Models\InterviewMessageTemplate;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;

class InterviewImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    protected $positionCache = [];
    protected $templateCache = [];

    public function __construct()
    {
        // Cache positions to avoid repeated queries (case-insensitive)
        $positions = Position::where('status', 'active')->get();
        foreach ($positions as $position) {
            $this->positionCache[strtolower(trim($position->name))] = $position->id;
        }

        // Cache message templates (case-insensitive)
        $templates = InterviewMessageTemplate::where('is_active', true)->get();
        foreach ($templates as $template) {
            $this->templateCache[strtolower(trim($template->name))] = $template->message_template;
        }
    }

    /**
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Skip example row (case insensitive)
        if (isset($row['nama_kandidat']) && in_array(strtoupper(trim($row['nama_kandidat'])), ['JOHN DOE', 'CONTOH'])) {
            return null;
        }

        // Skip empty rows - check multiple fields
        $isEmpty = empty(trim($row['nama_kandidat'] ?? '')) 
                && empty(trim($row['no_hp'] ?? ''))
                && empty(trim($row['posisi'] ?? ''));
        
        if ($isEmpty) {
            return null;
        }

        // Also skip if only nama filled but no other required fields
        if (!empty($row['nama_kandidat']) && empty($row['no_hp']) && empty($row['posisi'])) {
            return null;
        }

        // Validate required fields manually
        if (empty(trim($row['nama_kandidat'] ?? ''))) {
            throw new \Exception("Nama Kandidat wajib diisi");
        }
        if (empty(trim($row['no_hp'] ?? ''))) {
            throw new \Exception("No. HP wajib diisi");
        }
        if (empty(trim($row['posisi'] ?? ''))) {
            throw new \Exception("Posisi wajib diisi");
        }
        if (empty($row['tanggal_interview'])) {
            throw new \Exception("Tanggal Interview wajib diisi");
        }
        if (!isset($row['waktu_interview']) || (empty($row['waktu_interview']) && $row['waktu_interview'] !== 0 && $row['waktu_interview'] !== '0')) {
            throw new \Exception("Waktu Interview wajib diisi");
        }

        // Get position ID (case-insensitive lookup)
        $positionName = strtolower(trim($row['posisi']));
        $positionId = $this->positionCache[$positionName] ?? null;
        if (!$positionId) {
            $availablePositions = implode(', ', array_keys($this->positionCache));
            throw new \Exception("Posisi '{$row['posisi']}' tidak ditemukan. Posisi yang tersedia: {$availablePositions}");
        }

        // Parse date
        try {
            $interviewDate = $this->parseDate($row['tanggal_interview']);
        } catch (\Exception $e) {
            $dateValue = is_numeric($row['tanggal_interview']) ? 'Excel numeric: ' . $row['tanggal_interview'] : $row['tanggal_interview'];
            throw new \Exception("Format tanggal tidak valid: {$dateValue}. Gunakan format YYYY-MM-DD atau M/D/YYYY (contoh: 2026-02-20 atau 2/15/2026)");
        }

        // Parse time
        try {
            $interviewTime = $this->parseTime($row['waktu_interview']);
            if (!$interviewTime) {
                $timeValue = $row['waktu_interview'];
                $timeType = is_numeric($timeValue) ? ' (numeric: ' . $timeValue . ')' : '';
                throw new \Exception("Format waktu tidak dapat di-parse: {$timeValue}{$timeType}. Gunakan format HH:MM (contoh: 09:00, 14:30) atau angka jam (contoh: 9 untuk 09:00)");
            }
        } catch (\Exception $e) {
            throw $e;
        }

        // Get message template if template name provided
        $customTemplate = null;
        if (!empty($row['template_notifikasi'])) {
            $templateName = strtolower(trim($row['template_notifikasi']));
            $customTemplate = $this->templateCache[$templateName] ?? null;
            
            // If template name not found in cache, treat as custom message
            if ($customTemplate === null) {
                $customTemplate = $row['template_notifikasi'];
            }
        }

        return new Interview([
            'candidate_name' => $row['nama_kandidat'],
            'phone' => $this->formatPhone($row['no_hp']),
            'email' => !empty($row['email']) ? $row['email'] : null,
            'position_id' => $positionId,
            'interview_date' => $interviewDate,
            'interview_time' => $interviewTime,
            'location' => !empty($row['lokasi']) ? $row['lokasi'] : 'Kantor PT Mingda',
            'notes' => !empty($row['catatan']) ? $row['catatan'] : null,
            'custom_message_template' => $customTemplate,
            'status' => 'scheduled',
        ]);
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'nama_kandidat' => 'nullable|string|max:255',
            'no_hp' => 'nullable|string',
            'email' => 'nullable|email',
            'posisi' => 'nullable|string',
            'tanggal_interview' => 'nullable',
            'waktu_interview' => 'nullable',
            'lokasi' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
            'template_notifikasi' => 'nullable|string',
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'nama_kandidat.required' => 'Nama Kandidat wajib diisi',
            'no_hp.required' => 'No. HP wajib diisi',
            'email.email' => 'Format email tidak valid',
            'posisi.required' => 'Posisi wajib diisi',
            'tanggal_interview.required' => 'Tanggal Interview wajib diisi',
            'waktu_interview.required' => 'Waktu Interview wajib diisi',
        ];
    }

    /**
     * Parse date from various formats
     */
    protected function parseDate($date)
    {
        // Empty check
        if (empty($date)) {
            throw new \Exception("Tanggal kosong");
        }

        // Excel date (numeric - days since 1900)
        if (is_numeric($date) && $date > 0) {
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date))->format('Y-m-d');
            } catch (\Exception $e) {
                // Fallback if Excel date conversion fails
            }
        }

        // String date - try various formats
        $dateStr = trim((string)$date);
        
        // Try different date formats
        $formats = [
            'Y-m-d',      // 2026-02-15
            'm/d/Y',      // 2/15/2026
            'd/m/Y',      // 15/2/2026
            'Y/m/d',      // 2026/2/15
            'd-m-Y',      // 15-02-2026
            'm-d-Y',      // 02-15-2026
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $dateStr);
                if ($parsed) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Last resort: let Carbon try to parse it
        try {
            return Carbon::parse($dateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \Exception("Format tanggal tidak valid: {$date}");
        }
    }

    /**
     * Parse time from various formats
     */
    protected function parseTime($time)
    {
        // Empty check
        if (empty($time) && $time !== 0 && $time !== '0') {
            return null;
        }

        // Excel time as fraction (0.0 to 0.999 = 00:00 to 23:59)
        if (is_numeric($time) && $time < 1 && $time >= 0) {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($time))->format('H:i:s');
        }

        // Pure integer hours (1, 2, 3... = 01:00, 02:00, 03:00...)
        if (is_numeric($time) && $time >= 1 && $time <= 24) {
            $hours = floor($time);
            $minutes = ($time - $hours) * 60;
            return sprintf('%02d:%02d:00', $hours, $minutes);
        }

        // Convert to string for pattern matching
        $timeStr = trim((string)$time);

        // Format: HH:MM:SS or HH:MM or H:MM or H:M
        if (preg_match('/^(\d{1,2}):(\d{1,2})(?::(\d{2}))?$/', $timeStr, $matches)) {
            $hours = (int)$matches[1];
            $minutes = (int)$matches[2];
            $seconds = isset($matches[3]) ? (int)$matches[3] : 0;
            
            if ($hours >= 0 && $hours <= 23 && $minutes >= 0 && $minutes <= 59) {
                return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            }
        }

        // Try Carbon parse as last resort
        try {
            $parsed = Carbon::parse($timeStr);
            return $parsed->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format phone number (ensure starts with 62)
     */
    protected function formatPhone($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert 08xx to 628xx
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }

        // Add 62 if not present
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }

        return $phone;
    }
}
