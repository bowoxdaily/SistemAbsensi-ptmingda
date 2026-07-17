<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_smtp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('context', 30)->unique(); // notifications | interview
            $table->string('smtp_host', 191)->nullable();
            $table->unsignedSmallInteger('smtp_port')->nullable();
            $table->string('smtp_encryption', 20)->nullable(); // tls | ssl | none
            $table->string('smtp_username', 191)->nullable();
            $table->text('smtp_password')->nullable(); // encrypted at application layer
            $table->string('from_address', 191)->nullable();
            $table->string('from_name', 191)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_smtp_settings');
    }
};
