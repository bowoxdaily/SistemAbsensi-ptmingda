<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('opls') && Schema::hasColumn('opls', 'content')) {
            Schema::table('opls', function (Blueprint $table) {
                $table->dropColumn('content');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('opls') && !Schema::hasColumn('opls', 'content')) {
            Schema::table('opls', function (Blueprint $table) {
                $table->text('content')->nullable();
            });
        }
    }
};
