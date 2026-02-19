<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastMessage extends Model
{
    protected $fillable = [
        'title',
        'message',
        'image',
        'filter_type',
        'filter_values',
        'total_recipients',
        'sent_count',
        'failed_count',
        'status',
        'sent_by',
        'sent_at',
    ];

    protected $casts = [
        'filter_values' => 'array',
        'sent_at' => 'datetime',
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
    ];

    protected $appends = [
        'filter_label',
        'status_badge',
        'status_label',
    ];

    /**
     * Relationship to User (sender)
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Get filter label for display
     */
    public function getFilterLabelAttribute(): string
    {
        return match($this->filter_type) {
            'all' => 'Semua Karyawan',
            'position' => 'Berdasarkan Jabatan',
            'department' => 'Berdasarkan Department',
            'employee' => 'Karyawan Tertentu',
            default => 'Tidak diketahui'
        };
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'draft' => 'bg-secondary',
            'sending' => 'bg-info',
            'completed' => 'bg-success',
            'failed' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'sending' => 'Mengirim',
            'completed' => 'Selesai',
            'failed' => 'Gagal',
            default => 'Draft'
        };
    }
}
