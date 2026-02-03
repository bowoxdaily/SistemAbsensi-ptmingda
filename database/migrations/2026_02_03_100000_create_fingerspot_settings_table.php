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
        Schema::create('fingerspot_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Fingerspot Device');
            $table->string('sn')->nullable()->comment('Serial Number of the device');
            $table->string('webhook_token', 64)->unique()->comment('Token for webhook authentication');
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_checkout')->default(false)->comment('Auto checkout after certain hours');
            $table->integer('auto_checkout_hours')->default(8)->comment('Hours to auto checkout');
            $table->enum('scan_mode', ['first_last', 'all'])->default('first_last')
                ->comment('first_last: First scan=checkin, last=checkout. all: Process all scans');
            $table->text('notes')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });

        // Create log table for webhook requests
        Schema::create('fingerspot_logs', function (Blueprint $table) {
            $table->id();
            $table->string('pin')->comment('Employee PIN from device');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->datetime('scan_time')->comment('Scan time from device');
            $table->string('status_scan')->nullable()->comment('Scan status code from device');
            $table->string('verify_mode')->nullable()->comment('Verification mode (fingerprint, card, etc)');
            $table->string('sn')->nullable()->comment('Device serial number');
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->enum('process_status', ['pending', 'success', 'failed', 'skipped'])->default('pending');
            $table->text('process_message')->nullable();
            $table->json('raw_data')->nullable()->comment('Original raw data from webhook');
            $table->timestamps();

            $table->index(['pin', 'scan_time']);
            $table->index('process_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fingerspot_logs');
        Schema::dropIfExists('fingerspot_settings');
    }
};
