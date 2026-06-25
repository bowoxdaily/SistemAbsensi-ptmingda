<?php

namespace App\Notifications;

use App\Models\Interview;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(protected Interview $interview)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = Carbon::parse($this->interview->interview_date)->locale('id')->translatedFormat('l, d F Y');
        $time = Carbon::parse($this->interview->interview_time)->format('H:i');

        $mail = (new MailMessage)
            ->subject('Undangan Interview - PT Mingda')
            ->greeting('Yth. ' . $this->interview->candidate_name . ',')
            ->line('Berdasarkan hasil seleksi berkas Anda, kami mengundang Anda untuk mengikuti sesi interview untuk posisi ' . $this->interview->position->name . '.')
            ->line('Tanggal: ' . $date)
            ->line('Waktu: ' . $time . ' WIB')
            ->line('Lokasi: ' . $this->interview->location);

        if ($this->interview->notes) {
            $mail->line('Catatan: ' . $this->interview->notes);
        }

        if ($this->interview->qr_code_url) {
            $mail->action('Buka QR Check-in', $this->interview->qr_code_url)
                ->line('Silakan tunjukkan QR Code check-in kepada petugas keamanan saat tiba di lokasi.');
        }

        return $mail
            ->line('Mohon konfirmasi kehadiran Anda dengan membalas email ini atau menghubungi HRD PT Mingda.')
            ->salutation('Terima kasih, HRD PT Mingda');
    }
}