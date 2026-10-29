<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'cuti', 'libur', 'cuti_bersama', 'lembur', 'off', 'cuti_khusus', 'cuti_tahunan', 'cuti_umrah') DEFAULT 'hadir' COMMENT 'Status Kehadiran'");
        DB::statement("ALTER TABLE leaves MODIFY COLUMN leave_type ENUM('cuti', 'izin', 'sakit', 'cuti_umrah') NOT NULL COMMENT 'Jenis cuti/izin'");
    }

    public function down(): void
    {
        DB::statement("UPDATE attendances SET status = 'cuti' WHERE status = 'cuti_umrah'");
        DB::statement("UPDATE leaves SET leave_type = 'cuti' WHERE leave_type = 'cuti_umrah'");
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'cuti', 'libur', 'cuti_bersama', 'lembur', 'off', 'cuti_khusus', 'cuti_tahunan') DEFAULT 'hadir' COMMENT 'Status Kehadiran'");
        DB::statement("ALTER TABLE leaves MODIFY COLUMN leave_type ENUM('cuti', 'izin', 'sakit') NOT NULL COMMENT 'Jenis cuti/izin'");
    }
};