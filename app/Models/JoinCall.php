<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JoinCall extends Model
{
    protected $fillable = [
        'candidate_name',
        'phone',
        'email',
        'department_id',
        'join_call_date',
        'join_call_time',
        'location',
        'notes',
        'custom_message_template',
        'status',
        'wa_sent_at',
        'wa_message',
        'qr_code_token',
        'qr_code_image',
        'checked_in_at',
        'checked_in_by',
    ];

    protected $casts = [
        // 'join_call_date' => 'date', // Don't cast - keep as Y-m-d string from database
        'join_call_time' => 'datetime:H:i:s',
        'wa_sent_at'     => 'datetime',
        'checked_in_at'  => 'datetime',
    ];

    /**
     * Boot method to generate QR token
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($joinCall) {
            if (empty($joinCall->qr_code_token)) {
                $joinCall->qr_code_token = static::generateQrToken();
            }
        });
    }

    /**
     * Generate unique QR code token
     */
    public static function generateQrToken()
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (static::where('qr_code_token', $token)->exists());

        return $token;
    }

    /**
     * Get QR code URL
     */
    public function getQrCodeUrlAttribute()
    {
        if (!$this->qr_code_token) {
            return null;
        }
        return url('/join-call/scan/' . $this->qr_code_token);
    }

    /**
     * Get QR code image URL (using Google Charts API)
     */
    public function getQrCodeImageAttribute()
    {
        if (!$this->qr_code_token) {
            return null;
        }
        $url = urlencode($this->qr_code_url);
        return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={$url}";
    }

    /**
     * Check if join call has been checked in
     */
    public function isCheckedIn()
    {
        return !is_null($this->checked_in_at);
    }

    /**
     * Relationship with Department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
