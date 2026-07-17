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
        Schema::table('email_smtp_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('email_smtp_settings', 'reply_to_address')) {
                $table->string('reply_to_address', 191)->nullable()->after('from_name');
            }

            if (!Schema::hasColumn('email_smtp_settings', 'reply_to_name')) {
                $table->string('reply_to_name', 191)->nullable()->after('reply_to_address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_smtp_settings', function (Blueprint $table) {
            if (Schema::hasColumn('email_smtp_settings', 'reply_to_name')) {
                $table->dropColumn('reply_to_name');
            }

            if (Schema::hasColumn('email_smtp_settings', 'reply_to_address')) {
                $table->dropColumn('reply_to_address');
            }
        });
    }
};
