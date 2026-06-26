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
        DB::statement("ALTER TABLE employees ADD COLUMN termination_recommendation ENUM('can_rehire', 'considered') NULL AFTER tanggal_pending");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE employees DROP COLUMN termination_recommendation");
    }
};
