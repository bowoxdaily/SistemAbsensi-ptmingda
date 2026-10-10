<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add payroll component fields to employees table
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('allowance_position', 12, 2)->default(0)
                ->after('salary_base')
                ->comment('Tunjangan jabatan tetap bulanan');
            $table->decimal('bonus', 12, 2)->default(0)
                ->after('allowance_position')
                ->comment('Tunjangan bonus tetap bulanan');
            $table->decimal('union_deduction', 12, 2)->default(0)
                ->after('bonus')
                ->comment('Potongan iuran serikat bulanan');
        });

        // 2. Add detailed overtime columns to payrolls table
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('hourly_rate', 15, 2)->nullable()->after('net_salary')
                ->comment('Gaji per jam = (basic_salary + allowance_position) / 173');
            $table->decimal('daily_rate', 15, 2)->nullable()->after('hourly_rate')
                ->comment('Gaji per hari = total_gaji / 21');
            $table->integer('overtime_150_minutes')->default(0)->after('daily_rate')
                ->comment('Menit lembur jam pertama (150%)');
            $table->decimal('overtime_150_pay', 15, 2)->default(0)->after('overtime_150_minutes')
                ->comment('Pembayaran lembur 150%');
            $table->integer('overtime_200_minutes')->default(0)->after('overtime_150_pay')
                ->comment('Menit lembur jam ke-2 s/d ke-8 / hari libur (200%)');
            $table->decimal('overtime_200_pay', 15, 2)->default(0)->after('overtime_200_minutes')
                ->comment('Pembayaran lembur 200%');
            $table->decimal('company_bpjs_total', 15, 2)->default(0)->after('overtime_200_pay')
                ->comment('Total BPJS perusahaan (Kesehatan+JKK+JKM+JHT+JP)');
            $table->integer('days_leave')->default(0)->after('total_days_leave')
                ->comment('Jumlah hari cuti');
            $table->integer('days_sick')->default(0)->after('days_leave')
                ->comment('Jumlah hari sakit');
            $table->integer('days_permission')->default(0)->after('days_sick')
                ->comment('Jumlah hari izin');
            $table->integer('permission_hours')->default(0)->after('days_permission')
                ->comment('Total jam izin');
            $table->integer('late_minutes_total')->default(0)->after('permission_hours')
                ->comment('Total menit terlambat');
            $table->decimal('deduction_permission', 15, 2)->default(0)->after('late_minutes_total')
                ->comment('Potongan izin');
            $table->decimal('meal_allowance', 15, 2)->default(0)->after('deduction_permission')
                ->comment('Tunjangan makan (hadir_normal x Rp10.000)');
            $table->string('generated_by')->nullable()->after('notes')
                ->comment('auto/manual - cara pembuatan payroll');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['allowance_position', 'bonus', 'union_deduction']);
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'hourly_rate',
                'daily_rate',
                'overtime_150_minutes',
                'overtime_150_pay',
                'overtime_200_minutes',
                'overtime_200_pay',
                'company_bpjs_total',
                'days_leave',
                'days_sick',
                'days_permission',
                'permission_hours',
                'late_minutes_total',
                'deduction_permission',
                'meal_allowance',
                'generated_by',
            ]);
        });
    }
};
