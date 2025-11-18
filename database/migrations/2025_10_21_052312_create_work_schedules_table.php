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
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Nama jadwal (misal: Shift Pagi, Shift Sore)');
            $table->time('start_time')->comment('Jam mulai kerja');
            $table->time('end_time')->comment('Jam selesai kerja');
            $table->integer('late_tolerance')->default(0)->comment('Toleransi keterlambatan (menit)');
            $table->boolean('is_active')->default(true)->comment('Status aktif jadwal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
