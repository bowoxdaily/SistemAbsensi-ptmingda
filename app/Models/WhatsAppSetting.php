<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppSetting extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_settings';

    protected $fillable = [
        'provider',
        'api_key',
        'api_url',
        'sender',
        'admin_phone',
        'is_enabled',
        'notify_checkin',
        'notify_checkout',
        'send_checkin_photo',
        'send_checkout_photo',
        'notify_leave_request',
        'notify_leave_approved',
        'notify_leave_rejected',
        'checkin_template',
        'checkout_template',
        'leave_request_template',
        'leave_approved_template',
        'leave_rejected_template',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'notify_checkin' => 'boolean',
        'notify_checkout' => 'boolean',
        'send_checkin_photo' => 'boolean',
        'send_checkout_photo' => 'boolean',
        'notify_leave_request' => 'boolean',
        'notify_leave_approved' => 'boolean',
        'notify_leave_rejected' => 'boolean',
    ];

    /**
     * Get the active WhatsApp settings
     */
    public static function getActive()
    {
        return self::where('is_enabled', true)->first();
    }

    /**
     * Get default check-in template
     */
    public static function getDefaultCheckinTemplate()
    {
        return "✅ *Absen Masuk Berhasil*\n\n" .
            "Nama: {name}\n" .
            "Waktu: {time}\n" .
            "Status: {status}\n" .
            "Lokasi: {location}\n\n" .
            "_Sistem Absensi Karyawan_";
    }

    /**
     * Get default check-out template
     */
    public static function getDefaultCheckoutTemplate()
    {
        return "🏁 *Absen Keluar Berhasil*\n\n" .
            "Nama: {name}\n" .
            "Waktu: {time}\n" .
            "Durasi Kerja: {duration}\n" .
            "Lokasi: {location}\n\n" .
            "_Sistem Absensi Karyawan_";
    }

    /**
     * Check if provider is Fonnte
     */
    public function isFonnte()
    {
        return $this->provider === 'fonnte';
    }

    /**
     * Check if provider is Baileys
     */
    public function isBaileys()
    {
        return $this->provider === 'baileys';
    }

    /**
     * Get default leave request template (notifikasi ke admin)
     */
    public static function getDefaultLeaveRequestTemplate()
    {
        return "📋 *Pengajuan Cuti/Izin Baru*\n\n" .
            "Nama: {employee_name}\n" .
            "Kode Karyawan: {employee_nip}\n" .
            "Jenis: {leave_type}\n" .
            "Tanggal: {start_date} s/d {end_date}\n" .
            "Durasi: {total_days} hari\n" .
            "Alasan: {reason}\n\n" .
            "_Silakan cek dan approve di admin panel_";
    }

    /**
     * Get default leave approved template (notifikasi ke karyawan)
     */
    public static function getDefaultLeaveApprovedTemplate()
    {
        return "✅ *Pengajuan Cuti DISETUJUI*\n\n" .
            "Halo {employee_name},\n\n" .
            "Pengajuan {leave_type} Anda telah disetujui.\n\n" .
            "Detail:\n" .
            "Tanggal: {start_date} s/d {end_date}\n" .
            "Durasi: {total_days} hari\n" .
            "Disetujui oleh: {approved_by}\n" .
            "Tanggal Approval: {approved_at}\n\n" .
            "_Selamat menikmati waktu istirahat Anda_";
    }

    /**
     * Get default leave rejected template (notifikasi ke karyawan)
     */
    public static function getDefaultLeaveRejectedTemplate()
    {
        return "❌ *Pengajuan Cuti DITOLAK*\n\n" .
            "Halo {employee_name},\n\n" .
            "Mohon maaf, pengajuan {leave_type} Anda ditolak.\n\n" .
            "Detail:\n" .
            "Tanggal: {start_date} s/d {end_date}\n" .
            "Durasi: {total_days} hari\n" .
            "Alasan Penolakan: {rejection_reason}\n" .
            "Ditolak oleh: {approved_by}\n\n" .
            "_Silakan hubungi HRD untuk informasi lebih lanjut_";
    }
}
