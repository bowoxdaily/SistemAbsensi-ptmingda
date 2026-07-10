<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan composite index untuk optimasi query overtime recalculation
     * Query target: whereNotNull('check_out')->whereIn('status', ['hadir', 'terlambat'])->whereDate('attendance_date', '>=', $date)
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Composite index untuk overtime query (status + date + check_out)
            // Urutan: status (high cardinality), attendance_date (range query), check_out (not null filter)
            $table->index(['status', 'attendance_date', 'check_out'], 'att_overtime_query_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('att_overtime_query_idx');
        });
    }
};
