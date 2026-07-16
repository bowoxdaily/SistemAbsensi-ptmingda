<?php

namespace App\Console\Commands;

use App\Jobs\SendWarmupEmail;
use App\Models\Karyawans;
use App\Services\EmailWarmupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchWarmupEmails extends Command
{
    protected $signature = 'email:dispatch-warmup {--limit=0}';
    protected $description = 'Dispatch warmup emails to queue based on daily limit';

    public function handle(): int
    {
        $service = new EmailWarmupService();
        $schedule = $service->getSchedule();

        // Check if warmup is active
        if ($schedule->status !== 'active') {
            $this->warn('Email warmup is not active');
            return 0;
        }

        // Auto-advance day if calendar date changed
        $service->checkAndAdvanceDay();

        // Check daily limit
        $emailsAllowed = $schedule->emails_per_day;
        $emailsSentToday = $schedule->emails_sent_today;
        $remainingToday = max(0, $emailsAllowed - $emailsSentToday);

        if ($remainingToday <= 0) {
            $this->info("Daily limit reached: {$emailsSentToday}/{$emailsAllowed}");
            return 0;
        }

        // Get limit from command option
        $manualLimit = $this->option('limit');
        $toDispatch = $manualLimit > 0 ? min($manualLimit, $remainingToday) : $remainingToday;

        $this->info("Dispatching {$toDispatch} warmup emails (Day {$schedule->current_day}/{$schedule->total_days})");

        // Get employees
        $employees = Karyawans::whereNotNull('email')
            ->where('email', '!=', '')
            ->take($toDispatch)
            ->get();

        if ($employees->isEmpty()) {
            $this->warn('No employees found with email');
            return 0;
        }

        $dispatched = 0;
        foreach ($employees as $employee) {
            try {
                // Check if can send before dispatch
                if (!$service->canSendEmail()) {
                    $this->warn("Rate limit hit, stopping dispatch");
                    break;
                }

                // Dispatch job
                dispatch(new SendWarmupEmail($employee));
                $dispatched++;

                // Small delay between dispatches (optional)
                usleep(100000); // 0.1 seconds

            } catch (\Exception $e) {
                $this->error("Failed to dispatch for {$employee->email}: {$e->getMessage()}");
            }
        }

        $this->info("Dispatched {$dispatched} email jobs");
        Log::info("Warmup emails dispatched: {$dispatched}");

        return 0;
    }
}
