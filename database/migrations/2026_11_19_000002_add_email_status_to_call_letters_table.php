<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_letters', function (Blueprint $table) {
            $table->string('email_status', 20)->default('pending');
            $table->timestamp('email_sent_at')->nullable();
            $table->text('email_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('call_letters', function (Blueprint $table) {
            $table->dropColumn(['email_status', 'email_sent_at', 'email_error']);
        });
    }
};
