<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('join_calls', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
        });

        Schema::table('join_calls', function (Blueprint $table) {
            $table->unsignedBigInteger('position_id')->nullable()->change();
        });

        Schema::table('join_calls', function (Blueprint $table) {
            $table->foreign('position_id')
                ->references('id')
                ->on('positions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $fallbackPositionId = DB::table('positions')->min('id');

        if ($fallbackPositionId === null) {
            throw new RuntimeException('Tidak dapat rollback: tabel positions kosong dan join_calls.position_id tidak bisa dibuat wajib.');
        }

        DB::table('join_calls')
            ->whereNull('position_id')
            ->update(['position_id' => $fallbackPositionId]);

        Schema::table('join_calls', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
        });

        Schema::table('join_calls', function (Blueprint $table) {
            $table->unsignedBigInteger('position_id')->nullable(false)->change();
        });

        Schema::table('join_calls', function (Blueprint $table) {
            $table->foreign('position_id')
                ->references('id')
                ->on('positions')
                ->cascadeOnDelete();
        });
    }
};
