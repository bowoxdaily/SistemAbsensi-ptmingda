<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
    ];

    /**
     * Relasi ke Employees (Karyawans)
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Karyawans::class, 'position_id');
    }
}
