<?php

use App\Console\Commands\FailStaleCallLetterEmails;
use App\Jobs\SendCallLetterEmail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

it('queues call-letter email jobs', function () {
    Queue::fake();

    SendCallLetterEmail::dispatch(42);

    Queue::assertPushed(SendCallLetterEmail::class, fn ($job) => $job->callLetterId === 42);
});

it('marks call-letter emails pending for over an hour as failed', function () {
    Schema::create('call_letters', function (Blueprint $table) {
        $table->id();
        $table->string('email_status')->default('pending');
        $table->text('email_error')->nullable();
        $table->timestamps();
    });

    $staleId = DB::table('call_letters')->insertGetId([
        'email_status' => 'pending',
        'created_at' => now()->subHours(2),
        'updated_at' => now()->subHours(2),
    ]);
    $recentId = DB::table('call_letters')->insertGetId([
        'email_status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('call-letters:fail-stale-emails')->assertSuccessful();

    expect(DB::table('call_letters')->where('id', $staleId)->value('email_status'))->toBe('failed')
        ->and(DB::table('call_letters')->where('id', $recentId)->value('email_status'))->toBe('pending');

    Schema::drop('call_letters');
});
