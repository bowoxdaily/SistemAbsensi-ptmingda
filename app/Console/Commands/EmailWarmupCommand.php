<?php

namespace App\Console\Commands;

use App\Services\EmailWarmupService;
use Illuminate\Console\Command;

class EmailWarmupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:warmup {action} {--days=30} {--start=10} {--max=500} {--increase=15}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage email warmup schedule (start, pause, resume, stop, status)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = new EmailWarmupService();
        $action = strtolower($this->argument('action'));

        try {
            match ($action) {
                'start' => $this->handleStart($service),
                'pause' => $this->handlePause($service),
                'resume' => $this->handleResume($service),
                'stop' => $this->handleStop($service),
                'status' => $this->handleStatus($service),
                default => $this->error("Unknown action: {$action}"),
            };
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
        }
    }

    private function handleStart(EmailWarmupService $service): void
    {
        $days = (int)$this->option('days');
        $start = (int)$this->option('start');
        $max = (int)$this->option('max');
        $increase = (float)$this->option('increase');

        $service->start($days, $start, $max, $increase);

        $this->info('✓ Email warmup started');
        $this->line("  Days: {$days}");
        $this->line("  Starting volume: {$start} emails/day");
        $this->line("  Maximum volume: {$max} emails/day");
        $this->line("  Daily increase: {$increase}%");
    }

    private function handlePause(EmailWarmupService $service): void
    {
        $service->pause();
        $this->info('✓ Email warmup paused');
    }

    private function handleResume(EmailWarmupService $service): void
    {
        $service->resume();
        $this->info('✓ Email warmup resumed');
    }

    private function handleStop(EmailWarmupService $service): void
    {
        $service->stop();
        $this->info('✓ Email warmup stopped');
    }

    private function handleStatus(EmailWarmupService $service): void
    {
        $status = $service->getStatus();

        $this->info('Email Warmup Status:');
        $this->line('─────────────────────────────────────');
        $this->line("Status: <comment>{$status['status']}</comment>");
        $this->line("Progress: <info>{$status['progress_percentage']}%</info> (Day {$status['current_day']}/{$status['total_days']})");
        $this->line("Emails sent today: <info>{$status['emails_sent_today']}</info>/{$status['emails_allowed_today']}");
        $this->line("Can send now: " . ($status['can_send_now'] ? '<fg=green>Yes</>' : '<fg=red>No</>'));
        $this->line('');
        $this->line('Statistics:');
        $this->line("  Total sent: {$status['statistics']['total_sent']}");
        $this->line("  Delivered: {$status['statistics']['total_delivered']}");
        $this->line("  Bounce rate: {$status['statistics']['bounce_rate']}%");
        $this->line("  Spam rate: {$status['statistics']['spam_rate']}%");
        $this->line("  Delivery rate: {$status['statistics']['delivery_rate']}%");
        $this->line("  Sender reputation: <comment>{$status['statistics']['sender_reputation']}/100</comment> ({$status['statistics']['reputation_status']})");
        $this->line('');
        $this->line("Recommendation: {$service->getRecommendation()}");
    }
}
