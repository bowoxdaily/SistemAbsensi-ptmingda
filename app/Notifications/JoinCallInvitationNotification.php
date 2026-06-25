<?php

namespace App\Notifications;

use App\Models\JoinCall;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JoinCallInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(protected JoinCall $joinCall)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = Carbon::parse($this->joinCall->join_call_date)->locale('id')->translatedFormat('l, d F Y');
        $time = Carbon::parse($this->joinCall->join_call_time)->format('H:i');
        $department = $this->joinCall->subDepartment?->name ?? '-';

        $mail = (new MailMessage)
            ->subject('Undangan Panggilan Join - PT Mingda')
            ->greeting('Yth. ' . $this->joinCall->candidate_name . ',')
            ->line('Selamat. Berdasarkan hasil seleksi, Anda dijadwalkan untuk hadir pada proses panggilan join PT Mingda untuk sub departemen ' . $department . '.')
            ->line('Tanggal: ' . $date)
            ->line('Waktu: ' . $time . ' WIB')
            ->line('Lokasi: ' . $this->joinCall->location);

        if ($this->joinCall->notes) {
            $mail->line('Catatan: ' . $this->joinCall->notes);
        }

        if ($this->joinCall->qr_code_url) {
            $mail->action('Buka QR Check-in', $this->joinCall->qr_code_url)
                ->line('Silakan tunjukkan QR Code check-in kepada petugas keamanan saat tiba di lokasi.');
        }

        return $mail
            ->line('Mohon konfirmasi kehadiran Anda dengan membalas email ini atau menghubungi HRD PT Mingda.')
            ->salutation('Terima kasih, HRD PT Mingda');
    }
}