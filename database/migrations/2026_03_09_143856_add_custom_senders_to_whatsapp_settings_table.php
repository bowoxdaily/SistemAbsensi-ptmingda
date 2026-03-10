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
            // Custom sender numbers for each notification type
            // If null, will use default 'sender' field
            $table->string('checkin_sender', 20)->nullable()->after('sender')->comment('Nomor pengirim untuk notif absen masuk');
            $table->string('checkout_sender', 20)->nullable()->after('checkin_sender')->comment('Nomor pengirim untuk notif absen keluar');
            $table->string('leave_sender', 20)->nullable()->after('checkout_sender')->comment('Nomor pengirim untuk notif cuti/izin');
            $table->string('warning_letter_sender', 20)->nullable()->after('leave_sender')->comment('Nomor pengirim untuk notif SP');
            $table->string('payroll_sender', 20)->nullable()->after('warning_letter_sender')->comment('Nomor pengirim untuk notif payroll');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn([
                'checkin_sender',
                'checkout_sender',
                'leave_sender',
                'warning_letter_sender',
                'payroll_sender',
            ]);
        });
    }
};
