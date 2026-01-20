<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Holiday extends Model
{
    protected $fillable = [
        'date',
        'name',
        'type',
        'description',
        'is_active'
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean'
    ];

    /**
     * Check if a date is a holiday
     */
    public static function isHoliday($date)
    {
        $dateStr = Carbon::parse($date)->format('Y-m-d');
        return self::where('date', $dateStr)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get holidays in a date range
     */
    public static function getHolidaysInRange($startDate, $endDate)
    {
        return self::whereBetween('date', [$startDate, $endDate])
            ->where('is_active', true)
            ->orderBy('date')
            ->get();
    }

    /**
     * Get holidays for a specific month
     */
    public static function getHolidaysByMonth($year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        
        return self::whereBetween('date', [$startDate, $endDate])
            ->where('is_active', true)
            ->orderBy('date')
            ->get();
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'nasional' => 'Libur Nasional',
            'cuti_bersama' => 'Cuti Bersama',
            'custom' => 'Custom',
            default => $this->type
        };
    }
}

