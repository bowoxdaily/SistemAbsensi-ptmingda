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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('payroll_code')->unique(); // Kode slip gaji (contoh: PAY-2025-10-001)
            $table->string('period_month'); // Format: 2025-10 (YYYY-MM)
            $table->date('payment_date'); // Tanggal pembayaran

            // Komponen Gaji
            $table->decimal('basic_salary', 15, 2); // Gaji pokok
            $table->decimal('allowance_transport', 15, 2)->default(0); // Tunjangan transport
            $table->decimal('allowance_meal', 15, 2)->default(0); // Tunjangan makan
            $table->decimal('allowance_position', 15, 2)->default(0); // Tunjangan jabatan
            $table->decimal('allowance_others', 15, 2)->default(0); // Tunjangan lainnya
            $table->decimal('overtime_pay', 15, 2)->default(0); // Lembur
            $table->decimal('bonus', 15, 2)->default(0); // Bonus

            // Potongan
            $table->decimal('deduction_late', 15, 2)->default(0); // Potongan terlambat
            $table->decimal('deduction_absent', 15, 2)->default(0); // Potongan alpha
            $table->decimal('deduction_loan', 15, 2)->default(0); // Potongan pinjaman
            $table->decimal('deduction_bpjs', 15, 2)->default(0); // Potongan BPJS
            $table->decimal('deduction_tax', 15, 2)->default(0); // Potongan pajak (PPh21)
            $table->decimal('deduction_others', 15, 2)->default(0); // Potongan lainnya

            // Total
            $table->decimal('total_earnings', 15, 2); // Total pendapatan (gaji + tunjangan + bonus)
            $table->decimal('total_deductions', 15, 2); // Total potongan
            $table->decimal('net_salary', 15, 2); // Gaji bersih (take home pay)

            // Absensi Summary (opsional, untuk reference)
            $table->integer('total_days_present')->default(0);
            $table->integer('total_days_late')->default(0);
            $table->integer('total_days_absent')->default(0);
            $table->integer('total_days_leave')->default(0);

            // Status & Notes
            $table->enum('status', ['draft', 'sent', 'paid'])->default('draft'); // draft, sent (sudah kirim WA), paid
            $table->text('notes')->nullable(); // Catatan tambahan
            $table->timestamp('sent_at')->nullable(); // Waktu kirim notifikasi WA
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Index untuk query performance
            $table->index(['employee_id', 'period_month']);
            $table->index('payment_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
