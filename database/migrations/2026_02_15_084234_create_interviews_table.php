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
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->string('candidate_name', 100)->comment('Nama kandidat');
            $table->string('phone', 20)->comment('Nomor HP/WhatsApp');
            $table->string('email', 100)->nullable()->comment('Email kandidat');
            $table->foreignId('position_id')->constrained('positions')->onDelete('cascade')->comment('Posisi yang dilamar');
            $table->date('interview_date')->comment('Tanggal interview');
            $table->time('interview_time')->comment('Waktu interview');
            $table->string('location', 255)->default('Kantor PT Mingda')->comment('Lokasi interview');
            $table->text('notes')->nullable()->comment('Catatan tambahan');
            $table->enum('status', ['scheduled', 'notified', 'confirmed', 'completed', 'cancelled'])->default('scheduled')->comment('Status interview');
            $table->timestamp('wa_sent_at')->nullable()->comment('Waktu notifikasi WA terkirim');
            $table->text('wa_message')->nullable()->comment('Isi pesan WA yang dikirim');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
