<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceEditRequest extends Model
{
    protected $fillable = [
        'attendance_id',
        'requested_by',
        'old_attendance_date',
        'old_check_in',
        'old_check_out',
        'old_status',
        'new_attendance_date',
        'new_check_in',
        'new_check_out',
        'new_status',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'old_attendance_date' => 'date:Y-m-d',
        'new_attendance_date' => 'date:Y-m-d',
        'reviewed_at'         => 'datetime',
    ];

    /** Request ini untuk absensi mana */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /** Siapa yang submit request */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** Siapa yang review (manager) */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Scope: hanya yang pending */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
