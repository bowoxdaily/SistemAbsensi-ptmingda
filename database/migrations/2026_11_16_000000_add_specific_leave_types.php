<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const LEAVE_STATUSES = "'cuti_menikah', 'cuti_duka', 'cuti_istri_melahirkan', 'cuti_keguguran', 'cuti_baptis_anak', 'cuti_khitanan_anak', 'cuti_menikahkan_anak', 'cuti_nasional'";

    public function up(): void
    {
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'cuti', 'libur', 'cuti_bersama', 'lembur', 'off', 'cuti_khusus', 'cuti_tahunan', 'cuti_umrah', 'skorsing', 'cuti_hamil', " . self::LEAVE_STATUSES . ") DEFAULT 'hadir' COMMENT 'Status Kehadiran'");
        DB::statement("ALTER TABLE leaves MODIFY COLUMN leave_type ENUM('cuti', 'izin', 'sakit', 'cuti_umrah', " . self::LEAVE_STATUSES . ") NOT NULL COMMENT 'Jenis cuti/izin'");
    }

    public function down(): void
    {
        DB::statement("UPDATE attendances SET status = 'cuti' WHERE status IN (" . self::LEAVE_STATUSES . ")");
        DB::statement("UPDATE leaves SET leave_type = 'cuti' WHERE leave_type IN (" . self::LEAVE_STATUSES . ")");
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'cuti', 'libur', 'cuti_bersama', 'lembur', 'off', 'cuti_khusus', 'cuti_tahunan', 'cuti_umrah', 'skorsing', 'cuti_hamil') DEFAULT 'hadir' COMMENT 'Status Kehadiran'");
        DB::statement("ALTER TABLE leaves MODIFY COLUMN leave_type ENUM('cuti', 'izin', 'sakit', 'cuti_umrah') NOT NULL COMMENT 'Jenis cuti/izin'");
    }
};
