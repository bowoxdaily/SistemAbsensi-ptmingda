<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'lembur', 'off', and 'cuti_khusus' to attendance status enum
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'cuti', 'libur', 'cuti_bersama', 'lembur', 'off', 'cuti_khusus') DEFAULT 'hadir' COMMENT 'Status Kehadiran'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'lembur', 'off', and 'cuti_khusus' from enum
        // Convert any existing records before removing enum values
        DB::statement("UPDATE attendances SET status = 'hadir' WHERE status = 'lembur'");
        DB::statement("UPDATE attendances SET status = 'cuti' WHERE status IN ('off', 'cuti_khusus')");
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'cuti', 'libur', 'cuti_bersama') DEFAULT 'hadir' COMMENT 'Status Kehadiran'");
    }
};
