<?php

use App\Models\ReadingList;
use App\Models\User;
use App\Services\ShareLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

// Change request T6: optional list descriptions (tasks/T6/contract.json).

beforeEach(function () {
    $this->alice = User::factory()->create(['name' => 'Alice']);
});

it('creates a list with a description, edits it and shows the current one (AC1)', function () {
    $this->actingAs($this->alice)
        ->post('/lists', ['name' => 'Summer reading', 'description' => 'Beach books'])
        ->assertSessionHasNoErrors();
    $list = ReadingList::query()->sole();

    $this->get("/lists/{$list->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('lists/show')
            ->where('list.description', 'Beach books'));
    $this->get("/lists/{$list->id}/edit")
        ->assertInertia(fn (Assert $page) => $page
            ->component('lists/edit')
            ->where('list.description', 'Beach books'));

    $this->patch("/lists/{$list->id}", ['name' => 'Summer reading', 'description' => "Hammock books\nand more"])
        ->assertRedirect("/lists/{$list->id}");

    expect($list->fresh()?->description)->toBe("Hammock books\nand more");
    $this->get("/lists/{$list->id}")
        ->assertInertia(fn (Assert $page) => $page->where('list.description', "Hammock books\nand more"));
});

it('clears the description when it is emptied on edit', function (string $blank) {
    $list = ReadingList::factory()->for($this->alice)->create(['description' => 'Old']);

    $this->actingAs($this->alice)
        ->patch("/lists/{$list->id}", ['name' => $list->name, 'description' => $blank])
        ->assertSessionHasNoErrors();

    expect($list->fresh()?->description)->toBeNull();
})->with(['empty' => '', 'blank' => "  \r\n "]);

it('shows the description on the share page (AC2)', function () {
    config(['services.share_links.key' => 'test-share-key-3f9c2a7e1b']);
    $this->app->forgetInstance(ShareLinks::class);
    $list = ReadingList::factory()->for($this->alice)->create(['name' => 'Classics', 'description' => 'Old and gold']);
    $token = app(ShareLinks::class)->create($list, '7d');

    $this->assertGuest();
    $this->get("/s/{$token}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('share/show')
            ->where('list', ['name' => 'Classics', 'description' => 'Old and gold']));
});

it('accepts exactly 500 characters on create and edit (AC3)', function () {
    $text = str_repeat('é', 500);
    $this->actingAs($this->alice)
        ->post('/lists', ['name' => 'Long', 'description' => $text])
        ->assertSessionHasNoErrors();
    $list = ReadingList::query()->sole();
    expect($list->description)->toBe($text);

    $edited = str_repeat("a\r\n", 166).'ab'; // 500 characters once line breaks are normalised
    $this->patch("/lists/{$list->id}", ['name' => 'Long', 'description' => $edited])
        ->assertSessionHasNoErrors();
    expect($list->fresh()?->description)->toBe(str_repeat("a\n", 166).'ab');
});

it('rejects a description over 500 characters on create and saves nothing (AC3)', function () {
    $this->actingAs($this->alice)
        ->from('/lists/new')
        ->post('/lists', ['name' => 'Long', 'description' => str_repeat('x', 501)])
        ->assertRedirect('/lists/new')
        ->assertSessionHasErrors(['description' => 'The description field must not be greater than 500 characters.']);

    expect(ReadingList::query()->count())->toBe(0);
});

it('rejects a description over 500 characters on edit and keeps the list unchanged (AC3)', function () {
    $list = ReadingList::factory()->for($this->alice)->create(['name' => 'Classics', 'description' => 'Old']);

    $this->actingAs($this->alice)
        ->from("/lists/{$list->id}/edit")
        ->patch("/lists/{$list->id}", ['name' => 'Renamed', 'description' => str_repeat('x', 501)])
        ->assertRedirect("/lists/{$list->id}/edit")
        ->assertSessionHasErrors('description');

    expect($list->fresh()?->only('name', 'description'))->toBe(['name' => 'Classics', 'description' => 'Old']);
});

it('keeps lists without a description working (AC4)', function () {
    $this->actingAs($this->alice)->post('/lists', ['name' => 'Plain'])->assertSessionHasNoErrors();
    $list = ReadingList::query()->sole();
    expect($list->description)->toBeNull();

    $this->get("/lists/{$list->id}")
        ->assertInertia(fn (Assert $page) => $page->where('list.description', null));
    $this->get("/lists/{$list->id}/edit")
        ->assertInertia(fn (Assert $page) => $page->where('list.description', null));

    // Renaming without sending a description leaves it untouched.
    $list->update(['description' => 'Kept']);
    $this->patch("/lists/{$list->id}", ['name' => 'Renamed'])->assertSessionHasNoErrors();
    expect($list->fresh()?->only('name', 'description'))->toBe(['name' => 'Renamed', 'description' => 'Kept']);
});
