<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class EmailSmtpSetting extends Model
{
    use HasFactory;

    protected $table = 'email_smtp_settings';

    protected $fillable = [
        'context',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'from_address',
        'from_name',
        'reply_to_address',
        'reply_to_name',
        'interview_subject_template',
        'interview_body_template',
        'join_call_subject_template',
        'join_call_body_template',
        'is_active',
        'mailgun_api_key',
        'mailgun_domain',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function setSmtpPasswordAttribute($value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['smtp_password'] = Crypt::encryptString((string) $value);
    }

    public function getSmtpPasswordAttribute($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function setMailgunApiKeyAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['mailgun_api_key'] = null;
            return;
        }

        $this->attributes['mailgun_api_key'] = Crypt::encryptString((string) $value);
    }

    public function getMailgunApiKeyDecrypted(): ?string
    {
        $value = $this->attributes['mailgun_api_key'] ?? null;

        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
