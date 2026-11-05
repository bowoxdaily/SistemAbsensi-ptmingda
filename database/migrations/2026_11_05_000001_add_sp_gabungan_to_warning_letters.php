<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE warning_letters MODIFY COLUMN sp_type ENUM('ST', 'SP1', 'SP2', 'SP3', 'SP_GABUNGAN') NOT NULL");
    }

    public function down(): void
    {
        DB::table('warning_letters')->where('sp_type', 'SP_GABUNGAN')->update(['sp_type' => 'SP1']);
        DB::statement("ALTER TABLE warning_letters MODIFY COLUMN sp_type ENUM('SP1', 'SP2', 'SP3') NOT NULL");
    }
};
