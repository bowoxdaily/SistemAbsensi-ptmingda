<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('opl_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opl_id');
            $table->unsignedBigInteger('employee_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('opl_id')->references('id')->on('opls')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->index(['opl_id','employee_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('opl_reads');
    }
};
