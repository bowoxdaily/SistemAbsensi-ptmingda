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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('Kode jabatan');
            $table->string('name', 100)->comment('Nama jabatan');
            $table->text('description')->nullable()->comment('Deskripsi jabatan');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('Status jabatan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
