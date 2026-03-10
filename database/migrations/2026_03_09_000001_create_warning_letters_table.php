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
        Schema::create('warning_letters', function (Blueprint $table) {
            $table->id();

            // Employee & Type
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade')->comment('ID karyawan');
            $table->enum('sp_type', ['SP1', 'SP2', 'SP3'])->comment('Jenis SP (1, 2, atau 3)');

            // Document Details
            $table->string('sp_number', 50)->unique()->comment('Nomor surat SP');
            $table->date('issue_date')->comment('Tanggal terbit SP');
            $table->date('effective_date')->comment('Tanggal berlaku SP');
            $table->text('violation')->comment('Pelanggaran yang dilakukan');
            $table->text('description')->nullable()->comment('Deskripsi/keterangan tambahan');

            // File Upload
            $table->string('document_path')->comment('Path file dokumen SP (PDF/image)');

            // Status & Tracking
            $table->enum('status', ['aktif', 'selesai', 'dibatalkan'])->default('aktif')->comment('Status SP');
            $table->date('completion_date')->nullable()->comment('Tanggal selesai/expired');
            $table->text('cancellation_reason')->nullable()->comment('Alasan pembatalan');

            // Admin Actions
            $table->foreignId('issued_by')->constrained('users')->onDelete('restrict')->comment('Diterbitkan oleh (admin/manager)');
            $table->timestamp('issued_at')->comment('Waktu penerbitan');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null')->comment('Diupdate oleh');

            // WhatsApp Notification
            $table->timestamp('wa_sent_at')->nullable()->comment('Waktu notifikasi WA terkirim');
            $table->text('wa_message')->nullable()->comment('Isi pesan WA yang dikirim');

            $table->timestamps();
            $table->softDeletes(); // Soft delete for audit trail

            // Indexes for performance
            $table->index(['employee_id', 'status', 'sp_type']);
            $table->index(['issue_date', 'effective_date']);
            $table->index('sp_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warning_letters');
    }
};
