<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FingerspotLog extends Model
{
    protected $fillable = [
        'pin',
        'employee_id',
        'scan_time',
        'status_scan',
        'verify_mode',
        'sn',
        'photo_url',
        'attendance_id',
        'process_status',
        'process_message',
        'raw_data',
    ];

    protected $casts = [
        'scan_time' => 'datetime',
        'raw_data' => 'array',
    ];

    /**
     * Get the employee that owns the log
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the attendance record
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Scope for pending logs
     */
    public function scopePending($query)
    {
        return $query->where('process_status', 'pending');
    }

    /**
     * Scope for failed logs
     */
    public function scopeFailed($query)
    {
        return $query->where('process_status', 'failed');
    }

    /**
     * Mark as success
     */
    public function markAsSuccess(?int $attendanceId = null, ?string $message = null): void
    {
        $this->update([
            'process_status' => 'success',
            'attendance_id' => $attendanceId,
            'process_message' => $message,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $message): void
    {
        $this->update([
            'process_status' => 'failed',
            'process_message' => $message,
        ]);
    }

    /**
     * Mark as skipped
     */
    public function markAsSkipped(string $message): void
    {
        $this->update([
            'process_status' => 'skipped',
            'process_message' => $message,
        ]);
    }
}
