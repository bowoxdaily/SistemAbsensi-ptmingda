<?php

namespace App\Jobs;

use App\Mail\BroadcastMail;
use App\Models\BroadcastMessage;
use App\Services\EmailSmtpSettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendBroadcastEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $timeout = 60;

    public function __construct(
        public int $broadcastId,
        public string $email,
        public string $recipientName,
        public string $broadcastTitle,
        public string $broadcastMessage,
        public ?string $imageUrl = null,
    ) {}

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
        try {
            $smtpService = new EmailSmtpSettingService();
            $smtpService->applyMailer(EmailSmtpSettingService::CONTEXT_NOTIFICATIONS, 'smtp_notifications');

            Mail::mailer('smtp_notifications')->to($this->email)->send(new BroadcastMail(
                recipientName: $this->recipientName,
                broadcastTitle: $this->broadcastTitle,
                broadcastMessage: $this->broadcastMessage,
                imageUrl: $this->imageUrl,
            ));

            $broadcast = BroadcastMessage::find($this->broadcastId);
            if (!$broadcast) return;

            $broadcast->increment('sent_count');
            $this->checkCompletion($broadcast);
        } catch (Throwable $e) {
            $errorCategory = $this->classifyMailError($e);

            Log::warning("SendBroadcastEmailJob error broadcast #{$this->broadcastId} ({$errorCategory}): " . $e->getMessage(), [
                'email' => $this->email,
                'attempt' => $this->attempts(),
            ]);

            if ($this->isRetryableError($errorCategory)) {
                throw $e;
            }

            $broadcast = BroadcastMessage::find($this->broadcastId);
            if (!$broadcast) {
                return;
            }

            $broadcast->increment('failed_count');
            $this->checkCompletion($broadcast);
        }
    }

    public function failed(Throwable $exception): void
    {
        $broadcast = BroadcastMessage::find($this->broadcastId);
        if (!$broadcast) {
            return;
        }

        $broadcast->increment('failed_count');
        $this->checkCompletion($broadcast);

        Log::error("SendBroadcastEmailJob final failure broadcast #{$this->broadcastId}: " . $exception->getMessage(), [
            'email' => $this->email,
        ]);
    }

    private function checkCompletion(BroadcastMessage $broadcast): void
    {
        $processed = $broadcast->sent_count + $broadcast->failed_count;
        if ($processed >= $broadcast->total_recipients) {
            $broadcast->update([
                'status' => $broadcast->sent_count > 0 ? 'completed' : 'failed',
            ]);
            Log::info("Broadcast #{$this->broadcastId} (email) selesai. Terkirim: {$broadcast->sent_count}, Gagal: {$broadcast->failed_count}");
        }
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
