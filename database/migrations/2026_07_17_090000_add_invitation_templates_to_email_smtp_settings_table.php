<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('email_smtp_settings', function (Blueprint $table) {
            $table->string('interview_subject_template', 191)->nullable()->after('from_name');
            $table->text('interview_body_template')->nullable()->after('interview_subject_template');
            $table->string('join_call_subject_template', 191)->nullable()->after('interview_body_template');
            $table->text('join_call_body_template')->nullable()->after('join_call_subject_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_smtp_settings', function (Blueprint $table) {
            $table->dropColumn([
                'interview_subject_template',
                'interview_body_template',
                'join_call_subject_template',
                'join_call_body_template',
            ]);
        });
    }
};