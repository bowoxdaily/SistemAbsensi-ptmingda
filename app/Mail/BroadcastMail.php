<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BroadcastMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $broadcastTitle,
        public string $broadcastMessage,
        public ?string $imageUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->broadcastTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.broadcast',
            with: [
                'recipientName'    => $this->recipientName,
                'broadcastTitle'   => $this->broadcastTitle,
                'broadcastMessage' => $this->broadcastMessage,
                'imageUrl'         => $this->imageUrl,
                'appName'          => config('app.name', 'Sistem Absensi'),
                'appUrl'           => config('app.url'),
            ],
        );
    }
}
