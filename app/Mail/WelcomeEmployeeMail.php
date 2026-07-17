<?php

namespace App\Mail;

use App\Models\Karyawans;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Password;

class WelcomeEmployeeMail extends Mailable
{
    use Queueable, SerializesModels;

    public Karyawans $employee;
    public string $resetUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Karyawans $employee)
    {
        $this->employee = $employee;

        // Generate a fresh password reset token so the employee can set their own password.
        // This is safer than sending the plain-text default password over email.
        $this->resetUrl = $this->buildResetUrl($employee);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $fromAddress = (string) config('mail.from_notifications.address', config('mail.from.address'));
        $fromName = (string) config('mail.from_notifications.name', config('mail.from.name'));

        return new Envelope(
            subject: '🎉 Selamat Datang di PT Mingda — Akun Anda Sudah Siap!',
            from: new Address($fromAddress, $fromName),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome_employee',
            with: [
                'employee' => $this->employee,
                'resetUrl' => $this->resetUrl,
                'appUrl'   => config('app.url'),
                'appName'  => config('app.name', 'Sistem Absensi'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Build a password reset URL for the employee's user account.
     * Falls back to the app URL if the user account doesn't exist.
     */
    private function buildResetUrl(Karyawans $employee): string
    {
        try {
            if ($employee->user && $employee->user->email) {
                $token = Password::createToken($employee->user);
                return url(route('password.reset', [
                    'token' => $token,
                    'email' => $employee->user->email,
                ], false));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Could not generate password reset URL for employee', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
            ]);
        }

        return config('app.url');
    }
}
