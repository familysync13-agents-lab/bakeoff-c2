<?php

use App\Models\ReadingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->alice = User::factory()->create(['name' => 'Alice']);
    $this->bob = User::factory()->create(['name' => 'Bob']);
});

describe('signed-in owner', function () {
    it('lists only their own lists, newest first', function () {
        $older = ReadingList::factory()->for($this->alice)->create(['name' => 'Older', 'updated_at' => now()->subDay()]);
        $newer = ReadingList::factory()->for($this->alice)->create(['name' => 'Newer']);
        ReadingList::factory()->for($this->bob)->create(['name' => 'Bob private']);

        $this->actingAs($this->alice)->get('/lists')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('lists/index')
                ->has('lists', 2)
                ->where('lists.0.id', $newer->id)
                ->where('lists.0.name', 'Newer')
                ->where('lists.1.id', $older->id))
            ->assertDontSee('Bob private');
    });

    it('shows the new-list form', function () {
        $this->actingAs($this->alice)->get('/lists/new')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('lists/create'));
    });

    it('creates a list and redirects to it', function () {
        $response = $this->actingAs($this->alice)->post('/lists', ['name' => 'Summer reading']);

        $list = ReadingList::query()->sole();
        expect($list->user_id)->toBe($this->alice->id)
            ->and($list->name)->toBe('Summer reading');
        $response->assertRedirect("/lists/{$list->id}");

        $this->get("/lists/{$list->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('lists/show')
                ->where('list', ['id' => $list->id, 'name' => 'Summer reading']));
    });

    it('accepts names of 1 and exactly 100 characters', function (string $name) {
        $this->actingAs($this->alice)->post('/lists', ['name' => $name])->assertSessionHasNoErrors();

        expect(ReadingList::query()->sole()->name)->toBe($name);
    })->with(['one character' => 'X', '100 characters' => str_repeat('é', 100)]);

    it('rejects empty, blank and over-long names on create', function (?string $name) {
        $this->actingAs($this->alice)
            ->from('/lists/new')
            ->post('/lists', ['name' => $name])
            ->assertRedirect('/lists/new')
            ->assertSessionHasErrors('name');

        expect(ReadingList::query()->count())->toBe(0);
    })->with(['empty' => '', 'blank' => '   ', 'missing' => null, '101 characters' => str_repeat('x', 101)]);

    it('shows the edit form pre-filled', function () {
        $list = ReadingList::factory()->for($this->alice)->create(['name' => 'Classics']);

        $this->actingAs($this->alice)->get("/lists/{$list->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('lists/edit')
                ->where('list.name', 'Classics'));
    });

    it('renames a list', function () {
        $list = ReadingList::factory()->for($this->alice)->create(['name' => 'Classics']);

        $this->actingAs($this->alice)
            ->patch("/lists/{$list->id}", ['name' => 'Modern classics'])
            ->assertRedirect("/lists/{$list->id}");

        expect($list->fresh()?->name)->toBe('Modern classics');
    });

    it('rejects invalid names on rename and keeps the old name', function (string $name) {
        $list = ReadingList::factory()->for($this->alice)->create(['name' => 'Classics']);

        $this->actingAs($this->alice)
            ->from("/lists/{$list->id}/edit")
            ->patch("/lists/{$list->id}", ['name' => $name])
            ->assertRedirect("/lists/{$list->id}/edit")
            ->assertSessionHasErrors('name');

        expect($list->fresh()?->name)->toBe('Classics');
    })->with(['empty' => '', '101 characters' => str_repeat('x', 101)]);

    it('deletes a list permanently', function () {
        $list = ReadingList::factory()->for($this->alice)->create();

        $this->actingAs($this->alice)->delete("/lists/{$list->id}")->assertRedirect('/lists');

        expect(ReadingList::query()->whereKey($list->id)->exists())->toBeFalse();
        $this->get("/lists/{$list->id}")->assertNotFound();
        $this->get("/lists/{$list->id}/edit")->assertNotFound();
        $this->actingAs($this->bob)->get("/lists/{$list->id}")->assertNotFound();

        // ... and for guests (tasks/T2/contract.json AC7), not a redirect to /login.
        auth()->logout();
        $this->assertGuest();
        $this->get("/lists/{$list->id}")->assertNotFound();
        $this->get("/lists/{$list->id}/edit")->assertNotFound();
    });

    it('renders not-found pages inside the app shell', function () {
        $this->actingAs($this->alice)->get('/lists/999999')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('error')
                ->where('status', 404)
                ->where('auth.user.id', $this->alice->id));
    });
});

describe('another signed-in user', function () {
    it('cannot see, edit, rename or delete the list', function () {
        $list = ReadingList::factory()->for($this->alice)->create(['name' => 'Alice secret']);
        $this->actingAs($this->bob);

        $this->get('/lists')->assertOk()->assertDontSee('Alice secret');
        $this->get("/lists/{$list->id}")->assertNotFound()->assertDontSee('Alice secret');
        $this->get("/lists/{$list->id}/edit")->assertNotFound()->assertDontSee('Alice secret');
        $this->patch("/lists/{$list->id}", ['name' => 'Hijacked'])->assertNotFound();
        $this->post("/lists/{$list->id}", ['_method' => 'PATCH', 'name' => 'Hijacked'])->assertNotFound();
        $this->delete("/lists/{$list->id}")->assertNotFound();
        $this->post("/lists/{$list->id}", ['_method' => 'DELETE'])->assertNotFound();

        expect($list->fresh()?->name)->toBe('Alice secret');
    });

    it('does not learn about the list from validation errors', function () {
        $list = ReadingList::factory()->for($this->alice)->create(['name' => 'Alice secret']);

        $this->actingAs($this->bob)->patch("/lists/{$list->id}", ['name' => ''])->assertNotFound();

        expect($list->fresh()?->name)->toBe('Alice secret');
    });
});

describe('anonymous visitor', function () {
    it('is sent to /login for the index and the new-list form', function (string $path) {
        $this->get($path)->assertRedirect('/login');
    })->with(['/lists', '/lists/new']);

    it('gets 404 for existing, deleted and unknown lists alike', function (string $path) {
        $list = ReadingList::factory()->for($this->alice)->create(['name' => 'Alice secret']);
        $gone = ReadingList::factory()->for($this->alice)->create();
        $gone->delete();

        foreach ([$list->id, $gone->id, 999999] as $id) {
            $this->get(str_replace('{id}', (string) $id, $path))
                ->assertNotFound()
                ->assertDontSee('Alice secret');
        }
    })->with(['/lists/{id}', '/lists/{id}/edit']);

    it('cannot create, rename or delete lists', function () {
        $list = ReadingList::factory()->for($this->alice)->create(['name' => 'Alice secret']);

        $this->post('/lists', ['name' => 'Anonymous'])->assertRedirect('/login');
        $this->patch("/lists/{$list->id}", ['name' => 'Hijacked'])->assertNotFound();
        $this->patch("/lists/{$list->id}", ['name' => ''])->assertNotFound();
        $this->delete("/lists/{$list->id}")->assertNotFound();

        expect(ReadingList::query()->count())->toBe(1)
            ->and($list->fresh()?->name)->toBe('Alice secret');
    });
});

it('rejects state-changing list requests without a valid CSRF token', function () {
    $list = ReadingList::factory()->for($this->alice)->create(['name' => 'Alice secret']);

    // Exercise the real CSRF middleware (disabled by default in the test environment).
    $this->app->instance('env', 'preview');
    $this->actingAs($this->alice)
        ->withSession(['_token' => 'session-token'])
        ->withHeader('X-CSRF-TOKEN', 'forged-token')
        ->delete("/lists/{$list->id}")
        ->assertStatus(419);

    expect($list->fresh())->not->toBeNull();
});

it('redirects the legacy dashboard to /lists', function () {
    $this->actingAs($this->alice)->get('/dashboard')->assertRedirect('/lists');
});

it('deletes a user\'s lists with the account', function () {
    ReadingList::factory()->for($this->alice)->create();

    $this->alice->delete();

    expect(ReadingList::query()->count())->toBe(0);
});
