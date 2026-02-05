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
        Schema::table('fingerspot_settings', function (Blueprint $table) {
            $table->boolean('auto_sync')->default(false)->after('is_active');
            $table->integer('sync_interval')->default(5)->after('auto_sync'); // in minutes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fingerspot_settings', function (Blueprint $table) {
            $table->dropColumn(['auto_sync', 'sync_interval']);
        });
    }
};
