<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            // Notifikasi Leave/Cuti
            $table->boolean('notify_leave_request')->default(true)->after('notify_checkout')->comment('Kirim notifikasi saat karyawan ajukan cuti/izin');
            $table->boolean('notify_leave_approved')->default(true)->after('notify_leave_request')->comment('Kirim notifikasi saat cuti disetujui');
            $table->boolean('notify_leave_rejected')->default(true)->after('notify_leave_approved')->comment('Kirim notifikasi saat cuti ditolak');

            // Template pesan
            $table->text('leave_request_template')->nullable()->after('checkout_template')->comment('Template notifikasi pengajuan cuti ke admin');
            $table->text('leave_approved_template')->nullable()->after('leave_request_template')->comment('Template notifikasi cuti disetujui ke karyawan');
            $table->text('leave_rejected_template')->nullable()->after('leave_approved_template')->comment('Template notifikasi cuti ditolak ke karyawan');

            // Admin phone untuk menerima notifikasi
            $table->string('admin_phone')->nullable()->after('sender')->comment('Nomor WhatsApp admin yang menerima notifikasi cuti');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn([
                'notify_leave_request',
                'notify_leave_approved',
                'notify_leave_rejected',
                'leave_request_template',
                'leave_approved_template',
                'leave_rejected_template',
                'admin_phone',
            ]);
        });
    }
};
