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
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('fonnte'); // fonnte, baileys
            $table->string('api_key')->nullable();
            $table->string('api_url')->nullable(); // For Baileys server URL
            $table->string('sender')->nullable(); // Phone number
            $table->boolean('is_enabled')->default(false);
            $table->boolean('notify_checkin')->default(true);
            $table->boolean('notify_checkout')->default(true);
            $table->text('checkin_template')->nullable();
            $table->text('checkout_template')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_settings');
    }
};
