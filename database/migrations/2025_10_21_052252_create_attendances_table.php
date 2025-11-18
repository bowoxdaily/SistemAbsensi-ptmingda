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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade')->comment('ID karyawan');
            $table->date('attendance_date')->comment('Tanggal absensi');
            $table->time('check_in')->nullable()->comment('Jam masuk');
            $table->time('check_out')->nullable()->comment('Jam keluar');
            $table->enum('status', ['hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'cuti'])->default('hadir')->comment('Status kehadiran');
            $table->text('notes')->nullable()->comment('Catatan tambahan');
            $table->string('photo_in')->nullable()->comment('Foto saat check in');
            $table->string('photo_out')->nullable()->comment('Foto saat check out');
            $table->string('location_in')->nullable()->comment('Lokasi GPS saat check in');
            $table->string('location_out')->nullable()->comment('Lokasi GPS saat check out');
            $table->integer('late_minutes')->default(0)->comment('Menit keterlambatan');
            $table->timestamps();

            // Index untuk performa query
            $table->index(['employee_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
