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
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            // Format nomor surat SP
            // Format: {sp_type}/{dept}/{counter}/{year}
            // Contoh: SP1/HR/001/2026
            $table->string('sp_number_format', 100)->nullable()->after('payroll_template')
                ->comment('Format nomor surat SP. Variabel: {sp_type}, {dept}, {counter}, {year}, {month}');

            // Department code untuk nomor surat (default: HR)
            $table->string('sp_department_code', 10)->nullable()->after('sp_number_format')
                ->default('HR')->comment('Kode departemen untuk nomor surat SP');

            // Counter width (number of digits)
            $table->integer('sp_counter_width')->nullable()->after('sp_department_code')
                ->default(3)->comment('Jumlah digit counter (3 = 001, 4 = 0001)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn([
                'sp_number_format',
                'sp_department_code',
                'sp_counter_width',
            ]);
        });
    }
};
