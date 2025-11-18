<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Rename kolom lama untuk backup
            $table->renameColumn('sub_department', 'sub_department_old');
        });

        Schema::table('employees', function (Blueprint $table) {
            // Tambah kolom baru dengan foreign key
            $table->foreignId('sub_department_id')->nullable()->after('department_id')
                ->constrained('sub_departments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['sub_department_id']);
            $table->dropColumn('sub_department_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->renameColumn('sub_department_old', 'sub_department');
        });
    }
};
