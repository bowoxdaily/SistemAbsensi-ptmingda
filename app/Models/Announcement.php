<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'content',
        'type',
        'priority',
        'filter_type',
        'filter_values',
        'is_active',
        'show_popup',
        'start_date',
        'end_date',
        'created_by',
    ];

    protected $casts = [
        'filter_values' => 'array',
        'is_active'     => 'boolean',
        'show_popup'    => 'boolean',
        'start_date'    => 'datetime',
        'end_date'      => 'datetime',
    ];

    protected $appends = [
        'type_label',
        'type_badge',
        'type_icon',
        'priority_label',
        'priority_badge',
        'filter_label',
        'is_expired',
        'is_scheduled',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    // ─── Accessors ─────────────────────────────────────────────────────────────

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'info'    => 'Informasi',
            'warning' => 'Peringatan',
            'success' => 'Baik',
            'danger'  => 'Penting',
            default   => 'Informasi',
        };
    }

    public function getTypeBadgeAttribute(): string
    {
        return match($this->type) {
            'info'    => 'bg-info',
            'warning' => 'bg-warning',
            'success' => 'bg-success',
            'danger'  => 'bg-danger',
            default   => 'bg-info',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'info'    => 'bx-info-circle',
            'warning' => 'bx-error',
            'success' => 'bx-check-circle',
            'danger'  => 'bx-bell',
            default   => 'bx-info-circle',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'low'    => 'Rendah',
            'normal' => 'Normal',
            'high'   => 'Tinggi',
            'urgent' => 'Mendesak',
            default  => 'Normal',
        };
    }

    public function getPriorityBadgeAttribute(): string
    {
        return match($this->priority) {
            'low'    => 'bg-secondary',
            'normal' => 'bg-primary',
            'high'   => 'bg-warning',
            'urgent' => 'bg-danger',
            default  => 'bg-primary',
        };
    }

    public function getFilterLabelAttribute(): string
    {
        return match($this->filter_type) {
            'all'        => 'Semua Karyawan Aktif',
            'position'   => 'Berdasarkan Jabatan',
            'department' => 'Berdasarkan Departemen',
            'employee'   => 'Karyawan Tertentu',
            default      => 'Semua Karyawan Aktif',
        };
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function getIsScheduledAttribute(): bool
    {
        return $this->start_date && $this->start_date->isFuture();
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Scope: pengumuman yang sedang aktif & visible sekarang
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope: urutkan berdasarkan prioritas (urgent > high > normal > low)
     */
    public function scopeByPriority($query)
    {
        return $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
                     ->orderBy('created_at', 'desc');
    }

    // ─── Helper Methods ────────────────────────────────────────────────────────

    /**
     * Cek apakah pengumuman ini berlaku untuk employee tertentu.
     * Menerima Employee atau Karyawans (keduanya tabel employees)
     */
    public function isForEmployee(Model $employee): bool
    {
        return match($this->filter_type) {
            'all'        => true,
            'position'   => in_array($employee->position_id, $this->filter_values ?? []),
            'department' => in_array($employee->department_id, $this->filter_values ?? []),
            'employee'   => in_array($employee->id, $this->filter_values ?? []),
            default      => true,
        };
    }

    /**
     * Hitung jumlah penerima potensial
     */
    public function countRecipients(): int
    {
        $query = Karyawans::where('status', 'active');

        switch ($this->filter_type) {
            case 'position':
                $query->whereIn('position_id', $this->filter_values ?? []);
                break;
            case 'department':
                $query->whereIn('department_id', $this->filter_values ?? []);
                break;
            case 'employee':
                $query->whereIn('id', $this->filter_values ?? []);
                break;
        }

        return $query->count();
    }
}
