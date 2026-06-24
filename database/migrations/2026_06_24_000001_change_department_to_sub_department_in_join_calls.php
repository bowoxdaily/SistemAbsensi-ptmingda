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
        Schema::table('join_calls', function (Blueprint $table) {
            // Drop the old foreign key constraint
            $table->dropConstrainedForeignId('department_id');
            
            // Add the new sub_department_id column
            $table->foreignId('sub_department_id')
                ->nullable()
                ->after('email')
                ->constrained('sub_departments')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('join_calls', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropConstrainedForeignId('sub_department_id');
            
            // Restore the old department_id column
            $table->foreignId('department_id')
                ->nullable()
                ->after('email')
                ->constrained('departments')
                ->nullOnDelete();
        });
    }
};
