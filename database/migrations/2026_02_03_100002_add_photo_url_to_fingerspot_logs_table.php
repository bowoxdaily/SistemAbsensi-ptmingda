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
        Schema::table('fingerspot_logs', function (Blueprint $table) {
            $table->string('photo_url', 500)->nullable()->after('sn')
                ->comment('Photo URL from Fingerspot Cloud');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fingerspot_logs', function (Blueprint $table) {
            $table->dropColumn('photo_url');
        });
    }
};
