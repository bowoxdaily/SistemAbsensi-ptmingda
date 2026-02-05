<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FingerspotSetting extends Model
{
    protected $fillable = [
        'name',
        'sn',
        'api_url',
        'webhook_token',
        'is_active',
        'auto_sync',
        'sync_interval',
        'auto_checkout',
        'auto_checkout_hours',
        'scan_mode',
        'notes',
        'last_sync_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_sync' => 'boolean',
        'sync_interval' => 'integer',
        'auto_checkout' => 'boolean',
        'auto_checkout_hours' => 'integer',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Boot function to generate token on create
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->webhook_token)) {
                $model->webhook_token = Str::random(64);
            }
        });
    }

    /**
     * Get active setting by token
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('webhook_token', $token)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get first active setting
     */
    public static function getActive(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Regenerate webhook token
     */
    public function regenerateToken(): string
    {
        $this->webhook_token = Str::random(64);
        $this->save();
        return $this->webhook_token;
    }

    /**
     * Update last sync time
     */
    public function updateLastSync(): void
    {
        $this->update(['last_sync_at' => now()]);
    }

    /**
     * Get logs for this setting
     */
    public function logs()
    {
        return $this->hasMany(FingerspotLog::class, 'sn', 'sn');
    }
}
