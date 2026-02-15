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
        // Modify the role enum to add 'security'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('karyawan', 'admin', 'manager', 'security') DEFAULT 'karyawan' COMMENT 'Role user'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'security' from role enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('karyawan', 'admin', 'manager') DEFAULT 'karyawan' COMMENT 'Role user'");
    }
};
