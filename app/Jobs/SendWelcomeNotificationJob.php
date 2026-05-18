<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Karyawans;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class SendWelcomeNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $employee;
    protected $password;

    /**
     * Create a new job instance.
     */
    public function __construct(Karyawans $employee, $password = 'password123')
    {
        $this->employee = $employee;
        $this->password = $password;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $waService): void
    {
        try {
            $waService->sendWelcomeNotification($this->employee, $this->password);
        } catch (\Exception $e) {
            Log::error('Job SendWelcomeNotificationJob Error: ' . $e->getMessage());
        }
    }
}
