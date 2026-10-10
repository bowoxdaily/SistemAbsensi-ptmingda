<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->integer('approved_overtime_minutes')->default(0)->after('overtime_category')
                ->comment('Menit lembur yang disetujui (dari form fisik), 0 = belum disetujui');
            $table->string('overtime_approval_status', 20)->default('pending')->after('approved_overtime_minutes')
                ->index()->comment('Status approval: pending, approved, rejected');
            $table->unsignedBigInteger('overtime_approved_by')->nullable()->after('overtime_approval_status')
                ->comment('User ID yang melakukan approval');
            $table->timestamp('overtime_approved_at')->nullable()->after('overtime_approved_by')
                ->comment('Waktu approval dilakukan');
            $table->string('overtime_rejection_reason')->nullable()->after('overtime_approved_at')
                ->comment('Alasan penolakan lembur');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'approved_overtime_minutes',
                'overtime_approval_status',
                'overtime_approved_by',
                'overtime_approved_at',
                'overtime_rejection_reason',
            ]);
        });
    }
};
