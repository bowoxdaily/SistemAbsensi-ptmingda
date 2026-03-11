<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // employees: status filtered in almost every query
        Schema::table('employees', function (Blueprint $table) {
            $table->index('status', 'emp_status_idx');
            $table->index(['department_id', 'status'], 'emp_dept_status_idx');
            $table->index(['position_id', 'status'], 'emp_pos_status_idx');
        });

        // positions: status filtered when loading active positions/dropdowns
        // (name, level) used in ORDER BY name ASC, ISNULL(level), level ASC
        Schema::table('positions', function (Blueprint $table) {
            $table->index('status', 'pos_status_idx');
            $table->index(['name', 'level'], 'pos_name_level_idx');
        });

        // attendances: date-only range queries for rekap/report (without employee_id)
        // status filtered in summary/report queries
        Schema::table('attendances', function (Blueprint $table) {
            $table->index('attendance_date', 'att_date_idx');
            $table->index('status', 'att_status_idx');
        });

        // holidays: checked every alpha-generation run → WHERE date=X AND is_active=1
        Schema::table('holidays', function (Blueprint $table) {
            $table->index(['date', 'is_active'], 'hol_date_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('emp_status_idx');
            $table->dropIndex('emp_dept_status_idx');
            $table->dropIndex('emp_pos_status_idx');
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->dropIndex('pos_status_idx');
            $table->dropIndex('pos_name_level_idx');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('att_date_idx');
            $table->dropIndex('att_status_idx');
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropIndex('hol_date_active_idx');
        });
    }
};
