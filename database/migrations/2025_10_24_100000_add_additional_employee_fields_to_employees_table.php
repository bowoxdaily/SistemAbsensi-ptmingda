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
        Schema::table('employees', function (Blueprint $table) {
            // Data Pribadi Tambahan
            $table->string('agama', 50)->nullable()->after('marital_status')->comment('Agama karyawan');
            $table->string('bangsa', 50)->nullable()->after('agama')->comment('Kebangsaan');
            $table->string('status_kependudukan', 20)->nullable()->after('bangsa')->comment('Status kependudukan (WNI/WNA)');
            $table->integer('tanggungan_anak')->default(0)->after('status_kependudukan')->comment('Jumlah anak tanggungan');
            $table->string('nama_ibu_kandung', 100)->nullable()->after('tanggungan_anak')->comment('Nama ibu kandung');
            $table->string('ktp', 20)->nullable()->after('nama_ibu_kandung')->comment('Nomor KTP');
            $table->string('kartu_keluarga', 20)->nullable()->after('ktp')->comment('Nomor Kartu Keluarga');

            // Data Pekerjaan Tambahan
            $table->string('sub_department', 100)->nullable()->after('department_id')->comment('Sub departemen/bagian');
            $table->string('lulusan_sekolah', 100)->nullable()->after('employment_status')->comment('Pendidikan terakhir');
            $table->date('tanggal_resign')->nullable()->after('status')->comment('Tanggal resign (jika resign)');

            // Data Keuangan
            $table->string('bank', 50)->nullable()->after('salary_base')->comment('Nama bank');
            $table->string('nomor_rekening', 50)->nullable()->after('bank')->comment('Nomor rekening bank');

            // Data Administrasi
            $table->string('tax_npwp', 20)->nullable()->after('nomor_rekening')->comment('Nomor NPWP untuk pajak');
            $table->string('bpjs_kesehatan', 20)->nullable()->after('tax_npwp')->comment('Nomor BPJS Kesehatan');
            $table->string('bpjs_ketenagakerjaan', 20)->nullable()->after('bpjs_kesehatan')->comment('Nomor BPJS Ketenagakerjaan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'agama',
                'bangsa',
                'status_kependudukan',
                'tanggungan_anak',
                'nama_ibu_kandung',
                'ktp',
                'kartu_keluarga',
                'sub_department',
                'lulusan_sekolah',
                'tanggal_resign',
                'bank',
                'nomor_rekening',
                'tax_npwp',
                'bpjs_kesehatan',
                'bpjs_ketenagakerjaan',
            ]);
        });
    }
};
