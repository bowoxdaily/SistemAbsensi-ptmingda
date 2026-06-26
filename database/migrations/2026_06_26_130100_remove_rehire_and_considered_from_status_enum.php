<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("UPDATE employees SET status = 'inactive' WHERE status IN ('can_rehire', 'considered')");
        DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('active', 'inactive', 'resign', 'mangkir', 'gagal_probation', 'pending') DEFAULT 'active' COMMENT 'Status Karyawan'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('active', 'inactive', 'resign', 'mangkir', 'gagal_probation', 'pending', 'can_rehire', 'considered') DEFAULT 'active' COMMENT 'Status Karyawan'");
    }
};
