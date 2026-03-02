<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_edit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users');

            // Snapshot nilai lama (sebelum diedit)
            $table->date('old_attendance_date')->nullable();
            $table->time('old_check_in')->nullable();
            $table->time('old_check_out')->nullable();
            $table->string('old_status', 30)->nullable();

            // Nilai baru yang diinginkan
            $table->date('new_attendance_date');
            $table->time('new_check_in')->nullable();
            $table->time('new_check_out')->nullable();
            $table->string('new_status', 30);

            $table->text('reason'); // Alasan perubahan (wajib diisi)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Review oleh manager
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();

            $table->index(['attendance_id', 'status']);
            $table->index('requested_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_edit_requests');
    }
};
