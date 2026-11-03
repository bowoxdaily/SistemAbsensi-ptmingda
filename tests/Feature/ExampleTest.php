<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (!Schema::hasTable('landing_galleries')) {
        Schema::create('landing_galleries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->default('facility');
            $table->string('image_path');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
});

test('the landing page is accessible to guests', function () {
    $response = $this->get('/');

    $response->assertOk();
});
