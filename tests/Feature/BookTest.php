<?php

use App\Models\Book;
use App\Models\ReadingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
    config(['services.book_api.base_url' => 'http://books.test']);
    $this->alice = User::factory()->create(['name' => 'Alice']);
    $this->bob = User::factory()->create(['name' => 'Bob']);
    $this->list = ReadingList::factory()->for($this->alice)->create(['name' => 'Sci-fi']);
});

function duneResponse(): array
{
    return [
        'numFound' => 3,
        'docs' => [
            ['key' => '/works/OL893415W', 'title' => 'Dune', 'author_name' => ['Frank Herbert'], 'first_publish_year' => 1965, 'cover_i' => 1],
            ['key' => '/works/OL16808977W', 'title' => 'Dune: House Atreides', 'author_name' => ['Brian Herbert', 'Kevin J. Anderson'], 'first_publish_year' => 1999],
            ['title' => 'Dune fan book'],
        ],
    ];
}

describe('search', function () {
    it('queries the API and returns the documents in API order', function () {
        Http::fake(['books.test/search.json*' => Http::response(duneResponse())]);

        $this->actingAs($this->alice)->getJson("/lists/{$this->list->id}/books/search?q=dune")
            ->assertOk()
            ->assertExactJson(['results' => [
                ['key' => '/works/OL893415W', 'title' => 'Dune', 'authors' => ['Frank Herbert'], 'year' => 1965],
                ['key' => '/works/OL16808977W', 'title' => 'Dune: House Atreides', 'authors' => ['Brian Herbert', 'Kevin J. Anderson'], 'year' => 1999],
                ['key' => null, 'title' => 'Dune fan book', 'authors' => [], 'year' => null],
            ]]);

        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'http://books.test/search.json?q=dune&limit=10');
    });

    it('returns at most 10 results', function () {
        $docs = array_map(fn (int $i) => ['key' => "/works/OL{$i}W", 'title' => "Book {$i}"], range(1, 15));
        Http::fake(['books.test/*' => Http::response(['numFound' => 15, 'docs' => $docs])]);

        $results = $this->actingAs($this->alice)->getJson("/lists/{$this->list->id}/books/search?q=book")
            ->assertOk()->json('results');

        expect($results)->toHaveCount(10)->and($results[0]['title'])->toBe('Book 1');
    });

    it('returns an empty result list', function () {
        Http::fake(['books.test/*' => Http::response(['numFound' => 0, 'docs' => []])]);

        $this->actingAs($this->alice)->getJson("/lists/{$this->list->id}/books/search?q=zzzz-nothing")
            ->assertOk()->assertExactJson(['results' => []]);
    });

    it('reports the API as unavailable on errors and malformed responses', function (Closure $response) {
        Http::fake(['books.test/*' => $response]);

        $this->actingAs($this->alice)->getJson("/lists/{$this->list->id}/books/search?q=dune")
            ->assertStatus(503)
            ->assertExactJson(['message' => 'Book search is unavailable']);
    })->with([
        'HTTP 500' => fn () => fn () => Http::response('boom', 500),
        'invalid JSON' => fn () => fn () => Http::response('{"numFound": 3, "docs": [ {"title": "Dune",', 200),
        'no docs' => fn () => fn () => Http::response(['numFound' => 1], 200),
        'docs not a list' => fn () => fn () => Http::response(['docs' => 'nope'], 200),
        'connection failure' => fn () => fn () => Http::failedConnection(),
    ]);

    it('gives up on an API that never responds within 5 seconds', function () {
        Http::allowStrayRequests();
        // A listening socket that accepts connections but never answers.
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        expect($server)->not->toBeFalse();
        config(['services.book_api.base_url' => 'http://'.stream_socket_get_name($server, false)]);

        $started = microtime(true);
        $this->actingAs($this->alice)->getJson("/lists/{$this->list->id}/books/search?q=__timeout__")
            ->assertStatus(503)
            ->assertJson(['message' => 'Book search is unavailable']);

        expect(microtime(true) - $started)->toBeLessThan(5.0);
        fclose($server);
    });

    it('requires a query', function () {
        $this->actingAs($this->alice)->getJson("/lists/{$this->list->id}/books/search?q=")
            ->assertUnprocessable();
        Http::assertNothingSent();
    });

    it('is only available to the owner', function () {
        Http::fake();

        $this->actingAs($this->bob)->getJson("/lists/{$this->list->id}/books/search?q=dune")->assertNotFound();
        auth()->logout();
        $this->getJson("/lists/{$this->list->id}/books/search?q=dune")->assertNotFound();

        Http::assertNothingSent();
    });
});

describe('adding books', function () {
    $dune = ['key' => '/works/OL893415W', 'title' => 'Dune', 'authors' => ['Frank Herbert'], 'year' => 1965];

    it('adds a book to the list and shows it on the list page', function () use ($dune) {
        $this->actingAs($this->alice)
            ->post("/lists/{$this->list->id}/books", $dune)
            ->assertRedirect("/lists/{$this->list->id}");

        $this->get("/lists/{$this->list->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('lists/show')
                ->has('books', 1)
                ->where('books.0.title', 'Dune')
                ->where('books.0.authors', 'Frank Herbert')
                ->where('books.0.first_publish_year', 1965));
    });

    it('does not add the same book twice', function () use ($dune) {
        $this->actingAs($this->alice);
        $this->post("/lists/{$this->list->id}/books", $dune)->assertRedirect();
        $this->post("/lists/{$this->list->id}/books", $dune)->assertRedirect();

        expect($this->list->books()->count())->toBe(1);
    });

    it('deduplicates books without a key by their details', function () {
        $book = ['key' => null, 'title' => 'Dune fan book', 'authors' => [], 'year' => null];
        $this->actingAs($this->alice);
        $this->post("/lists/{$this->list->id}/books", $book)->assertRedirect();
        $this->post("/lists/{$this->list->id}/books", $book)->assertRedirect();
        $this->post("/lists/{$this->list->id}/books", [...$book, 'title' => 'Other'])->assertRedirect();

        expect($this->list->books()->pluck('title')->all())->toBe(['Dune fan book', 'Other']);
    });

    it('keeps books per list and joins several authors', function () use ($dune) {
        $other = ReadingList::factory()->for($this->alice)->create();
        $this->actingAs($this->alice);
        $this->post("/lists/{$this->list->id}/books", $dune);
        $this->post("/lists/{$other->id}/books", [...$dune, 'authors' => ['Brian Herbert', 'Kevin J. Anderson']]);

        expect($this->list->books()->count())->toBe(1)
            ->and($other->books()->sole()->authors)->toBe('Brian Herbert, Kevin J. Anderson');
    });

    it('rejects invalid books', function (array $payload) {
        $this->actingAs($this->alice)
            ->from("/lists/{$this->list->id}")
            ->post("/lists/{$this->list->id}/books", $payload)
            ->assertSessionHasErrors();

        expect(Book::query()->count())->toBe(0);
    })->with([
        'no title' => [['key' => '/works/X', 'authors' => []]],
        'authors not a list' => [['title' => 'T', 'authors' => 'x']],
        'year not a number' => [['title' => 'T', 'authors' => [], 'year' => 'soon']],
    ]);

    it('does not let another user or a guest add books', function () use ($dune) {
        $this->actingAs($this->bob)->post("/lists/{$this->list->id}/books", $dune)->assertNotFound();
        $this->actingAs($this->bob)->post("/lists/{$this->list->id}/books", ['title' => ''])->assertNotFound();
        auth()->logout();
        $this->post("/lists/{$this->list->id}/books", $dune)->assertNotFound();

        expect(Book::query()->count())->toBe(0);
    });

    it('removes the books with their list', function () use ($dune) {
        $this->actingAs($this->alice)->post("/lists/{$this->list->id}/books", $dune);
        $this->delete("/lists/{$this->list->id}")->assertRedirect('/lists');

        expect(Book::query()->count())->toBe(0);
    });
});
