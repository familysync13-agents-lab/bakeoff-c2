<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('serves the landing page to signed-out visitors', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->where('auth.user', null)
            ->where('name', 'Shared Reading Lists'));
});

it('serves the landing page to signed-in users', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('welcome'));
});

it('renders a responsive document shell with the product name', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1">', false)
        ->assertSee('<html lang="en"', false)
        ->assertSee('Shared Reading Lists');
});
