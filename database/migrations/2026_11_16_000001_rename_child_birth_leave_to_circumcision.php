<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'cuti', 'libur', 'cuti_bersama', 'lembur', 'off', 'cuti_khusus', 'cuti_tahunan', 'cuti_umrah', 'skorsing', 'cuti_hamil', 'cuti_menikah', 'cuti_duka', 'cuti_istri_melahirkan', 'cuti_keguguran', 'cuti_baptis_anak', 'cuti_kelahiran_anak', 'cuti_khitanan_anak', 'cuti_menikahkan_anak', 'cuti_nasional') DEFAULT 'hadir' COMMENT 'Status Kehadiran'");
        DB::statement("ALTER TABLE leaves MODIFY COLUMN leave_type ENUM('cuti', 'izin', 'sakit', 'cuti_umrah', 'cuti_menikah', 'cuti_duka', 'cuti_hamil', 'cuti_istri_melahirkan', 'cuti_tahunan', 'cuti_keguguran', 'cuti_baptis_anak', 'cuti_kelahiran_anak', 'cuti_khitanan_anak', 'cuti_menikahkan_anak', 'cuti_nasional') NOT NULL COMMENT 'Jenis cuti/izin'");
        DB::statement("UPDATE attendances SET status = 'cuti_khitanan_anak' WHERE status = 'cuti_kelahiran_anak'");
        DB::statement("UPDATE leaves SET leave_type = 'cuti_khitanan_anak' WHERE leave_type = 'cuti_kelahiran_anak'");
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'cuti', 'libur', 'cuti_bersama', 'lembur', 'off', 'cuti_khusus', 'cuti_tahunan', 'cuti_umrah', 'skorsing', 'cuti_hamil', 'cuti_menikah', 'cuti_duka', 'cuti_istri_melahirkan', 'cuti_keguguran', 'cuti_baptis_anak', 'cuti_khitanan_anak', 'cuti_menikahkan_anak', 'cuti_nasional') DEFAULT 'hadir' COMMENT 'Status Kehadiran'");
        DB::statement("ALTER TABLE leaves MODIFY COLUMN leave_type ENUM('cuti', 'izin', 'sakit', 'cuti_umrah', 'cuti_menikah', 'cuti_duka', 'cuti_hamil', 'cuti_istri_melahirkan', 'cuti_tahunan', 'cuti_keguguran', 'cuti_baptis_anak', 'cuti_khitanan_anak', 'cuti_menikahkan_anak', 'cuti_nasional') NOT NULL COMMENT 'Jenis cuti/izin'");
    }

    public function down(): void
    {
        DB::statement("UPDATE attendances SET status = 'cuti_kelahiran_anak' WHERE status = 'cuti_khitanan_anak'");
        DB::statement("UPDATE leaves SET leave_type = 'cuti_kelahiran_anak' WHERE leave_type = 'cuti_khitanan_anak'");
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'cuti', 'libur', 'cuti_bersama', 'lembur', 'off', 'cuti_khusus', 'cuti_tahunan', 'cuti_umrah', 'skorsing', 'cuti_hamil', 'cuti_menikah', 'cuti_duka', 'cuti_istri_melahirkan', 'cuti_keguguran', 'cuti_baptis_anak', 'cuti_kelahiran_anak', 'cuti_menikahkan_anak', 'cuti_nasional') DEFAULT 'hadir' COMMENT 'Status Kehadiran'");
        DB::statement("ALTER TABLE leaves MODIFY COLUMN leave_type ENUM('cuti', 'izin', 'sakit', 'cuti_umrah', 'cuti_menikah', 'cuti_duka', 'cuti_hamil', 'cuti_istri_melahirkan', 'cuti_tahunan', 'cuti_keguguran', 'cuti_baptis_anak', 'cuti_kelahiran_anak', 'cuti_menikahkan_anak', 'cuti_nasional') NOT NULL COMMENT 'Jenis cuti/izin'");
    }
};
