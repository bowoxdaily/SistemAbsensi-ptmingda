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
        Schema::create('employees', function (Blueprint $table) {
            // 1. Identitas Pribadi
            $table->id();
            $table->string('employee_code', 20)->unique()->comment('Kode unik (misal: EMP001)');
            $table->string('nik', 20)->nullable()->comment('Nomor Induk Kependudukan (opsional)');
            $table->string('name', 100)->comment('Nama lengkap karyawan');
            $table->enum('gender', ['L', 'P'])->comment('Jenis kelamin');
            $table->string('birth_place', 50)->comment('Tempat lahir');
            $table->date('birth_date')->comment('Tanggal lahir');
            $table->enum('marital_status', ['Belum Menikah', 'Menikah', 'Duda', 'Janda'])->comment('Status perkawinan');

            // 2. Data Pekerjaan
            $table->foreignId('department_id')->constrained('departments')->onDelete('restrict')->comment('Departemen tempat karyawan bekerja');
            $table->foreignId('position_id')->constrained('positions')->onDelete('restrict')->comment('Jabatan/posisi karyawan');
            $table->date('join_date')->comment('Tanggal bergabung');
            $table->enum('employment_status', ['Tetap', 'Kontrak', 'Magang', 'Outsource'])->comment('Status kerja');
            $table->enum('shift_type', ['Pagi', 'Sore', 'Malam', 'Rotasi'])->comment('Jenis shift kerja');
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->onDelete('set null')->comment('ID atasan langsung (jika ada)');
            $table->decimal('salary_base', 12, 2)->nullable()->comment('Gaji pokok (opsional, kalau nanti mau hitung payroll)');

            // 3. Data Kontak & Alamat
            $table->text('address')->comment('Alamat lengkap');
            $table->string('city', 50)->comment('Kota domisili');
            $table->string('province', 50)->comment('Provinsi');
            $table->string('postal_code', 10)->comment('Kode pos');
            $table->string('phone', 20)->comment('Nomor HP');
            $table->string('email', 100)->comment('Email pribadi (bisa sama dengan akun login)');
            $table->string('emergency_contact_name', 100)->comment('Nama kontak darurat');
            $table->string('emergency_contact_phone', 20)->comment('Nomor kontak darurat');

            // 4. Data Akun & Sistem
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('Relasi ke tabel users (akun login)');
            $table->enum('status', ['active', 'inactive', 'resign'])->default('active')->comment('Status aktif karyawan');
            $table->string('profile_photo', 255)->nullable()->comment('Path foto profil');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
