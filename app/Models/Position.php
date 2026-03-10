<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $fillable = [
        'code',
        'name',
        'level',
        'description',
        'status',
    ];

    protected $appends = ['display_name'];

    /**
     * Nama tampilan dengan level (jika ada)
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->level) {
            return $this->name . ' Level ' . $this->level;
        }
        return $this->name;
    }

    /**
     * Relasi ke Employees (Karyawans)
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Karyawans::class, 'position_id');
    }
}
