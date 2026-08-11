<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AttendanceClarification extends Model
{
    protected $fillable = [
        'attendance_id',
        'employee_id',
        'reason',
        'new_status',
        'new_check_in',
        'new_check_out',
        'attachment_path',
        'attachment_original_name',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /** Relasi ke Attendance */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /** Relasi ke Employee yang mengajukan */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** Relasi ke User yang mereview (admin) */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Scope: hanya yang pending */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /** Accessor: URL lengkap untuk lampiran */
    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment_path || $this->attachment_path === '0') {
            return null;
        }
        $disk = config('filesystems.default', 'r2');
        return Storage::disk($disk)->url($this->attachment_path);
    }

    /** Cek apakah lampiran adalah gambar */
    public function getIsImageAttribute(): bool
    {
        if (!$this->attachment_original_name) {
            return false;
        }
        $ext = strtolower(pathinfo($this->attachment_original_name, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }
}
