<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'cuti', 'libur', 'cuti_bersama', 'lembur', 'off', 'cuti_khusus', 'cuti_tahunan', 'cuti_umrah', 'skorsing') DEFAULT 'hadir' COMMENT 'Status Kehadiran'");
    }

    public function down(): void
    {
        DB::statement("UPDATE attendances SET status = 'alpha' WHERE status = 'skorsing'");
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'cuti', 'libur', 'cuti_bersama', 'lembur', 'off', 'cuti_khusus', 'cuti_tahunan', 'cuti_umrah') DEFAULT 'hadir' COMMENT 'Status Kehadiran'");
    }
};