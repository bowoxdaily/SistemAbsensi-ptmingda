<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarningLetter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'sp_type',
        'sp_number',
        'issue_date',
        'effective_date',
        'violation',
        'description',
        'document_path',
        'status',
        'completion_date',
        'cancellation_reason',
        'issued_by',
        'issued_at',
        'updated_by',
        'wa_sent_at',
        'wa_message',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'effective_date' => 'date',
        'completion_date' => 'date',
        'issued_at' => 'datetime',
        'wa_sent_at' => 'datetime',
    ];

    /**
     * Relationship: Employee who received the SP
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relationship: User who issued the SP
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Relationship: User who last updated the SP
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if SP is still active
     */
    public function isActive(): bool
    {
        return $this->status === 'aktif';
    }

    /**
     * Get SP type label
     */
    public function getSpTypeLabelAttribute(): string
    {
        return [
            'SP1' => 'SP 1 - Peringatan Pertama',
            'SP2' => 'SP 2 - Peringatan Kedua',
            'SP3' => 'SP 3 - Peringatan Terakhir',
        ][$this->sp_type] ?? $this->sp_type;
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeAttribute(): string
    {
        return [
            'aktif' => 'bg-danger',
            'selesai' => 'bg-success',
            'dibatalkan' => 'bg-secondary',
        ][$this->status] ?? 'bg-secondary';
    }

    /**
     * Get SP type badge class for UI
     */
    public function getSpTypeBadgeAttribute(): string
    {
        return [
            'SP1' => 'bg-warning',
            'SP2' => 'bg-orange',
            'SP3' => 'bg-danger',
        ][$this->sp_type] ?? 'bg-secondary';
    }

    /**
     * Generate SP Number with custom format from settings
     * Default format: {sp_type}/{dept}/{counter}/{year}
     * Available variables: {sp_type}, {dept}, {counter}, {year}, {month}
     */
    public static function generateSpNumber($spType, $issueDate = null): string
    {
        // Use issue_date if provided, otherwise use current date
        $date = $issueDate ? new \DateTime($issueDate) : new \DateTime();
        $year = $date->format('Y');
        $month = $date->format('m');

        // Get settings from WhatsAppSetting (or use default)
        $settings = WhatsAppSetting::first();

        $format = $settings->sp_number_format ?? '{sp_type}/{dept}/{counter}/{year}';
        $dept = $settings->sp_department_code ?? 'HR';
        $counterWidth = $settings->sp_counter_width ?? 3;

        // Count existing SP of this type in current year
        $count = self::where('sp_type', $spType)
            ->whereYear('issue_date', $year)
            ->count() + 1;

        // Format counter with padding
        $counter = str_pad($count, $counterWidth, '0', STR_PAD_LEFT);

        // Replace variables in format
        $spNumber = str_replace(
            ['{sp_type}', '{dept}', '{counter}', '{year}', '{month}'],
            [$spType, $dept, $counter, $year, $month],
            $format
        );

        return $spNumber;
    }
}
