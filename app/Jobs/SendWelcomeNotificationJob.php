<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Karyawans;
use App\Mail\WelcomeEmployeeMail;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWelcomeNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $employee;
    protected $password;

    /** Maximum number of attempts before failing the job. */
    public int $tries = 3;

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
     * Sends both a WhatsApp welcome message and a welcome email to the new employee.
     */
    public function handle(WhatsAppService $waService): void
    {
        $employee = $this->employee;

        // ── 1. WhatsApp notification ──────────────────────────────────
        try {
            $waService->sendWelcomeNotification($employee, $this->password);
            Log::info('Welcome WA sent', ['employee_id' => $employee->id, 'name' => $employee->name]);
        } catch (\Exception $e) {
            Log::error('SendWelcomeNotificationJob: WA failed', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
            ]);
            // Do NOT rethrow — WA failure should not block email delivery.
        }

        // ── 2. Email notification ─────────────────────────────────────
        $emailAddress = $employee->email ?? ($employee->user->email ?? null);

        if (empty($emailAddress)) {
            Log::warning('SendWelcomeNotificationJob: No email address, skipping email', [
                'employee_id' => $employee->id,
            ]);
            return;
        }

        try {
            // Load relations needed by the Mailable (department, position, user).
            if (!$employee->relationLoaded('department')) {
                $employee->load(['department', 'position', 'user']);
            }

            Mail::to($emailAddress)
                ->send(new WelcomeEmployeeMail($employee));

            Log::info('Welcome email sent', [
                'employee_id' => $employee->id,
                'name'        => $employee->name,
                'email'       => $emailAddress,
            ]);
        } catch (\Exception $e) {
            Log::error('SendWelcomeNotificationJob: Email failed', [
                'employee_id' => $employee->id,
                'email'       => $emailAddress,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
