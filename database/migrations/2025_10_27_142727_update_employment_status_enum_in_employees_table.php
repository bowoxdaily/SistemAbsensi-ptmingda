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
        // Update existing data: convert "Magang" and "Outsource" to "Probation"
        DB::statement("UPDATE employees SET employment_status = 'Probation' WHERE employment_status IN ('Magang', 'Outsource')");
        
        // Alter the enum column to new values
        DB::statement("ALTER TABLE employees MODIFY COLUMN employment_status ENUM('Tetap', 'Kontrak', 'Probation') COMMENT 'Status kerja'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore old enum values
        DB::statement("ALTER TABLE employees MODIFY COLUMN employment_status ENUM('Tetap', 'Kontrak', 'Magang', 'Outsource') COMMENT 'Status kerja'");
        
        // Optionally restore the data (convert Probation back to Magang)
        DB::statement("UPDATE employees SET employment_status = 'Magang' WHERE employment_status = 'Probation'");
    }
};
