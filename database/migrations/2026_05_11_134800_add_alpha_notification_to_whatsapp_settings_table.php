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
            $table->boolean('notify_alpha')->default(true)->after('notify_payroll');
            $table->text('alpha_template')->nullable()->after('payroll_template');
            $table->string('alpha_api_key', 255)->nullable()->after('payroll_api_key');
            $table->string('alpha_sender', 50)->nullable()->after('payroll_sender');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn(['notify_alpha', 'alpha_template', 'alpha_api_key', 'alpha_sender']);
        });
    }
};
