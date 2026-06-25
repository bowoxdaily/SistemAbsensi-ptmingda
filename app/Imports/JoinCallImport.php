<?php

namespace App\Imports;

use App\Models\JoinCall;
use App\Models\SubDepartment;
use App\Models\JoinMessageTemplate;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;

class JoinCallImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    protected $departmentCache = [];
    protected $templateCache = [];

    public function __construct()
    {
        // Cache sub departments to avoid repeated queries (case-insensitive)
        $subDepartments = SubDepartment::query()->get();
        foreach ($subDepartments as $subDepartment) {
            $this->departmentCache[strtolower(trim($subDepartment->name))] = $subDepartment->id;
        }

        // Cache message templates (case-insensitive)
        $templates = JoinMessageTemplate::where('is_active', true)->get();
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
        $subDepartmentRaw = $row['sub_departemen'] ?? ($row['departemen'] ?? ($row['posisi'] ?? ''));

        // Skip example row (case insensitive)
        if (isset($row['nama_kandidat']) && in_array(strtoupper(trim($row['nama_kandidat'])), ['JOHN DOE', 'CONTOH'])) {
            return null;
        }

        // Skip empty rows - check multiple fields
        $isEmpty = empty(trim($row['nama_kandidat'] ?? '')) 
            && empty(trim($row['email'] ?? ''))
            && empty(trim($row['no_hp'] ?? ''))
            && empty(trim($subDepartmentRaw));
        
        if ($isEmpty) {
            return null;
        }

        // Also skip if only nama filled but no other required fields
        if (!empty($row['nama_kandidat']) && empty($row['email']) && empty($row['no_hp']) && empty($subDepartmentRaw)) {
            return null;
        }

        // Validate required fields manually
        if (empty(trim($row['nama_kandidat'] ?? ''))) {
            throw new \Exception("Nama Kandidat wajib diisi");
        }
        if (empty(trim($row['email'] ?? ''))) {
            throw new \Exception("Email wajib diisi");
        }
        if (empty(trim($subDepartmentRaw))) {
            throw new \Exception("Sub Departemen wajib diisi");
        }
        if (empty($row['tanggal_join'])) {
            throw new \Exception("Tanggal Join wajib diisi");
        }
        if (!isset($row['waktu_join']) || (empty($row['waktu_join']) && $row['waktu_join'] !== 0 && $row['waktu_join'] !== '0')) {
            throw new \Exception("Waktu Join wajib diisi");
        }

        // Get department ID (case-insensitive lookup)
        $departmentRaw = $subDepartmentRaw;
        $departmentName = strtolower(trim($departmentRaw));
        $departmentId = $this->departmentCache[$departmentName] ?? null;
        if (!$departmentId) {
            $availableDepartments = implode(', ', array_keys($this->departmentCache));
            throw new \Exception("Sub Departemen '{$departmentRaw}' tidak ditemukan. Sub Departemen yang tersedia: {$availableDepartments}");
        }

        // Parse date
        try {
            $joinDate = $this->parseDate($row['tanggal_join']);
        } catch (\Exception $e) {
            $dateValue = is_numeric($row['tanggal_join']) ? 'Excel numeric: ' . $row['tanggal_join'] : $row['tanggal_join'];
            throw new \Exception("Format tanggal tidak valid: {$dateValue}. Gunakan format YYYY-MM-DD atau M/D/YYYY (contoh: 2026-02-20 atau 2/15/2026)");
        }

        // Parse time
        try {
            $joinTime = $this->parseTime($row['waktu_join']);
            if (!$joinTime) {
                $timeValue = $row['waktu_join'];
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

        return new JoinCall([
            'candidate_name' => $row['nama_kandidat'],
            'phone' => !empty($row['no_hp']) ? $this->formatPhone($row['no_hp']) : null,
            'email' => strtolower(trim($row['email'])),
            'sub_department_id' => $departmentId,
            'join_call_date' => $joinDate,
            'join_call_time' => $joinTime,
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
            'sub_departemen' => 'nullable|string',
            'departemen' => 'nullable|string',
            'posisi' => 'nullable|string',
            'tanggal_join' => 'nullable',
            'waktu_join' => 'nullable',
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
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'sub_departemen.required' => 'Sub Departemen wajib diisi',
            'departemen.required' => 'Departemen wajib diisi',
            'tanggal_join.required' => 'Tanggal Join wajib diisi',
            'waktu_join.required' => 'Waktu Join wajib diisi',
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
