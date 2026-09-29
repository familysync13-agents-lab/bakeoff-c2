<?php

use App\Http\Controllers\HealthController;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Outside the web group: no session or cookies for the preview health check.
            Route::get('/healthz', HealthController::class)->name('healthz');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Share the Inertia props (signed-in user) before route-model binding, so its 404s render the signed-in shell.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: HandleInertiaRequests::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Render "forbidden" and "not found" inside the app shell.
        $exceptions->respond(function (SymfonyResponse $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();
            if (! in_array($status, [403, 404], true) || $request->expectsJson()) {
                return $response;
            }

            return Inertia::render('error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
