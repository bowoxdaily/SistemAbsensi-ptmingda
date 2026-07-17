<?php

namespace App\Notifications;

use App\Models\JoinCall;
use App\Services\EmailSmtpSettingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

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
        $fromAddress = (string) config('mail.from_interview.address', config('mail.from.address'));
        $fromName = (string) config('mail.from_interview.name', config('mail.from.name'));
        $replyToAddress = (string) config('mail.reply_to_interview.address', $fromAddress);
        $replyToName = (string) config('mail.reply_to_interview.name', $fromName);
        $templateService = app(EmailSmtpSettingService::class);
        $template = $templateService->getJoinCallEmailTemplateConfig();
        $variables = [
            '{nama}' => $this->joinCall->candidate_name,
            '{departemen}' => $department,
            '{sub_departemen}' => $department,
            '{sub_department}' => $department,
            '{posisi}' => $department,
            '{tanggal}' => $date,
            '{waktu}' => $time,
            '{lokasi}' => $this->joinCall->location,
            '{catatan}' => $this->joinCall->notes ?? '-',
            '{qr_url}' => $this->joinCall->qr_code_url ?? '',
        ];
        $subject = $templateService->renderTemplate($template['subject'], $variables);
        $body = $templateService->renderTemplate($template['body'], $variables);

        $mail = (new MailMessage)
            ->mailer('smtp_interview')
            ->from($fromAddress, $fromName)
            ->replyTo($replyToAddress, $replyToName)
            ->subject($subject);

        foreach ($this->bodyParagraphs($body) as $paragraph) {
            $mail->line(new HtmlString(nl2br(e($paragraph))));
        }

        if ($this->joinCall->qr_code_url) {
            $mail->action('Buka QR Check-in', $this->joinCall->qr_code_url)
                ->line('Silakan tunjukkan QR Code check-in kepada petugas keamanan saat tiba di lokasi.');
        }

        return $mail;
    }

    private function bodyParagraphs(string $body): array
    {
        return array_values(array_filter(
            preg_split('/\R{2,}/', trim($body)) ?: [],
            static fn (string $paragraph): bool => trim($paragraph) !== ''
        ));
    }
}