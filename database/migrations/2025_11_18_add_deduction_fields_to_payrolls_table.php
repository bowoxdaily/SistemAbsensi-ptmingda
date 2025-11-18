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
        Schema::table('payrolls', function (Blueprint $table) {
            // Tambah dua kolom baru untuk potongan tunjangan sakit dan serikat
            $table->decimal('deduction_sick_leave', 15, 2)->default(0)->after('deduction_tax')->comment('Potongan Tunjangan Sakit');
            $table->decimal('deduction_union', 15, 2)->default(0)->after('deduction_sick_leave')->comment('Potongan Tunjangan Serikat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['deduction_sick_leave', 'deduction_union']);
        });
    }
};
