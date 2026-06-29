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
        DB::statement("ALTER TABLE employees MODIFY COLUMN termination_recommendation ENUM('can_rehire', 'considered', 'not_recommended', 'blacklist') NULL AFTER tanggal_pending");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE employees SET termination_recommendation = NULL WHERE termination_recommendation = 'not_recommended'");
        DB::statement("ALTER TABLE employees MODIFY COLUMN termination_recommendation ENUM('can_rehire', 'considered', 'blacklist') NULL AFTER tanggal_pending");
    }
};
