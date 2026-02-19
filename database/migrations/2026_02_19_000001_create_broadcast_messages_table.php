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
        Schema::create('broadcast_messages', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Judul broadcast
            $table->text('message'); // Isi pesan
            $table->string('image')->nullable(); // Path gambar (opsional)
            $table->string('filter_type')->default('all'); // all, position, department
            $table->json('filter_values')->nullable(); // ID jabatan/department yang dipilih
            $table->integer('total_recipients')->default(0); // Total penerima
            $table->integer('sent_count')->default(0); // Jumlah terkirim
            $table->integer('failed_count')->default(0); // Jumlah gagal
            $table->enum('status', ['draft', 'sending', 'completed', 'failed'])->default('draft');
            $table->foreignId('sent_by')->constrained('users'); // Admin yang mengirim
            $table->timestamp('sent_at')->nullable(); // Waktu pengiriman
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcast_messages');
    }
};
