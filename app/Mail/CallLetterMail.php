<?php

namespace App\Mail;

use App\Models\CallLetter;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Storage;

class CallLetterMail extends Mailable
{
    public function __construct(public CallLetter $callLetter, private string $disk) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Surat Panggilan - ' . $this->callLetter->letter_number,
            from: new Address(
                (string) config('mail.from_notifications.address', config('mail.from.address')),
                (string) config('mail.from_notifications.name', config('mail.from.name')),
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.call_letter',
            text: 'emails.call_letter_text',
            with: [
                'callLetter' => $this->callLetter,
                'recipientName' => $this->callLetter->employee->name,
                'appName' => config('app.name', 'Sistem Absensi'),
            ],
        );
    }

    public function attachments(): array
    {
        return [Attachment::fromData(
            fn () => Storage::disk($this->disk)->get($this->callLetter->document_path),
            'Surat-Panggilan-' . $this->callLetter->letter_number . '.pdf'
        )->withMime('application/pdf')];
    }
}
