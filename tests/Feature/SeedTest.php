<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('creates exactly the two seed users', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->orderBy('email')->pluck('name', 'email')->all())->toBe([
        'alice@example.test' => 'Alice',
        'bob@example.test' => 'Bob',
    ]);
});

it('is idempotent', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(2);
    $this->getJson('/healthz')->assertJson(['users' => 2]);
});

it('stores hashed passwords that let the seed users sign in', function (string $email, string $password) {
    $this->seed(DatabaseSeeder::class);

    $user = User::query()->where('email', $email)->firstOrFail();
    expect($user->password)->not->toBe($password)
        ->and(Hash::check($password, $user->password))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();

    $this->post(route('login.store'), ['email' => $email, 'password' => $password]);
    $this->assertAuthenticatedAs($user);
})->with([
    ['alice@example.test', 'Correct-Horse-1'],
    ['bob@example.test', 'Battery-Staple-2'],
]);
