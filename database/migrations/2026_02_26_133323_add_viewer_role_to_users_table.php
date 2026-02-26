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
        // Add 'viewer' and 'guest' to role enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('karyawan', 'admin', 'manager', 'security', 'guest', 'viewer') DEFAULT 'karyawan' COMMENT 'Role user'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('karyawan', 'admin', 'manager', 'security') DEFAULT 'karyawan' COMMENT 'Role user'");
    }
};
