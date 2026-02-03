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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('fingerspot_pin', 20)->nullable()->after('employee_code')
                ->comment('PIN used in Fingerspot device for this employee');
            $table->index('fingerspot_pin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['fingerspot_pin']);
            $table->dropColumn('fingerspot_pin');
        });
    }
};
