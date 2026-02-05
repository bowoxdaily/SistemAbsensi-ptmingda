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
            $table->string('api_url', 500)->nullable()->after('sn')
                ->comment('External API URL to fetch attlog data (pull mode)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fingerspot_settings', function (Blueprint $table) {
            $table->dropColumn('api_url');
        });
    }
};
