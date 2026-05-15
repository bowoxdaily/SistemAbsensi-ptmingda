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
        Schema::create('join_calls', function (Blueprint $table) {
            $table->id();
            $table->string('candidate_name', 100)->comment('Nama kandidat');
            $table->string('phone', 20)->comment('Nomor HP/WhatsApp');
            $table->string('email', 100)->nullable()->comment('Email kandidat');
            $table->foreignId('position_id')->constrained('positions')->onDelete('cascade')->comment('Posisi');
            $table->date('join_date')->comment('Tanggal bergabung');
            $table->time('join_time')->comment('Waktu bergabung');
            $table->string('location', 255)->default('Kantor PT Mingda')->comment('Lokasi bergabung');
            $table->text('notes')->nullable()->comment('Catatan tambahan');
            $table->enum('status', ['scheduled', 'notified', 'confirmed', 'completed', 'cancelled'])->default('scheduled')->comment('Status panggilan join');
            $table->timestamp('wa_sent_at')->nullable()->comment('Waktu notifikasi WA terkirim');
            $table->text('wa_message')->nullable()->comment('Isi pesan WA yang dikirim');
            $table->text('custom_message_template')->nullable()->comment('Custom template pesan WA');
            $table->string('qr_code_token', 64)->nullable()->unique()->comment('Token unik untuk QR Code');
            $table->text('qr_code_image')->nullable()->comment('Data URI untuk QR Code base64');
            $table->timestamp('checked_in_at')->nullable()->comment('Waktu check-in QR Code');
            $table->string('checked_in_by', 100)->nullable()->comment('Nama petugas security yang scan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('join_calls');
    }
};
