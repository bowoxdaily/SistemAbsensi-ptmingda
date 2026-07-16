<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailWarmupStat extends Model
{
    use HasFactory;

    protected $table = 'email_warmup_stats';

    protected $fillable = [
        'total_sent',
        'total_delivered',
        'total_bounced',
        'total_spam',
        'delivery_rate',
        'bounce_rate',
        'spam_rate',
        'sender_reputation',
        'reputation_status',
        'last_calculated_at',
    ];

    protected $casts = [
        'last_calculated_at' => 'datetime',
    ];

    /**
     * Assess reputation status based on scores
     */
    public function assessReputationStatus(): string
    {
        if ($this->sender_reputation >= 90) {
            return 'excellent';
        } elseif ($this->sender_reputation >= 75) {
            return 'good';
        } elseif ($this->sender_reputation >= 50) {
            return 'fair';
        }
        return 'poor';
    }

    /**
     * Calculate statistics
     */
    public function calculate(): void
    {
        $total = $this->total_sent;

        if ($total === 0) {
            return;
        }

        $this->delivery_rate = round(($this->total_delivered / $total) * 100, 2);
        $this->bounce_rate = round(($this->total_bounced / $total) * 100, 2);
        $this->spam_rate = round(($this->total_spam / $total) * 100, 2);

        // Calculate reputation score
        $reputationScore = 100;
        $reputationScore -= ($this->bounce_rate * 0.5); // 0.5 points per bounce %
        $reputationScore -= ($this->spam_rate * 2); // 2 points per spam %
        $reputationScore += ($this->delivery_rate * 0.1); // 0.1 points per delivery %

        $this->sender_reputation = max(0, min(100, $reputationScore));
        $this->reputation_status = $this->assessReputationStatus();
        $this->last_calculated_at = now();
        $this->save();
    }
}
