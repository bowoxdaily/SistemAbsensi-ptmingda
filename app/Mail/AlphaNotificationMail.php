<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class AlphaNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $employeeCode,
        public string $departmentName,
        public string $attendanceDate,
        public int $totalAlpha,
    ) {}

    public function envelope(): Envelope
    {
        $fromAddress = (string) config('mail.from_notifications.address', config('mail.from.address'));
        $fromName = (string) config('mail.from_notifications.name', config('mail.from.name'));
        $replyToAddress = (string) config('mail.reply_to_notifications.address', $fromAddress);
        $replyToName = (string) config('mail.reply_to_notifications.name', $fromName);

        return new Envelope(
            subject: 'Pemberitahuan Alpha - ' . $this->attendanceDate,
            from: new Address($fromAddress, $fromName),
            replyTo: [new Address($replyToAddress, $replyToName)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alpha_notification',
            text: 'emails.alpha_notification_text',
            with: [
                'recipientName' => $this->recipientName,
                'employeeCode' => $this->employeeCode,
                'departmentName' => $this->departmentName,
                'attendanceDate' => $this->attendanceDate,
                'totalAlpha' => $this->totalAlpha,
                'appName' => config('app.name', 'Sistem Absensi'),
                'appUrl' => config('app.url'),
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
