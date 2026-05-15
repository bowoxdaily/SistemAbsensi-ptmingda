<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('join_calls', function (Blueprint $table) {
            $table->renameColumn('join_date', 'join_call_date');
            $table->renameColumn('join_time', 'join_call_time');
        });
    }

    public function down(): void
    {
        Schema::table('join_calls', function (Blueprint $table) {
            $table->renameColumn('join_call_date', 'join_date');
            $table->renameColumn('join_call_time', 'join_time');
        });
    }
};
