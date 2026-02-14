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
        // Add 'Mangkir' and 'Gagal Probation' to employment_status enum
        DB::statement("ALTER TABLE employees MODIFY COLUMN employment_status ENUM('Tetap', 'Kontrak', 'Probation', 'Mangkir', 'Gagal Probation') COMMENT 'Status kerja'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'Mangkir' and 'Gagal Probation' from enum
        // Note: Data with these values will be set to NULL
        DB::statement("UPDATE employees SET employment_status = 'Kontrak' WHERE employment_status IN ('Mangkir', 'Gagal Probation')");
        DB::statement("ALTER TABLE employees MODIFY COLUMN employment_status ENUM('Tetap', 'Kontrak', 'Probation') COMMENT 'Status kerja'");
    }
};
