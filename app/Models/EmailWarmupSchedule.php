<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailWarmupSchedule extends Model
{
    use HasFactory;

    protected $table = 'email_warmup_schedules';

    protected $fillable = [
        'status',
        'current_day',
        'total_days',
        'emails_per_day',
        'max_emails_per_day',
        'increase_percentage',
        'emails_sent_today',
        'last_send_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_send_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(EmailWarmupLog::class);
    }

    public function stats()
    {
        return $this->hasOne(EmailWarmupStat::class);
    }

    /**
     * Get the recommended emails to send today
     */
    public function getEmailsForToday(): int
    {
        if ($this->status !== 'active') {
            return 0;
        }

        $emailsPerDay = $this->emails_per_day;
        
        // Increase volume gradually
        for ($i = 1; $i < $this->current_day; $i++) {
            $emailsPerDay = $emailsPerDay * (1 + ($this->increase_percentage / 100));
        }

        // Cap at max
        return min((int)$emailsPerDay, $this->max_emails_per_day);
    }

    /**
     * Check if can send more emails today
     */
    public function canSendToday(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $allowed = $this->getEmailsForToday();
        return $this->emails_sent_today < $allowed;
    }

    /**
     * Record an email sent
     */
    public function recordSent(string $recipient, string $subject, string $messageId = null): EmailWarmupLog
    {
        $this->increment('emails_sent_today');
        $this->update(['last_send_at' => now()]);

        return EmailWarmupLog::create([
            'recipient_email' => $recipient,
            'subject' => $subject,
            'status' => 'sent',
            'message_id' => $messageId,
            'warmup_day' => $this->current_day,
            'sent_at' => now(),
        ]);
    }

    /**
     * Move to next day
     */
    public function nextDay(): void
    {
        if ($this->current_day >= $this->total_days) {
            $this->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } else {
            $this->update([
                'current_day' => $this->current_day + 1,
                'emails_sent_today' => 0,
            ]);
        }
    }

    /**
     * Get completion percentage
     */
    public function getProgressPercentage(): int
    {
        return (int)(($this->current_day / $this->total_days) * 100);
    }
}
