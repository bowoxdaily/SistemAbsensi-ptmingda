<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            // Dedicated approved Meta template for alpha notifications (supports {{1}}–{{5}})
            $table->string('kirim_alpha_template_name', 128)->nullable()->after('kirim_fallback_template_language');
            $table->string('kirim_alpha_template_language', 20)->nullable()->default('id')->after('kirim_alpha_template_name');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn(['kirim_alpha_template_name', 'kirim_alpha_template_language']);
        });
    }
};
