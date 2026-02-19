<?php

namespace App\Jobs;

use App\Models\BroadcastMessage;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 60;

    public function __construct(
        public int $broadcastId,
        public string $phone,
        public string $message,
        public ?string $imageUrl = null,
    ) {}

    public function handle(): void
    {
        $whatsapp = new WhatsAppService();

        try {
            $result = $whatsapp->send($this->phone, $this->message, $this->imageUrl);

            $broadcast = BroadcastMessage::find($this->broadcastId);
            if (!$broadcast) return;

            if ($result) {
                $broadcast->increment('sent_count');
            } else {
                $broadcast->increment('failed_count');
            }

            // Check if all messages processed — mark as completed
            $processed = $broadcast->sent_count + $broadcast->failed_count;
            if ($processed >= $broadcast->total_recipients) {
                $broadcast->update(['status' => $broadcast->sent_count > 0 ? 'completed' : 'failed']);
                Log::info("Broadcast #{$this->broadcastId} selesai. Terkirim: {$broadcast->sent_count}, Gagal: {$broadcast->failed_count}");
            }
        } catch (\Exception $e) {
            Log::error("SendBroadcastJob error broadcast #{$this->broadcastId}: " . $e->getMessage());

            $broadcast = BroadcastMessage::find($this->broadcastId);
            if ($broadcast) {
                $broadcast->increment('failed_count');
                $processed = $broadcast->sent_count + $broadcast->failed_count;
                if ($processed >= $broadcast->total_recipients) {
                    $broadcast->update(['status' => $broadcast->sent_count > 0 ? 'completed' : 'failed']);
                }
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendBroadcastJob permanently failed broadcast #{$this->broadcastId}: " . $exception->getMessage());
        $broadcast = BroadcastMessage::find($this->broadcastId);
        if ($broadcast) {
            $broadcast->increment('failed_count');
        }
    }
}
