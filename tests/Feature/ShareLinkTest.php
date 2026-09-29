<?php

use App\Models\ReadingList;
use App\Models\User;
use App\Services\ShareLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

const SHARE_KEY = 'test-share-key-3f9c2a7e1b';

beforeEach(function () {
    config(['services.share_links.key' => SHARE_KEY, 'app.url' => 'http://app.test:8080']);
    $this->app->forgetInstance(ShareLinks::class);
    $this->alice = User::factory()->create(['name' => 'Alice']);
    $this->bob = User::factory()->create(['name' => 'Bob']);
    $this->list = ReadingList::factory()->for($this->alice)->create(['name' => 'Sci-fi Classics']);
    $this->list->books()->create(['identity' => '/works/OL893415W', 'title' => 'Dune', 'authors' => 'Frank Herbert', 'first_publish_year' => 1965]);
});

/** Creates a share link as the owner through the UI endpoint and returns its path (/s/{token}). */
function createShareLink(ReadingList $list, string $expiresIn = '7d'): string
{
    $response = test()->actingAs($list->user)
        ->post("/lists/{$list->id}/share-links", ['expires_in' => $expiresIn])
        ->assertRedirect("/lists/{$list->id}");

    $url = $response->getSession()?->get('inertia.flash_data')['shareLink'] ?? null;
    expect($url)->toBeString()->toStartWith('http://app.test:8080/s/');
    app('auth')->forgetGuards();

    return (string) parse_url($url, PHP_URL_PATH);
}

function assertHidden(TestResponse $response, string $name): void
{
    expect($response->status())->toBeIn([403, 404, 410])
        ->and($response->getContent())->not->toContain($name);
}

describe('creating', function () {
    it('flashes an absolute share link to the owner', function (string $expiry, int $seconds) {
        $this->freezeSecond();
        $path = createShareLink($this->list, $expiry);

        expect($path)->toMatch('#^/s/\d+\.\d+\.[0-9a-f]{64}$#');
        $claims = app(ShareLinks::class)->verify(substr($path, 3));
        expect($claims)->toBe(['list' => $this->list->id, 'expires' => now()->getTimestamp() + $seconds]);
    })->with([['1m', 60], ['1d', 86_400], ['7d', 604_800]]);

    it('shows the share controls to the owner', function () {
        $this->actingAs($this->alice)->get("/lists/{$this->list->id}")
            ->assertInertia(fn (Assert $page) => $page->component('lists/show'));
    });

    it('rejects unknown expiry periods', function (mixed $expiry) {
        $this->actingAs($this->alice)
            ->from("/lists/{$this->list->id}")
            ->post("/lists/{$this->list->id}/share-links", ['expires_in' => $expiry])
            ->assertSessionHasErrors('expires_in')
            ->assertSessionMissing('inertia.flash_data');
    })->with(['', '30d', '60', null]);

    it('answers 404 to everyone but the owner', function () {
        $this->actingAs($this->bob)->post("/lists/{$this->list->id}/share-links", ['expires_in' => '7d'])->assertNotFound();
        auth()->logout();
        $this->post("/lists/{$this->list->id}/share-links", ['expires_in' => '7d'])->assertNotFound();
    });

    it('fails closed without a signing key', function () {
        config(['services.share_links.key' => '']);
        $this->app->forgetInstance(ShareLinks::class);
        $this->withoutExceptionHandling();

        $this->actingAs($this->alice)->post("/lists/{$this->list->id}/share-links", ['expires_in' => '7d']);
    })->throws(RuntimeException::class);
});

describe('viewing', function () {
    it('shows the list and its books read-only to an anonymous visitor (AC1)', function () {
        $path = createShareLink($this->list);

        $this->assertGuest();
        $this->get($path)
            ->assertOk()
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertInertia(fn (Assert $page) => $page
                ->component('share/show')
                ->where('list', ['name' => 'Sci-fi Classics'])
                ->has('books', 1)
                ->where('books.0.title', 'Dune')
                ->where('books.0.authors', 'Frank Herbert')
                ->where('books.0.first_publish_year', 1965));
    });

    it('rejects every single-character change, truncation and extension of the token (AC2)', function () {
        $path = createShareLink($this->list);
        $token = substr($path, 3);
        $alphabet = str_split('0123456789abcdefABCDEF.-_xz');

        $variants = [];
        for ($i = 0; $i < strlen($token); $i++) {
            foreach ($alphabet as $char) {
                if ($char !== $token[$i]) {
                    $variants[] = substr_replace($token, $char, $i, 1);
                }
            }
            $variants[] = substr($token, 0, $i);
            $variants[] = substr($token, $i + 1);
        }
        foreach ($alphabet as $char) {
            $variants[] = $token.$char;
            $variants[] = $char.$token;
        }

        $links = app(ShareLinks::class);
        foreach (array_unique($variants) as $variant) {
            expect($links->verify($variant))->toBeNull("accepted {$variant}");
        }

        // A sample over HTTP.
        foreach ([substr($token, 0, -1), $token.'0', substr_replace($token, $token[0] === '9' ? '8' : '9', 0, 1), substr_replace($token, 'x', -1, 1)] as $variant) {
            assertHidden($this->get('/s/'.$variant), 'Sci-fi Classics');
        }
        $this->get($path)->assertOk();
    });

    it('rejects tokens signed with another key or not signed at all', function () {
        $expires = now()->addDay()->getTimestamp();
        $payload = "{$this->list->id}.{$expires}";
        $forged = [
            $payload.'.'.hash_hmac('sha256', 'share-link:v1:'.$payload, 'another-key'),
            $payload.'.'.hash_hmac('sha256', $payload, SHARE_KEY),
            $payload.'.'.hash('sha256', $payload),
            $payload.'.'.strtoupper(hash_hmac('sha256', 'share-link:v1:'.$payload, SHARE_KEY)),
            "0{$payload}.".hash_hmac('sha256', 'share-link:v1:0'.$payload, SHARE_KEY),
        ];

        foreach ($forged as $token) {
            assertHidden($this->get('/s/'.$token), 'Sci-fi Classics');
        }
    });

    it('shows each list only through its own link (AC3)', function () {
        $other = ReadingList::factory()->for($this->bob)->create(['name' => 'Poetry Corner']);
        $first = createShareLink($this->list);
        $second = createShareLink($other);

        $this->get($first)->assertOk()->assertSee('Sci-fi Classics')->assertDontSee('Poetry Corner');
        $this->get($second)->assertOk()->assertSee('Poetry Corner')->assertDontSee('Sci-fi Classics')
            ->assertInertia(fn (Assert $page) => $page->has('books', 0));
    });

    it('expires: a 1-minute link works immediately and is gone 70 s later (AC4)', function () {
        $this->freezeSecond();
        $path = createShareLink($this->list, '1m');

        $this->get($path)->assertOk();
        $this->travel(59)->seconds();
        $this->get($path)->assertOk();
        $this->travel(1)->seconds();
        assertHidden($this->get($path)->assertStatus(410), 'Sci-fi Classics');
        $this->travel(10)->seconds();
        assertHidden($this->get($path), 'Sci-fi Classics');
    });

    it('answers 404 once the list is deleted (AC6)', function () {
        $path = createShareLink($this->list);

        $this->actingAs($this->alice)->delete("/lists/{$this->list->id}")->assertRedirect('/lists');
        auth()->logout();

        assertHidden($this->get($path)->assertNotFound(), 'Sci-fi Classics');
    });

    it('grants no write access to visitors of a share page (AC5)', function () {
        $path = createShareLink($this->list);
        $this->get($path)->assertOk();

        $this->patch("/lists/{$this->list->id}", ['name' => 'Hacked'])->assertNotFound();
        $this->delete("/lists/{$this->list->id}")->assertNotFound();
        $this->post("/lists/{$this->list->id}/books", ['key' => '/works/X', 'title' => 'Intruder', 'authors' => ['Eve'], 'year' => 2000])->assertNotFound();
        $this->post("/lists/{$this->list->id}/share-links", ['expires_in' => '7d'])->assertNotFound();
        $this->get("/lists/{$this->list->id}")->assertNotFound();

        $this->list->refresh();
        expect($this->list->name)->toBe('Sci-fi Classics')
            ->and($this->list->books()->pluck('title')->all())->toBe(['Dune']);
    });

    it('never exposes the signing key (AC7)', function () {
        $path = createShareLink($this->list);
        $owner = $this->actingAs($this->alice)->get("/lists/{$this->list->id}")->getContent();
        auth()->logout();

        foreach ([$owner, $this->get($path)->getContent(), $this->get('/s/garbage')->getContent(), $this->get('/')->getContent()] as $html) {
            expect($html)->not->toContain(SHARE_KEY);
        }
    });
});
