<?php

use App\Support\SentryScrubber;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\ExceptionDataBag;
use Sentry\Frame;
use Sentry\Options;
use Sentry\Serializer\PayloadSerializer;
use Sentry\Stacktrace;

it('raises an unhandled server error on GET /debug/server-error', function () {
    $this->get('/debug/server-error')->assertStatus(500);
});

it('reports the forced server error to the exception handler (Sentry)', function () {
    $this->withoutExceptionHandling();

    $this->get('/debug/server-error');
})->throws(RuntimeException::class, 'Forced server error');

it('shows the client error page', function () {
    $this->get('/debug/client-error')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('debug/client-error'));
});

it('hides the debug routes when APP_ENV is production', function (string $uri) {
    app()->detectEnvironment(fn () => 'production');

    $this->get($uri)->assertNotFound();
})->with(['/debug/server-error', '/debug/client-error']);

it('is wired as the Sentry before_send hook and survives config caching', function () {
    $hook = config('sentry.before_send');

    expect($hook)->toBe([SentryScrubber::class, 'beforeSend'])
        ->and(is_callable($hook))->toBeTrue()
        ->and(var_export($hook, true))->not->toContain('Closure');
});

it('removes the share-link signing key (V0_SECRET_CANARY) from error events', function () {
    $secret = 'AGENTSAPP-CANARY-0123456789abcdef';
    config(['services.share_links.key' => $secret]);

    $frame = new Frame('handle', 'app/Foo.php', 10);
    $frame->setVars(['key' => $secret, 'nested' => ['k' => "x{$secret}y"]]);
    $frame->setContextLine("\$key = '{$secret}';");
    $frame->setPreContext([$secret]);

    $event = Event::createEvent();
    $event->setExceptions([new ExceptionDataBag(new RuntimeException("bad {$secret}"), new Stacktrace([$frame]))]);
    $event->setMessage("message {$secret}");
    $event->setBreadcrumb([new Breadcrumb('info', 'default', 'log', "crumb {$secret}", ['v' => $secret])]);
    $event->setRequest(['url' => "http://app/?k={$secret}", 'headers' => [$secret => 'x']]);
    $event->setExtra(['s' => strtolower($secret)]);
    $event->setTags(['t' => $secret]);
    $event->setContext('app', ['s' => $secret]);

    $scrubbed = SentryScrubber::beforeSend($event);

    $dump = (new PayloadSerializer(new Options))->serialize($scrubbed);
    expect(stripos($dump, $secret))->toBeFalse()
        ->and($scrubbed->getExceptions()[0]->getValue())->toBe('bad '.SentryScrubber::REDACTED)
        ->and($dump)->toContain(SentryScrubber::REDACTED);
});

it('leaves events untouched without a signing key', function () {
    config(['services.share_links.key' => null]);
    $event = Event::createEvent();
    $event->setMessage('plain');

    expect(SentryScrubber::beforeSend($event)->getMessage())->toBe('plain');
});

it('registers the debug routes', function () {
    expect(Route::has('debug.server-error'))->toBeTrue()
        ->and(Route::has('debug.client-error'))->toBeTrue();
});
