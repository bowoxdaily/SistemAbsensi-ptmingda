<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah composite index yang belum ada untuk query-query kritikal:
     *
     * 1. (employee_id, attendance_date) — untuk query absensi per karyawan per tanggal
     *    Digunakan di: karyawanDashboard, getMonthlyStats, show() karyawan
     *
     * 2. (employee_id, status) — untuk attendance summary per karyawan
     *    Digunakan di: KaryawanController::show() attendance_summary
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Composite index untuk query per-karyawan per-periode (paling sering dipakai)
            if (!$this->indexExists('attendances', 'att_emp_date_idx')) {
                $table->index(['employee_id', 'attendance_date'], 'att_emp_date_idx');
            }

            // Composite index untuk summary status per karyawan
            if (!$this->indexExists('attendances', 'att_emp_status_idx')) {
                $table->index(['employee_id', 'status'], 'att_emp_status_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('att_emp_date_idx');
            $table->dropIndex('att_emp_status_idx');
        });
    }

    /**
     * Cek apakah index sudah ada untuk menghindari error duplikasi.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = \Illuminate\Support\Facades\DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );

        return count($indexes) > 0;
    }
};
