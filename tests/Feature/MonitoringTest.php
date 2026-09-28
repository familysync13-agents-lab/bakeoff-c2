<?php

/**
 * @return array<string, mixed>
 */
function monitoringConfig(string $html): array
{
    preg_match('#<script id="monitoring-config" type="application/json">(.*?)</script>#s', $html, $m);

    return json_decode($m[1] ?? 'null', true, flags: JSON_THROW_ON_ERROR);
}

it('renders the public browser monitoring settings', function () {
    config([
        'services.sentry_browser.dsn' => 'http://public@ingest:9000/1',
        'app.release' => 'abc123',
        'app.env' => 'preview',
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    expect(monitoringConfig($html))->toBe([
        'dsn' => 'http://public@ingest:9000/1',
        'release' => 'abc123',
        'environment' => 'preview',
    ]);
});

it('renders normally without a DSN', function () {
    config(['services.sentry_browser.dsn' => null]);

    $html = $this->get('/')->assertOk()->getContent();

    expect(monitoringConfig($html)['dsn'])->toBeNull();
});

it('configures the server SDK from SENTRY_DSN, APP_RELEASE and APP_ENV', function () {
    $_ENV['SENTRY_DSN'] = $_SERVER['SENTRY_DSN'] = 'http://public@ingest:9000/1';
    $_ENV['APP_RELEASE'] = $_SERVER['APP_RELEASE'] = 'abc123';

    try {
        $config = require config_path('sentry.php');
    } finally {
        unset($_ENV['APP_RELEASE'], $_SERVER['APP_RELEASE']);
        $_ENV['SENTRY_DSN'] = $_SERVER['SENTRY_DSN'] = '';
    }

    expect($config['dsn'])->toBe('http://public@ingest:9000/1')
        ->and($config['release'])->toBe('abc123')
        ->and($config['environment'])->toBe(env('APP_ENV'))
        ->and(app()->bound('sentry'))->toBeTrue();
});
