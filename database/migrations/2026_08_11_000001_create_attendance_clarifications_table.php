<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_clarifications', function (Blueprint $table) {
            $table->id();

            // Relasi ke absensi yang diklarifikasi
            $table->foreignId('attendance_id')
                ->constrained('attendances')
                ->cascadeOnDelete();

            // Karyawan yang mengajukan
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            // Data klarifikasi
            $table->string('reason');                          // Alasan / keterangan
            $table->string('new_status')->nullable();          // Status yang diminta
            $table->time('new_check_in')->nullable();          // Jam masuk yang seharusnya
            $table->time('new_check_out')->nullable();         // Jam keluar yang seharusnya

            // Lampiran scan formulir fisik (WAJIB)
            $table->string('attachment_path');                 // path di storage/app/public/
            $table->string('attachment_original_name');        // nama file asli dari user

            // Status review
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();

            // Index untuk query umum
            $table->index(['employee_id', 'status']);
            $table->index(['attendance_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_clarifications');
    }
};
