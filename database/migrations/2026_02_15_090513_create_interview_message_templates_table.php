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
        Schema::create('interview_message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Nama template');
            $table->text('message_template')->comment('Isi template pesan (gunakan placeholder: {nama}, {posisi}, {tanggal}, {waktu}, {lokasi}, {catatan})');
            $table->boolean('is_default')->default(false)->comment('Template default sistem');
            $table->boolean('is_active')->default(true)->comment('Status aktif');
            $table->integer('usage_count')->default(0)->comment('Jumlah pemakaian');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_message_templates');
    }
};
