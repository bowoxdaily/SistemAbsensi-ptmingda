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
        Schema::create('attendance_monthly_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->integer('year');
            $table->integer('month');
            
            // Status counts
            $table->integer('hadir')->default(0);
            $table->integer('terlambat')->default(0);
            $table->integer('izin')->default(0);
            $table->integer('sakit')->default(0);
            $table->integer('cuti')->default(0);
            $table->integer('alpha')->default(0);
            $table->integer('libur')->default(0);
            $table->integer('cuti_bersama')->default(0);
            
            $table->timestamps();

            // Index dan Unique Constraint
            $table->unique(['employee_id', 'year', 'month'], 'emp_year_month_unique');
            $table->index(['year', 'month'], 'year_month_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_monthly_summaries');
    }
};
