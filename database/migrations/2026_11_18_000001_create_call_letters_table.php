<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('letter_number', 100)->unique();
            $table->date('issue_date');
            $table->dateTime('meeting_at');
            $table->string('location', 255);
            $table->text('purpose');
            $table->text('description')->nullable();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index('meeting_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_letters');
    }
};

