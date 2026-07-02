<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            // Name of the approved Meta template to use when free-form fails due to 24h window (error 131047)
            $table->string('kirim_fallback_template_name', 128)->nullable()->after('kirim_phone_number_id');
            // Language code for the fallback template (e.g. 'id', 'en')
            $table->string('kirim_fallback_template_language', 20)->nullable()->default('id')->after('kirim_fallback_template_name');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn(['kirim_fallback_template_name', 'kirim_fallback_template_language']);
        });
    }
};
