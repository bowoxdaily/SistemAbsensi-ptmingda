<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Headers;
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
        $fromAddress = (string) config('mail.from_notifications.address', config('mail.from.address'));
        $fromName = (string) config('mail.from_notifications.name', config('mail.from.name'));

        return new Envelope(
            subject: $this->broadcastTitle,
            from: new Address($fromAddress, $fromName),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.broadcast',
            text: 'emails.broadcast_text',
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

    public function headers(): Headers
    {
        return new Headers(text: [
            'X-Auto-Response-Suppress' => 'OOF, AutoReply',
            'Precedence' => 'bulk',
        ]);
    }
}
