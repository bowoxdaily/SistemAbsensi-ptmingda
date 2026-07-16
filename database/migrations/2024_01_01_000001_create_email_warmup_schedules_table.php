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
        Schema::create('email_warmup_schedules', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['inactive', 'active', 'paused', 'completed'])->default('inactive');
            $table->integer('current_day')->default(1); // Current warmup day (1-30)
            $table->integer('total_days')->default(30); // Total warmup duration
            $table->integer('emails_per_day')->default(10); // Emails to send per day
            $table->integer('max_emails_per_day')->default(500); // Max emails per day (final target)
            $table->float('increase_percentage')->default(15); // % increase each day
            $table->integer('emails_sent_today')->default(0); // Counter for today
            $table->timestamp('last_send_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('email_warmup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_email');
            $table->string('subject');
            $table->enum('status', ['sent', 'bounced', 'spam', 'delivered'])->default('sent');
            $table->string('message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('warmup_day');
            $table->timestamp('sent_at');
            $table->timestamps();
        });

        Schema::create('email_warmup_stats', function (Blueprint $table) {
            $table->id();
            $table->integer('total_sent')->default(0);
            $table->integer('total_delivered')->default(0);
            $table->integer('total_bounced')->default(0);
            $table->integer('total_spam')->default(0);
            $table->float('delivery_rate')->default(0); // %
            $table->float('bounce_rate')->default(0); // %
            $table->float('spam_rate')->default(0); // %
            $table->float('sender_reputation')->default(100); // 0-100 score
            $table->string('reputation_status')->default('excellent'); // excellent, good, fair, poor
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_warmup_schedules');
        Schema::dropIfExists('email_warmup_logs');
        Schema::dropIfExists('email_warmup_stats');
    }
};
