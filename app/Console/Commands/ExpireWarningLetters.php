<?php

namespace App\Console\Commands;

use App\Models\WarningLetter;
use Illuminate\Console\Command;

class ExpireWarningLetters extends Command
{
    protected $signature = 'warning-letters:expire';

    protected $description = 'Mark active warning letters as completed when their completion date is reached.';

    public function handle(): int
    {
        $count = WarningLetter::query()
            ->where('status', 'aktif')
            ->whereDate('completion_date', '<=', today())
            ->update(['status' => 'selesai', 'updated_at' => now()]);

        $this->info("Expired {$count} warning letter(s).");

        return self::SUCCESS;
    }
}

