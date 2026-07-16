<?php

namespace App\Mail;

use App\Models\Karyawans;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Simplified email template for warmup system.
 * Uses plain-text and minimal HTML to avoid spam filters.
 * Designed to establish sender reputation with ISPs.
 */
class WarmupEmployeeMail extends Mailable
{
    use Queueable, SerializesModels;

    public Karyawans $employee;

    /**
     * Create a new message instance.
     */
    public function __construct(Karyawans $employee)
    {
        $this->employee = $employee;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('mail.from.address', 'noreply@mingda.id'),
            subject: 'Selamat Datang di PT Mingda',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            text: 'emails.warmup_employee_text',
            with: [
                'employee' => $this->employee,
                'appName' => config('app.name', 'Sistem Absensi'),
            ],
        );
    }
}
