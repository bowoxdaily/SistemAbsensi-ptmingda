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
        // Add 'mangkir' and 'gagal_probation' to status enum (Status Karyawan)
        DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('active', 'inactive', 'resign', 'mangkir', 'gagal_probation') DEFAULT 'active' COMMENT 'Status Karyawan'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'mangkir' and 'gagal_probation' from enum
        // Convert any existing records to 'inactive' before removing enum values
        DB::statement("UPDATE employees SET status = 'inactive' WHERE status IN ('mangkir', 'gagal_probation')");
        DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('active', 'inactive', 'resign') DEFAULT 'active' COMMENT 'Status Karyawan'");
    }
};
