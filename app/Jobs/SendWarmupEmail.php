<?php

namespace App\Jobs;

use App\Mail\WarmupEmployeeMail;
use App\Models\Karyawans;
use App\Services\EmailWarmupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendWarmupEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Karyawans $employee,
        public string $mailClass = WarmupEmployeeMail::class,
    ) {
        $this->onQueue('warmup-emails');
    }

    public function handle(): void
    {
        $service = new EmailWarmupService();
        $schedule = $service->getSchedule();

        // Double-check warmup is active
        if ($schedule->status !== 'active') {
            $this->fail(new \Exception('Warmup not active'));
            return;
        }

        try {
            // Create mailable instance
            $seed = $this->employee->email . '|' . now()->format('Y-m-d H:i:s.u');
            $mailable = new $this->mailClass($this->employee, (int) $schedule->current_day, $seed);
            
            // Send to employee email (must specify recipient)
            Mail::to($this->employee->email)->send($mailable);

            // Record in warmup logs
            $service->recordEmailSent(
                $this->employee->email,
                $mailable->envelope()->subject ?? 'Welcome Email',
                null // Would need SMTP server to get message ID
            );
        } catch (\Exception $e) {
            // Record failure
            $service->recordEmailStatus(
                $this->employee->email,
                'bounced',
                $e->getMessage()
            );

            throw $e;
        }
    }
}
