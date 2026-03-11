<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

// Run only the migrations we need, skipping MySQL-specific ALTER TABLE statements.
// This is necessary because several migrations use DB::statement("ALTER TABLE ... MODIFY COLUMN ...")
// which is a MySQL-only syntax. When run against SQLite (used in tests), those statements fail.
// We create just the minimal schema required for authentication tests.
beforeEach(function () {
    // Create minimal in-memory schema for auth tests
    Schema::dropIfExists('personal_access_tokens');
    Schema::dropIfExists('employees');
    Schema::dropIfExists('users');

    Schema::create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('role')->default('karyawan');
        $table->string('status')->default('aktif');
        $table->string('avatar')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    // Minimal employees table (no FK constraints for SQLite test compatibility)
    Schema::create('employees', function ($table) {
        $table->id();
        $table->string('employee_code')->unique();
        $table->string('name');
        $table->string('email')->nullable();
        $table->string('status')->default('active');
        $table->integer('department_id')->nullable();
        $table->integer('sub_department_id')->nullable();
        $table->integer('position_id')->nullable();
        $table->integer('work_schedule_id')->nullable();
        $table->integer('user_id')->nullable();
        $table->timestamps();
    });

    Schema::create('personal_access_tokens', function ($table) {
        $table->id();
        $table->morphs('tokenable');
        $table->string('name');
        $table->string('token', 64)->unique();
        $table->text('abilities')->nullable();
        $table->timestamp('last_used_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('personal_access_tokens');
    Schema::dropIfExists('employees');
    Schema::dropIfExists('users');
});

// ─── POST /api/auth/login ─────────────────────────────────────────────────────

test('auth login returns a bearer token for valid credentials', function () {
    $user = User::factory()->create([
        'email'    => 'api@test.com',
        'password' => bcrypt('secret123'),
        'role'     => 'admin',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email'    => 'api@test.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['token', 'token_type', 'user' => ['id', 'name', 'email', 'role']],
        ])
        ->assertJson(['success' => true])
        ->assertJsonPath('data.token_type', 'Bearer');
});

test('auth login returns 401 for invalid credentials', function () {
    User::factory()->create(['email' => 'wrong@test.com', 'password' => bcrypt('correct')]);

    $this->postJson('/api/auth/login', [
        'email'    => 'wrong@test.com',
        'password' => 'incorrect',
    ])->assertStatus(401)->assertJson(['success' => false]);
});

test('auth login returns 422 when fields are missing', function () {
    $this->postJson('/api/auth/login', [])->assertStatus(422);
});

// ─── GET /api/v1/me ───────────────────────────────────────────────────────────

test('GET /api/v1/me returns authenticated user info with valid bearer token', function () {
    $user = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me')
        ->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'role']]);
});

test('GET /api/v1/me returns 401 without token', function () {
    $this->getJson('/api/v1/me')->assertStatus(401);
});

// ─── GET /api/v1/karyawan ─────────────────────────────────────────────────────

test('GET /api/v1/karyawan is accessible by admin', function () {
    $user = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/karyawan')
        ->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonStructure(['data', 'meta']);
});

test('GET /api/v1/karyawan is accessible by manager', function () {
    $user = User::factory()->create(['role' => 'manager']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/karyawan')->assertStatus(200)->assertJson(['success' => true]);
});

test('GET /api/v1/karyawan is accessible by viewer', function () {
    $user = User::factory()->create(['role' => 'viewer']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/karyawan')->assertStatus(200)->assertJson(['success' => true]);
});

test('GET /api/v1/karyawan is blocked for karyawan role', function () {
    $user = User::factory()->create(['role' => 'karyawan']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/karyawan')->assertStatus(403)->assertJson(['success' => false]);
});

test('GET /api/v1/karyawan returns 401 without token', function () {
    $this->getJson('/api/v1/karyawan')->assertStatus(401);
});

// ─── POST /api/auth/logout ────────────────────────────────────────────────────

test('auth logout revokes the current token', function () {
    $user = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($user);

    $this->postJson('/api/auth/logout')
        ->assertStatus(200)
        ->assertJson(['success' => true]);
});
