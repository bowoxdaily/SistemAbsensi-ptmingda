<?php

namespace App\Console\Commands;

use App\Models\CallLetter;
use Illuminate\Console\Command;

class FailStaleCallLetterEmails extends Command
{
    protected $signature = 'call-letters:fail-stale-emails';

    protected $description = 'Mark call-letter emails stuck in the queue as failed for manual retry.';

    public function handle(): int
    {
        $count = CallLetter::query()
            ->where('email_status', 'pending')
            ->where('updated_at', '<', now()->subHour())
            ->update([
                'email_status' => 'failed',
                'email_error' => 'Email tertahan di antrean lebih dari satu jam. Silakan kirim ulang.',
                'updated_at' => now(),
            ]);

        $this->info("Marked {$count} stale call-letter email(s) as failed.");

        return self::SUCCESS;
    }
}
