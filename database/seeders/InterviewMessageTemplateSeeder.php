<?php

namespace Database\Seeders;

use App\Models\InterviewMessageTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InterviewMessageTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Template Default (Formal)',
                'is_default' => true,
                'message_template' => "*Undangan Interview - PT Mingda*

Kepada Yth,
*{nama}*

Berdasarkan hasil seleksi berkas Anda, kami mengundang Anda untuk mengikuti sesi interview untuk posisi *{posisi}*.

📅 *Tanggal:* {tanggal}
🕐 *Waktu:* {waktu} WIB
📍 *Lokasi:* {lokasi}

📝 *Catatan:*
{catatan}

Mohon konfirmasi kehadiran Anda dengan membalas pesan ini.

Terima kasih dan sampai jumpa di hari interview.

*HRD PT Mingda*",
            ],
            [
                'name' => 'Template Friendly',
                'is_default' => false,
                'message_template' => "Halo *{nama}*! 👋

Kabar baik nih! Kamu lolos tahap administrasi untuk posisi *{posisi}* di PT Mingda.

Kami undang kamu interview ya:
📅 {tanggal}
⏰ {waktu} WIB
📍 {lokasi}

{catatan}

Mohon konfirmasi kehadirannya dengan reply di sini ya!

Sampai jumpa! 😊

Tim HRD PT Mingda",
            ],
            [
                'name' => 'Template Profesional',
                'is_default' => false,
                'message_template' => "*Interview Invitation - PT Mingda*

Dear *{nama}*,

We are pleased to invite you for an interview session for the position of *{posisi}*.

*Schedule Details:*
📅 Date: {tanggal}
🕐 Time: {waktu} WIB
📍 Venue: {lokasi}

*Additional Information:*
{catatan}

Please confirm your attendance by replying to this message.

Best regards,
*HR Department - PT Mingda*",
            ],
            [
                'name' => 'Template Singkat',
                'is_default' => false,
                'message_template' => "Hi *{nama}*,

Interview *{posisi}*:
📅 {tanggal}
⏰ {waktu}
📍 {lokasi}

Konfirmasi ya!

HRD PT Mingda",
            ],
        ];

        foreach ($templates as $template) {
            InterviewMessageTemplate::create($template);
        }
    }
}
