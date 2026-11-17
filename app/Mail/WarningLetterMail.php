<?php

namespace App\Mail;

use App\Models\WarningLetter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class WarningLetterMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WarningLetter $warningLetter) {}

    public function envelope(): Envelope
    {
        $fromAddress = (string) config('mail.from_notifications.address', config('mail.from.address'));
        $fromName = (string) config('mail.from_notifications.name', config('mail.from.name'));

        return new Envelope(
            subject: 'Surat Peringatan ' . $this->warningLetter->sp_type . ' - ' . $this->warningLetter->sp_number,
            from: new Address($fromAddress, $fromName),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.warning_letter',
            text: 'emails.warning_letter_text',
            with: [
                'warningLetter' => $this->warningLetter,
                'recipientName' => $this->warningLetter->employee->name,
                'appName' => config('app.name', 'Sistem Absensi'),
            ],
        );
    }
}

