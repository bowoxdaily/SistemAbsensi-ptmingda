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
            $table->boolean('notify_welcome')->default(true);
            $table->string('welcome_api_key')->nullable();
            $table->string('welcome_sender')->nullable();
            $table->text('welcome_template')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn([
                'notify_welcome',
                'welcome_api_key',
                'welcome_sender',
                'welcome_template'
            ]);
        });
    }
};
