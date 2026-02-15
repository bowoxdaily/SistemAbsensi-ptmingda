<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewMessageTemplate extends Model
{
    protected $fillable = [
        'name',
        'message_template',
        'is_default',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Get active templates
     */
    public static function getActive()
    {
        return self::where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get default template
     */
    public static function getDefault()
    {
        return self::where('is_default', true)->first();
    }

    /**
     * Increment usage count
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }
}
