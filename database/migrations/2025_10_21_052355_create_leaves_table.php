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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade')->comment('ID karyawan');
            $table->enum('leave_type', ['cuti', 'izin', 'sakit'])->comment('Jenis cuti/izin');
            $table->date('start_date')->comment('Tanggal mulai');
            $table->date('end_date')->comment('Tanggal selesai');
            $table->integer('total_days')->comment('Total hari');
            $table->text('reason')->comment('Alasan cuti/izin');
            $table->string('attachment')->nullable()->comment('Lampiran dokumen (jika ada)');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->comment('Status persetujuan');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->comment('Disetujui oleh');
            $table->timestamp('approved_at')->nullable()->comment('Tanggal disetujui');
            $table->text('rejection_reason')->nullable()->comment('Alasan penolakan');
            $table->timestamps();

            // Index untuk performa query
            $table->index(['employee_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
