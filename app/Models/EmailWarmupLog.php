<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailWarmupLog extends Model
{
    use HasFactory;

    protected $table = 'email_warmup_logs';

    protected $fillable = [
        'recipient_email',
        'subject',
        'status',
        'message_id',
        'error_message',
        'warmup_day',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
