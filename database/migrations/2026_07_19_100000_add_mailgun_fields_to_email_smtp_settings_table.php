<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_smtp_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('email_smtp_settings', 'mailgun_api_key')) {
                $table->text('mailgun_api_key')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('email_smtp_settings', 'mailgun_domain')) {
                $table->string('mailgun_domain', 191)->nullable()->after('mailgun_api_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('email_smtp_settings', function (Blueprint $table) {
            if (Schema::hasColumn('email_smtp_settings', 'mailgun_api_key')) {
                $table->dropColumn('mailgun_api_key');
            }
            if (Schema::hasColumn('email_smtp_settings', 'mailgun_domain')) {
                $table->dropColumn('mailgun_domain');
            }
        });
    }
};
