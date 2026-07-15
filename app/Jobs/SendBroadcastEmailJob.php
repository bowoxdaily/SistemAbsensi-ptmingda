<?php

namespace App\Jobs;

use App\Mail\BroadcastMail;
use App\Models\BroadcastMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBroadcastEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 60;

    public function __construct(
        public int $broadcastId,
        public string $email,
        public string $recipientName,
        public string $broadcastTitle,
        public string $broadcastMessage,
        public ?string $imageUrl = null,
    ) {}

    public function handle(): void
    {
        try {
            Mail::to($this->email)->send(new BroadcastMail(
                recipientName: $this->recipientName,
                broadcastTitle: $this->broadcastTitle,
                broadcastMessage: $this->broadcastMessage,
                imageUrl: $this->imageUrl,
            ));

            $broadcast = BroadcastMessage::find($this->broadcastId);
            if (!$broadcast) return;

            $broadcast->increment('sent_count');
            $this->checkCompletion($broadcast);
        } catch (\Exception $e) {
            Log::error("SendBroadcastEmailJob error broadcast #{$this->broadcastId}: " . $e->getMessage());

            $broadcast = BroadcastMessage::find($this->broadcastId);
            if ($broadcast) {
                $broadcast->increment('failed_count');
                $this->checkCompletion($broadcast);
            }
        }
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
}
