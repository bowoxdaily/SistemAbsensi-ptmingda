<?php

namespace App\Services;

use App\Models\EmailSmtpSetting;
use Illuminate\Support\Facades\Schema;

class EmailSmtpSettingService
{
    public const CONTEXT_NOTIFICATIONS = 'notifications';
    public const CONTEXT_INTERVIEW = 'interview';

    private const INVITATION_TEMPLATE_COLUMNS = [
        'interview_subject_template',
        'interview_body_template',
        'join_call_subject_template',
        'join_call_body_template',
    ];

    public static function contexts(): array
    {
        return [
            self::CONTEXT_NOTIFICATIONS,
            self::CONTEXT_INTERVIEW,
        ];
    }

    public function isStorageReady(): bool
    {
        return Schema::hasTable('email_smtp_settings');
    }

    public function hasInvitationTemplateStorage(): bool
    {
        if (!$this->isStorageReady()) {
            return false;
        }

        foreach (self::INVITATION_TEMPLATE_COLUMNS as $column) {
            if (!Schema::hasColumn('email_smtp_settings', $column)) {
                return false;
            }
        }

        return true;
    }

    public function getContextConfig(string $context): array
    {
        if (!$this->isStorageReady()) {
            return $this->appendInvitationTemplateConfig($context, [
                'context' => $context,
                'is_custom' => false,
                'is_active' => false,
                'smtp_host' => (string) config('mail.mailers.smtp.host', '127.0.0.1'),
                'smtp_port' => (int) config('mail.mailers.smtp.port', 587),
                'smtp_encryption' => $this->normalizeEncryptionForUi(config('mail.mailers.smtp.scheme')),
                'smtp_username' => (string) config('mail.mailers.smtp.username', ''),
                'smtp_password' => (string) config('mail.mailers.smtp.password', ''),
                'from_address' => $this->fallbackFromAddress($context),
                'from_name' => $this->fallbackFromName($context),
            ], null);
        }

        $record = EmailSmtpSetting::query()->where('context', $context)->first();

        if ($record && $record->is_active && !empty($record->smtp_host) && !empty($record->smtp_username)) {
            return $this->appendInvitationTemplateConfig($context, [
                'context' => $context,
                'is_custom' => true,
                'is_active' => (bool) $record->is_active,
                'smtp_host' => (string) $record->smtp_host,
                'smtp_port' => (int) ($record->smtp_port ?: 587),
                'smtp_encryption' => $this->normalizeEncryptionForUi($record->smtp_encryption),
                'smtp_username' => (string) $record->smtp_username,
                'smtp_password' => (string) ($record->smtp_password ?? ''),
                'from_address' => (string) ($record->from_address ?: $this->fallbackFromAddress($context)),
                'from_name' => (string) ($record->from_name ?: $this->fallbackFromName($context)),
            ], $record);
        }

        return $this->appendInvitationTemplateConfig($context, [
            'context' => $context,
            'is_custom' => false,
            'is_active' => false,
            'smtp_host' => (string) config('mail.mailers.smtp.host', '127.0.0.1'),
            'smtp_port' => (int) config('mail.mailers.smtp.port', 587),
            'smtp_encryption' => $this->normalizeEncryptionForUi(config('mail.mailers.smtp.scheme')),
            'smtp_username' => (string) config('mail.mailers.smtp.username', ''),
            'smtp_password' => (string) config('mail.mailers.smtp.password', ''),
            'from_address' => $this->fallbackFromAddress($context),
            'from_name' => $this->fallbackFromName($context),
        ], $record);
    }

    public function getUiSettings(): array
    {
        $settings = [];

        foreach (self::contexts() as $context) {
            $config = $this->getContextConfig($context);
            $settings[$context] = [
                'context' => $context,
                'is_active' => (bool) $config['is_active'],
                'smtp_host' => $config['smtp_host'],
                'smtp_port' => $config['smtp_port'],
                'smtp_encryption' => $config['smtp_encryption'] ?: 'tls',
                'smtp_username' => $config['smtp_username'],
                'from_address' => $config['from_address'],
                'from_name' => $config['from_name'],
                'has_password' => !empty($config['smtp_password']),
                'using_custom' => (bool) $config['is_custom'],
                'interview_subject_template' => $config['interview_subject_template'] ?? self::getDefaultInterviewSubjectTemplate(),
                'interview_body_template' => $config['interview_body_template'] ?? self::getDefaultInterviewBodyTemplate(),
                'join_call_subject_template' => $config['join_call_subject_template'] ?? self::getDefaultJoinCallSubjectTemplate(),
                'join_call_body_template' => $config['join_call_body_template'] ?? self::getDefaultJoinCallBodyTemplate(),
                'template_storage_ready' => $this->hasInvitationTemplateStorage(),
            ];
        }

        return $settings;
    }

    public function getInterviewEmailTemplateConfig(): array
    {
        $config = $this->getContextConfig(self::CONTEXT_INTERVIEW);

        return [
            'subject' => $config['interview_subject_template'] ?? self::getDefaultInterviewSubjectTemplate(),
            'body' => $config['interview_body_template'] ?? self::getDefaultInterviewBodyTemplate(),
        ];
    }

    public function getJoinCallEmailTemplateConfig(): array
    {
        $config = $this->getContextConfig(self::CONTEXT_INTERVIEW);

        return [
            'subject' => $config['join_call_subject_template'] ?? self::getDefaultJoinCallSubjectTemplate(),
            'body' => $config['join_call_body_template'] ?? self::getDefaultJoinCallBodyTemplate(),
        ];
    }

    public function renderTemplate(string $template, array $variables): string
    {
        return str_replace(array_keys($variables), array_values($variables), $template);
    }

    public static function getDefaultInterviewSubjectTemplate(): string
    {
        return 'Undangan Interview - PT Mingda';
    }

    public static function getDefaultInterviewBodyTemplate(): string
    {
        return "Yth. {nama},\n\n"
            . "Berdasarkan hasil seleksi berkas Anda, kami mengundang Anda untuk mengikuti sesi interview untuk posisi {posisi}.\n\n"
            . "Tanggal: {tanggal}\n"
            . "Waktu: {waktu} WIB\n"
            . "Lokasi: {lokasi}\n\n"
            . "Catatan: {catatan}\n\n"
            . "Mohon konfirmasi kehadiran Anda dengan membalas email ini atau menghubungi HRD PT Mingda.\n\n"
            . "Terima kasih,\n"
            . "HRD PT Mingda";
    }

    public static function getDefaultJoinCallSubjectTemplate(): string
    {
        return 'Undangan Panggilan Join - PT Mingda';
    }

    public static function getDefaultJoinCallBodyTemplate(): string
    {
        return "Yth. {nama},\n\n"
            . "Selamat. Berdasarkan hasil seleksi, Anda dijadwalkan untuk hadir pada proses panggilan join PT Mingda untuk sub departemen {departemen}.\n\n"
            . "Tanggal: {tanggal}\n"
            . "Waktu: {waktu} WIB\n"
            . "Lokasi: {lokasi}\n\n"
            . "Catatan: {catatan}\n\n"
            . "Mohon konfirmasi kehadiran Anda dengan membalas email ini atau menghubungi HRD PT Mingda.\n\n"
            . "Terima kasih,\n"
            . "HRD PT Mingda";
    }

    public function applyMailer(string $context, string $mailerName): array
    {
        $config = $this->getContextConfig($context);
        $scheme = $this->toMailerScheme($config['smtp_encryption']);

        config([
            "mail.mailers.{$mailerName}" => [
                'transport' => 'smtp',
                'scheme' => $scheme,
                'url' => null,
                'host' => $config['smtp_host'],
                'port' => (int) $config['smtp_port'],
                'username' => $config['smtp_username'],
                'password' => $config['smtp_password'],
                'timeout' => null,
                'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST)),
            ],
            "mail.from_{$context}" => [
                'address' => $config['from_address'],
                'name' => $config['from_name'],
            ],
        ]);

        return $config;
    }

    private function fallbackFromAddress(string $context): string
    {
        return (string) config("mail.from_{$context}.address", config('mail.from.address', 'hello@example.com'));
    }

    private function fallbackFromName(string $context): string
    {
        return (string) config("mail.from_{$context}.name", config('mail.from.name', 'Example'));
    }

    private function appendInvitationTemplateConfig(string $context, array $config, ?EmailSmtpSetting $record): array
    {
        if ($context !== self::CONTEXT_INTERVIEW) {
            return $config;
        }

        $config['interview_subject_template'] = self::getDefaultInterviewSubjectTemplate();
        $config['interview_body_template'] = self::getDefaultInterviewBodyTemplate();
        $config['join_call_subject_template'] = self::getDefaultJoinCallSubjectTemplate();
        $config['join_call_body_template'] = self::getDefaultJoinCallBodyTemplate();

        if (!$record || !$this->hasInvitationTemplateStorage()) {
            return $config;
        }

        $config['interview_subject_template'] = (string) ($record->interview_subject_template ?: self::getDefaultInterviewSubjectTemplate());
        $config['interview_body_template'] = (string) ($record->interview_body_template ?: self::getDefaultInterviewBodyTemplate());
        $config['join_call_subject_template'] = (string) ($record->join_call_subject_template ?: self::getDefaultJoinCallSubjectTemplate());
        $config['join_call_body_template'] = (string) ($record->join_call_body_template ?: self::getDefaultJoinCallBodyTemplate());

        return $config;
    }

    private function normalizeEncryptionForUi(mixed $value): string
    {
        $value = strtolower((string) ($value ?? ''));

        return match ($value) {
            'ssl', 'smtps' => 'ssl',
            'none' => 'none',
            default => 'tls',
        };
    }

    private function toMailerScheme(string $encryption): string
    {
        return strtolower($encryption) === 'ssl' ? 'smtps' : 'smtp';
    }
}
