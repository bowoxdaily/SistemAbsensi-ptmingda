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
        Schema::table('employees', function (Blueprint $table) {
            // Mapping shift lama ke work_schedule_id baru
            // Anggap sudah ada data di work_schedules:
            // ID 1 = Shift Pagi
            // ID 2 = Shift Siang
            // ID 3 = Shift Malam

            // Update data existing berdasarkan mapping
            DB::statement("
                UPDATE employees
                SET shift_type = CASE
                    WHEN shift_type = 'Pagi' THEN '1'
                    WHEN shift_type = 'Sore' OR shift_type = 'Siang' THEN '2'
                    WHEN shift_type = 'Malam' THEN '3'
                    WHEN shift_type = 'Rotasi' THEN '1'
                    ELSE '1'
                END
                WHERE shift_type IS NOT NULL
            ");

            // Rename kolom dari shift_type ke work_schedule_id
            $table->renameColumn('shift_type', 'work_schedule_id');
        });

        // Ubah tipe kolom menjadi unsignedBigInteger dan tambah foreign key
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('work_schedule_id')->nullable()->change();
            $table->foreign('work_schedule_id')->references('id')->on('work_schedules')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['work_schedule_id']);

            // Rename back
            $table->renameColumn('work_schedule_id', 'shift_type');
        });

        // Kembalikan data ke format string
        Schema::table('employees', function (Blueprint $table) {
            $table->string('shift_type', 20)->nullable()->change();
        });
    }
};
