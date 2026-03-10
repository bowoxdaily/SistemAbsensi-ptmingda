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
            // Multiple API keys for different notification types
            // If null, will use default 'api_key' field
            $table->string('checkin_api_key')->nullable()->after('api_key')->comment('API key Fonnte untuk notif absen masuk');
            $table->string('checkout_api_key')->nullable()->after('checkin_api_key')->comment('API key Fonnte untuk notif absen keluar');
            $table->string('leave_api_key')->nullable()->after('checkout_api_key')->comment('API key Fonnte untuk notif cuti/izin');
            $table->string('warning_letter_api_key')->nullable()->after('leave_api_key')->comment('API key Fonnte untuk notif SP');
            $table->string('payroll_api_key')->nullable()->after('warning_letter_api_key')->comment('API key Fonnte untuk notif payroll');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn([
                'checkin_api_key',
                'checkout_api_key',
                'leave_api_key',
                'warning_letter_api_key',
                'payroll_api_key',
            ]);
        });
    }
};
