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
        // Add 'pending' to status enum (Status Karyawan)
        DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('active', 'inactive', 'resign', 'mangkir', 'gagal_probation', 'pending') DEFAULT 'active' COMMENT 'Status Karyawan'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert any existing 'pending' records to 'inactive' before removing enum value
        DB::statement("UPDATE employees SET status = 'inactive' WHERE status = 'pending'");
        DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('active', 'inactive', 'resign', 'mangkir', 'gagal_probation') DEFAULT 'active' COMMENT 'Status Karyawan'");
    }
};
