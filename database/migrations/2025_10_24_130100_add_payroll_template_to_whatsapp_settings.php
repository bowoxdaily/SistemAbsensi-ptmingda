<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->text('payroll_template')->nullable()->after('leave_rejected_template');
            $table->boolean('notify_payroll')->default(true)->after('notify_leave_rejected');
        });

        // Insert default payroll template
        DB::table('whatsapp_settings')->update([
            'payroll_template' => "🧾 *SLIP GAJI - {period}*\n\n" .
                "Kepada Yth.\n" .
                "*{employee_name}*\n\n" .
                "Berikut rincian gaji untuk periode *{formatted_period}*:\n\n" .
                "💰 *PENDAPATAN*\n" .
                "• Gaji Pokok: {basic_salary}\n" .
                "• Tunjangan: {total_allowances}\n" .
                "• Lembur: {overtime}\n" .
                "• Bonus: {bonus}\n" .
                "─────────────\n" .
                "Total: {total_earnings}\n\n" .
                "➖ *POTONGAN*\n" .
                "• Keterlambatan: {deduction_late}\n" .
                "• Ketidakhadiran: {deduction_absent}\n" .
                "• BPJS: {deduction_bpjs}\n" .
                "• Pajak: {deduction_tax}\n" .
                "• Lainnya: {other_deductions}\n" .
                "─────────────\n" .
                "Total: {total_deductions}\n\n" .
                "✅ *GAJI BERSIH*\n" .
                "*{net_salary}*\n\n" .
                "📅 Tanggal Pembayaran: {payment_date}\n\n" .
                "Terima kasih atas dedikasi Anda! 🙏",
            'notify_payroll' => true
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn(['payroll_template', 'notify_payroll']);
        });
    }
};
