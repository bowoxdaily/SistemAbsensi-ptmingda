<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCareer extends Model
{
    protected $fillable = [
        'employee_id',
        'previous_position_id',
        'new_position_id',
        'effective_date',
        'movement_type',
        'notes',
        'created_by',
        'updated_by',
    ];

    // Don't cast effective_date - keep as Y-m-d string from database
    // This prevents timezone conversion issues when returning to API

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Karyawans::class, 'employee_id');
    }

    public function previousPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'previous_position_id');
    }

    public function newPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'new_position_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
