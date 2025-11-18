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
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('gps_accuracy_in', 10, 2)->nullable()->after('location_in')->comment('GPS accuracy saat check in (meter)');
            $table->decimal('gps_accuracy_out', 10, 2)->nullable()->after('location_out')->comment('GPS accuracy saat check out (meter)');
            $table->boolean('is_mocked_in')->default(false)->after('gps_accuracy_in')->comment('Terdeteksi mock location saat check in');
            $table->boolean('is_mocked_out')->default(false)->after('gps_accuracy_out')->comment('Terdeteksi mock location saat check out');
            $table->text('gps_warnings_in')->nullable()->after('is_mocked_in')->comment('Peringatan fake GPS saat check in (JSON)');
            $table->text('gps_warnings_out')->nullable()->after('is_mocked_out')->comment('Peringatan fake GPS saat check out (JSON)');
            $table->boolean('is_suspicious_in')->default(false)->after('gps_warnings_in')->comment('Flag GPS mencurigakan saat check in');
            $table->boolean('is_suspicious_out')->default(false)->after('gps_warnings_out')->comment('Flag GPS mencurigakan saat check out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'gps_accuracy_in',
                'gps_accuracy_out',
                'is_mocked_in',
                'is_mocked_out',
                'gps_warnings_in',
                'gps_warnings_out',
                'is_suspicious_in',
                'is_suspicious_out'
            ]);
        });
    }
};
