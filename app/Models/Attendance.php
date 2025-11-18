<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'attendance_date',
        'check_in',
        'check_out',
        'status',
        'notes',
        'photo_in',
        'photo_out',
        'location_in',
        'location_out',
        'late_minutes',
        'gps_accuracy_in',
        'gps_accuracy_out',
        'is_mocked_in',
        'is_mocked_out',
        'gps_warnings_in',
        'gps_warnings_out',
        'is_suspicious_in',
        'is_suspicious_out',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in' => 'datetime:H:i:s',
        'check_out' => 'datetime:H:i:s',
        'late_minutes' => 'integer',
        'is_mocked_in' => 'boolean',
        'is_mocked_out' => 'boolean',
        'is_suspicious_in' => 'boolean',
        'is_suspicious_out' => 'boolean',
        'gps_accuracy_in' => 'float',
        'gps_accuracy_out' => 'float',
    ];

    /**
     * Relasi ke Employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
