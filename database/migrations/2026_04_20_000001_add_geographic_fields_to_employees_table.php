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
            // Add geographic fields after province
            $table->string('desa', 100)->nullable()->after('province')->comment('Desa/Kelurahan');
            $table->string('kecamatan', 100)->nullable()->after('desa')->comment('Kecamatan');
            $table->string('kabupaten', 100)->nullable()->after('kecamatan')->comment('Kabupaten/Kota');

            // Add indexes for better query performance on grouping
            $table->index('kabupaten');
            $table->index('kecamatan');
            $table->index('desa');
            $table->index('province');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['kabupaten']);
            $table->dropIndex(['kecamatan']);
            $table->dropIndex(['desa']);
            $table->dropIndex(['province']);
            $table->dropColumn(['desa', 'kecamatan', 'kabupaten']);
        });
    }
};
