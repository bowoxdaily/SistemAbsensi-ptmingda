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
        Schema::create('office_settings', function (Blueprint $table) {
            $table->id();
            $table->string('office_name')->default('Kantor Pusat');
            $table->decimal('latitude', 10, 8)->comment('Latitude lokasi kantor');
            $table->decimal('longitude', 11, 8)->comment('Longitude lokasi kantor');
            $table->integer('radius_meters')->default(100)->comment('Radius dalam meter untuk validasi lokasi');
            $table->boolean('enforce_location')->default(true)->comment('Paksa validasi lokasi saat absen');
            $table->text('address')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('office_settings')->insert([
            'office_name' => 'Kantor Pusat',
            'latitude' => -6.200000, // Default Jakarta
            'longitude' => 106.816666,
            'radius_meters' => 100,
            'enforce_location' => true,
            'address' => 'Jakarta, Indonesia',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_settings');
    }
};
