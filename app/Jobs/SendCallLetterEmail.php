<?php

namespace App\Jobs;

use App\Mail\CallLetterMail;
use App\Models\CallLetter;
use App\Services\EmailSmtpSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;
use RuntimeException;

class SendCallLetterEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $callLetterId) {}

    public function handle(): void
    {
        $letter = CallLetter::with('employee')->findOrFail($this->callLetterId);
        if (!filter_var(trim((string) ($letter->employee->email ?? '')), FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Email karyawan tidak tersedia atau tidak valid.');
        }
        if (!$letter->document_path || !Storage::disk('r2')->exists($letter->document_path)) {
            throw new RuntimeException('File surat tidak ditemukan di R2.');
        }

        app(EmailSmtpSettingService::class)->applyMailer(EmailSmtpSettingService::CONTEXT_NOTIFICATIONS, 'smtp_notifications');
        Mail::mailer('smtp_notifications')->to(trim($letter->employee->email))->send(new CallLetterMail($letter, 'r2'));
        $letter->forceFill(['email_status' => 'sent', 'email_sent_at' => now(), 'email_error' => null])->save();
    }

    public function failed(Throwable $exception): void
    {
        CallLetter::whereKey($this->callLetterId)->update([
            'email_status' => 'failed',
            'email_error' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
        Log::error('Call letter queued email failed', ['call_letter_id' => $this->callLetterId, 'error' => $exception->getMessage()]);
    }
}
