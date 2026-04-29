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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');                              // Judul pengumuman
            $table->text('content');                             // Isi pengumuman
            $table->enum('type', ['info', 'warning', 'success', 'danger'])->default('info'); // Tipe notifikasi
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal'); // Prioritas
            $table->string('filter_type')->default('all');       // all, position, department, employee
            $table->json('filter_values')->nullable();           // ID jabatan/department/karyawan
            $table->boolean('is_active')->default(true);         // Status aktif
            $table->boolean('show_popup')->default(false);       // Tampil sebagai popup saat login
            $table->timestamp('start_date')->nullable();         // Mulai tampil
            $table->timestamp('end_date')->nullable();           // Berakhir tampil
            $table->foreignId('created_by')->constrained('users'); // Admin pembuat
            $table->timestamps();
        });

        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['announcement_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('announcements');
    }
};
