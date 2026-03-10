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
            $table->boolean('notify_warning_letter')->default(false)->after('notify_leave_rejected')->comment('Aktifkan notifikasi SP');
            $table->text('warning_letter_template')->nullable()->after('notify_warning_letter')->comment('Template pesan SP');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn(['notify_warning_letter', 'warning_letter_template']);
        });
    }
};
