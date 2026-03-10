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
        // Add 'draft' to status enum
        DB::statement("ALTER TABLE warning_letters MODIFY COLUMN status ENUM('draft', 'aktif', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'draft' COMMENT 'Status SP'");

        // Make document_path nullable (for draft status)
        Schema::table('warning_letters', function (Blueprint $table) {
            $table->string('document_path', 500)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum (remove 'draft')
        DB::statement("ALTER TABLE warning_letters MODIFY COLUMN status ENUM('aktif', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'aktif' COMMENT 'Status SP'");

        // Note: We don't change document_path back as it might have null values now
    }
};
