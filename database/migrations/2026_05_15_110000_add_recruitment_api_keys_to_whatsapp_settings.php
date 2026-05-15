<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            // Custom API key & sender for Interview notifications
            $table->string('interview_api_key')->nullable()->after('payroll_api_key')->comment('API key Fonnte untuk notif interview');
            $table->string('interview_sender', 20)->nullable()->after('payroll_sender')->comment('Nomor pengirim untuk notif interview');

            // Custom API key & sender for Join Call notifications
            $table->string('join_call_api_key')->nullable()->after('interview_api_key')->comment('API key Fonnte untuk notif panggilan join');
            $table->string('join_call_sender', 20)->nullable()->after('interview_sender')->comment('Nomor pengirim untuk notif panggilan join');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn([
                'interview_api_key',
                'interview_sender',
                'join_call_api_key',
                'join_call_sender',
            ]);
        });
    }
};
