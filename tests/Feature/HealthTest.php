<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reports status, environment and user count as JSON', function () {
    User::factory()->count(3)->create();

    $this->getJson('/healthz')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertExactJson([
            'status' => 'ok',
            'env' => config('app.env'),
            'users' => 3,
        ]);
});

it('reports the configured APP_ENV', function () {
    config(['app.env' => 'preview']);

    $this->get('/healthz')->assertOk()->assertJson(['env' => 'preview', 'users' => 0]);
});

it('does not start a session or set cookies', function () {
    $response = $this->get('/healthz');

    expect($response->headers->getCookies())->toBeEmpty();
});
