<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'late_tolerance',
        'overtime_threshold',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'late_tolerance' => 'integer',
        'overtime_threshold' => 'integer',
        'is_active' => 'boolean',
    ];
}
