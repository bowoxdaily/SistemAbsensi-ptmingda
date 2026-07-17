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
    public int $warmupDay;
    public string $seed;

    /**
     * Create a new message instance.
     */
    public function __construct(Karyawans $employee, int $warmupDay = 1, ?string $seed = null)
    {
        $this->employee = $employee;
        $this->warmupDay = $warmupDay;
        $this->seed = $seed ?? ($employee->email . '|' . now()->format('Y-m-d H:i:s'));
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subjects = [
            'Informasi Akun Absensi PT Mingda',
            'Pemberitahuan Aktivasi Akun Karyawan',
            'Akun Sistem Absensi Sudah Tersedia',
            'Panduan Awal Penggunaan Sistem Absensi',
            'Konfirmasi Data Akun Absensi Karyawan',
        ];

        $index = $this->variantIndex(count($subjects));

        return new Envelope(
            from: config('mail.from.address', 'noreply@mingda.id'),
            subject: $subjects[$index],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $openings = [
            'Terima kasih telah bergabung bersama PT Mingda.',
            'Kami menyampaikan informasi akun Anda di sistem absensi.',
            'Email ini berisi konfirmasi bahwa akun absensi Anda sudah aktif.',
            'Berikut pemberitahuan terkait akses awal ke sistem absensi karyawan.',
            'Kami mengirimkan update aktivasi akun untuk kebutuhan absensi harian.',
        ];

        $mainPoints = [
            'Silakan gunakan akun ini saat melakukan absensi kerja sesuai jadwal.',
            'Mohon pastikan data profil Anda di sistem sudah benar sebelum digunakan.',
            'Jika ada ketidaksesuaian data, segera informasikan ke tim HRD.',
            'Akses sistem ini diperlukan untuk kehadiran, izin, dan rekap absensi.',
            'Gunakan akun secara pribadi dan jangan dibagikan ke pihak lain.',
        ];

        $closings = [
            'Jika memerlukan bantuan, hubungi HRD melalui kanal internal.',
            'Untuk pertanyaan lanjutan, tim HRD siap membantu Anda.',
            'Silakan balas email ini jika Anda membutuhkan klarifikasi.',
            'Terima kasih atas perhatian dan kerja samanya.',
            'Semoga proses onboarding Anda berjalan lancar.',
        ];

        return new Content(
            text: 'emails.warmup_employee_text',
            with: [
                'employee' => $this->employee,
                'appName' => config('app.name', 'Sistem Absensi'),
                'opening' => $openings[$this->variantIndex(count($openings), 1)],
                'mainPoint' => $mainPoints[$this->variantIndex(count($mainPoints), 2)],
                'closing' => $closings[$this->variantIndex(count($closings), 3)],
                'warmupDay' => $this->warmupDay,
            ],
        );
    }

    private function variantIndex(int $poolSize, int $salt = 0): int
    {
        $hash = crc32($this->seed . '|' . $this->employee->email . '|' . $this->warmupDay . '|' . $salt);
        return abs((int) $hash) % $poolSize;
    }
}
