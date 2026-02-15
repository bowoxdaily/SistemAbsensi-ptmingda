<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    protected $fillable = [
        'candidate_name',
        'phone',
        'email',
        'position_id',
        'interview_date',
        'interview_time',
        'location',
        'notes',
        'custom_message_template',
        'status',
        'wa_sent_at',
        'wa_message',
    ];

    protected $casts = [
        'interview_date' => 'date',
        'interview_time' => 'datetime:H:i:s',
        'wa_sent_at' => 'datetime',
    ];

    /**
     * Relationship with Position
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
