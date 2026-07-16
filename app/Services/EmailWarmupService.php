<?php

namespace App\Services;

use App\Models\EmailWarmupSchedule;
use App\Models\EmailWarmupStat;
use App\Models\EmailWarmupLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmailWarmupService
{
    protected EmailWarmupSchedule $schedule;
    protected EmailWarmupStat $stats;

    public function __construct()
    {
        $this->schedule = EmailWarmupSchedule::firstOrCreate(
            [],
            [
                'status' => 'inactive',
                'current_day' => 1,
                'total_days' => 30,
                'emails_per_day' => 10,
                'max_emails_per_day' => 500,
                'increase_percentage' => 15,
            ]
        );

        $this->stats = EmailWarmupStat::firstOrCreate(
            [],
            [
                'total_sent' => 0,
                'total_delivered' => 0,
                'total_bounced' => 0,
                'total_spam' => 0,
            ]
        );
    }

    /**
     * Start warmup schedule
     */
    public function start(
        int $totalDays = 30,
        int $startVolume = 10,
        int $maxVolume = 500,
        float $increasePercentage = 15
    ): EmailWarmupSchedule
    {
        $this->schedule->update([
            'status' => 'active',
            'current_day' => 1,
            'emails_sent_today' => 0,
            'total_days' => $totalDays,
            'emails_per_day' => $startVolume,
            'max_emails_per_day' => $maxVolume,
            'increase_percentage' => $increasePercentage,
            'started_at' => now(),
            'completed_at' => null,
        ]);

        Log::info('Email warmup started', [
            'total_days' => $totalDays,
            'start_volume' => $startVolume,
            'max_volume' => $maxVolume,
        ]);

        return $this->schedule;
    }

    /**
     * Pause warmup
     */
    public function pause(): void
    {
        $this->schedule->update(['status' => 'paused']);
        Log::info('Email warmup paused');
    }

    /**
     * Resume warmup
     */
    public function resume(): void
    {
        if ($this->schedule->status === 'completed') {
            throw new \Exception('Cannot resume completed warmup. Start a new one.');
        }

        $this->schedule->update(['status' => 'active']);
        Log::info('Email warmup resumed');
    }

    /**
     * Stop warmup
     */
    public function stop(): void
    {
        $this->schedule->update(['status' => 'inactive']);
        Log::info('Email warmup stopped');
    }

    /**
     * Check if can send email now
     */
    public function canSendEmail(): bool
    {
        if (!$this->schedule->canSendToday()) {
            return false;
        }

        // Rate limiting: minimum 5 seconds between emails
        if ($this->schedule->last_send_at && $this->schedule->last_send_at->diffInSeconds(now()) < 5) {
            return false;
        }

        return true;
    }

    /**
     * Get delay in seconds before next email can be sent
     */
    public function getDelayBeforeNextEmail(): int
    {
        if (!$this->schedule->last_send_at) {
            return 0;
        }

        $secondsSinceLast = $this->schedule->last_send_at->diffInSeconds(now());
        $delay = 5 - $secondsSinceLast;

        return max(0, $delay);
    }

    /**
     * Record email sent
     */
    public function recordEmailSent(
        string $recipientEmail,
        string $subject,
        string $messageId = null
    ): EmailWarmupLog
    {
        $log = $this->schedule->recordSent($recipientEmail, $subject, $messageId);

        $this->stats->increment('total_sent');
        $this->stats->save();

        Log::info('Email warmup: email sent', [
            'recipient' => $recipientEmail,
            'day' => $this->schedule->current_day,
            'sent_today' => $this->schedule->emails_sent_today,
        ]);

        return $log;
    }

    /**
     * Record email status update
     */
    public function recordEmailStatus(
        string $recipientEmail,
        string $status,
        ?string $errorMessage = null
    ): void
    {
        $log = EmailWarmupLog::where('recipient_email', $recipientEmail)
            ->latest()
            ->first();

        if (!$log) {
            return;
        }

        $log->update([
            'status' => $status,
            'error_message' => $errorMessage,
        ]);

        // Update stats
        match ($status) {
            'delivered' => $this->stats->increment('total_delivered'),
            'bounced' => $this->stats->increment('total_bounced'),
            'spam' => $this->stats->increment('total_spam'),
            default => null,
        };

        $this->stats->calculate();

        Log::info('Email warmup: status updated', [
            'recipient' => $recipientEmail,
            'status' => $status,
        ]);
    }

    /**
     * Check if should advance to next day
     */
    public function checkAndAdvanceDay(): void
    {
        $today = Carbon::today();
        $lastUpdate = $this->schedule->updated_at?->toDateString();

        if ($lastUpdate && $lastUpdate !== $today->toDateString()) {
            $this->schedule->nextDay();
            Log::info('Email warmup: advanced to next day', [
                'day' => $this->schedule->current_day,
                'emails_for_today' => $this->schedule->getEmailsForToday(),
            ]);
        }
    }

    /**
     * Get schedule
     */
    public function getSchedule(): EmailWarmupSchedule
    {
        return $this->schedule->fresh();
    }

    /**
     * Get statistics
     */
    public function getStatistics(): EmailWarmupStat
    {
        return $this->stats->fresh();
    }

    /**
     * Get warmup status summary
     */
    public function getStatus(): array
    {
        $this->checkAndAdvanceDay();

        return [
            'status' => $this->schedule->status,
            'current_day' => $this->schedule->current_day,
            'total_days' => $this->schedule->total_days,
            'progress_percentage' => $this->schedule->getProgressPercentage(),
            'emails_sent_today' => $this->schedule->emails_sent_today,
            'emails_allowed_today' => $this->schedule->getEmailsForToday(),
            'can_send_now' => $this->canSendEmail(),
            'started_at' => $this->schedule->started_at,
            'completed_at' => $this->schedule->completed_at,
            'statistics' => [
                'total_sent' => $this->stats->total_sent,
                'total_delivered' => $this->stats->total_delivered,
                'total_bounced' => $this->stats->total_bounced,
                'total_spam' => $this->stats->total_spam,
                'delivery_rate' => $this->stats->delivery_rate,
                'bounce_rate' => $this->stats->bounce_rate,
                'spam_rate' => $this->stats->spam_rate,
                'sender_reputation' => $this->stats->sender_reputation,
                'reputation_status' => $this->stats->reputation_status,
            ],
        ];
    }

    /**
     * Get recommended action based on current metrics
     */
    public function getRecommendation(): string
    {
        $stats = $this->getStatistics();

        if ($stats->bounce_rate > 5) {
            return 'High bounce rate detected. Review your email list quality.';
        }

        if ($stats->spam_rate > 2) {
            return 'High spam complaint rate. Reduce email frequency and improve content quality.';
        }

        if ($stats->delivery_rate < 95) {
            return 'Delivery rate below target. Continue warmup gradually.';
        }

        if ($this->schedule->status === 'completed') {
            return 'Warmup completed successfully! You can now send at full volume.';
        }

        return 'Warmup progressing normally. Continue with scheduled sends.';
    }
}
