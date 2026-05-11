<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add 'ST' (Surat Teguran) to the sp_type enum column.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE warning_letters MODIFY COLUMN sp_type ENUM('ST', 'SP1', 'SP2', 'SP3') NOT NULL COMMENT 'Jenis SP (ST, 1, 2, atau 3)'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE warning_letters MODIFY COLUMN sp_type ENUM('SP1', 'SP2', 'SP3') NOT NULL COMMENT 'Jenis SP (1, 2, atau 3)'");
    }
};
