<?php

namespace App\Jobs;

use App\Mail\AlphaNotificationMail;
use App\Models\Attendance;
use App\Services\EmailSmtpSettingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAlphaNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 60;

    public function __construct(public int $attendanceId)
    {
    }

    public function middleware(): array
    {
        return [
            new RateLimited('lark-outbound-email'),
        ];
    }

    public function backoff(): array
    {
        return [15, 60, 180, 600];
    }

    public function handle(): void
    {
        $attendance = Attendance::with(['employee.department'])->find($this->attendanceId);

        if (!$attendance) {
            Log::warning('SendAlphaNotificationEmailJob skipped: attendance not found', [
                'attendance_id' => $this->attendanceId,
            ]);
            return;
        }

        if ($attendance->status !== 'alpha') {
            Log::info('SendAlphaNotificationEmailJob skipped: status is not alpha', [
                'attendance_id' => $this->attendanceId,
                'status' => $attendance->status,
            ]);
            return;
        }

        $employee = $attendance->employee;
        $emailAddress = trim((string) ($employee->email ?? ''));

        if (empty($emailAddress)) {
            Log::warning('SendAlphaNotificationEmailJob skipped: employee email not found', [
                'attendance_id' => $this->attendanceId,
                'employee_id' => $attendance->employee_id,
            ]);
            return;
        }

        try {
            $attendanceDate = Carbon::parse($attendance->attendance_date);

            $totalAlphaThisMonth = Attendance::where('employee_id', $employee->id)
                ->where('status', 'alpha')
                ->whereYear('attendance_date', $attendanceDate->year)
                ->whereMonth('attendance_date', $attendanceDate->month)
                ->count();

            $formattedDate = $attendanceDate
                ->locale('id')
                ->translatedFormat('l, d F Y');

            $smtpService = new EmailSmtpSettingService();
            $smtpService->applyMailer(EmailSmtpSettingService::CONTEXT_NOTIFICATIONS, 'smtp_notifications');

            Mail::mailer('smtp_notifications')->to($emailAddress)->send(new AlphaNotificationMail(
                recipientName: (string) ($employee->name ?? 'Karyawan'),
                employeeCode: (string) ($employee->employee_code ?? '-'),
                departmentName: (string) ($employee->department->name ?? '-'),
                attendanceDate: $formattedDate,
                totalAlpha: (int) $totalAlphaThisMonth,
            ));

            Log::info('Alpha notification email sent', [
                'attendance_id' => $this->attendanceId,
                'employee_id' => $attendance->employee_id,
                'email' => $emailAddress,
                'total_alpha' => $totalAlphaThisMonth,
            ]);
        } catch (Throwable $e) {
            $errorCategory = $this->classifyMailError($e);

            Log::warning('SendAlphaNotificationEmailJob error (' . $errorCategory . '): ' . $e->getMessage(), [
                'attendance_id' => $this->attendanceId,
                'email' => $emailAddress,
                'attempt' => $this->attempts(),
            ]);

            if ($this->isRetryableError($errorCategory)) {
                throw $e;
            }
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SendAlphaNotificationEmailJob final failure: ' . $exception->getMessage(), [
            'attendance_id' => $this->attendanceId,
        ]);
    }

    private function classifyMailError(Throwable $exception): string
    {
        $message = strtolower($exception->getMessage());

        if (
            str_contains($message, 'frequency limited')
            || str_contains($message, 'rate limit exceed')
            || str_contains($message, 'web server is down')
            || str_contains($message, '450 ')
            || str_contains($message, '451 ')
            || str_contains($message, '521 ')
            || str_contains($message, '908')
        ) {
            return 'rate-limit-or-temporary';
        }

        if (
            str_contains($message, 'verify address failed')
            || str_contains($message, 'user not found')
            || str_contains($message, 'invalid mail address')
            || str_contains($message, 'empty group members')
        ) {
            return 'invalid-recipient';
        }

        if (
            str_contains($message, 'rejected by antispam system')
            || str_contains($message, 'rejected by operation antispam rules')
            || str_contains($message, 'reject by blocklist')
        ) {
            return 'antispam-rejection';
        }

        return 'other';
    }

    private function isRetryableError(string $category): bool
    {
        return $category === 'rate-limit-or-temporary';
    }
}
