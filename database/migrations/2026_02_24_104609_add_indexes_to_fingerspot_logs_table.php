<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fingerspot_logs', function (Blueprint $table) {
            // Index for ORDER BY scan_time DESC (default sort)
            $table->index('scan_time', 'fl_scan_time_idx');
            // Index for ORDER BY created_at DESC
            $table->index('created_at', 'fl_created_at_idx');
            // Index for employee_id (used in eager load JOIN)
            $table->index('employee_id', 'fl_employee_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fingerspot_logs', function (Blueprint $table) {
            $table->dropIndex('fl_scan_time_idx');
            $table->dropIndex('fl_created_at_idx');
            $table->dropIndex('fl_employee_id_idx');
        });
    }
};
