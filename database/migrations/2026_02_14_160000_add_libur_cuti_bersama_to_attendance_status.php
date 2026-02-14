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
        // Add 'libur' and 'cuti_bersama' to attendance status enum
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'cuti', 'libur', 'cuti_bersama') DEFAULT 'hadir' COMMENT 'Status Kehadiran'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'libur' and 'cuti_bersama' from enum
        // Convert any existing records to 'cuti' before removing enum values
        DB::statement("UPDATE attendances SET status = 'cuti' WHERE status IN ('libur', 'cuti_bersama')");
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'cuti') DEFAULT 'hadir' COMMENT 'Status Kehadiran'");
    }
};
