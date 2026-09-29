<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Forced errors for checking error monitoring (T5). Not available when APP_ENV is "production".
 */
class DebugController extends Controller
{
    /**
     * GET /debug/server-error: an unhandled server-side exception (HTTP 500), reported to Sentry by the exception handler.
     */
    public function serverError(): never
    {
        $this->abortInProduction();

        throw new RuntimeException('Forced server error (GET /debug/server-error)');
    }

    /**
     * GET /debug/client-error: a page whose button throws an unhandled error in the browser.
     */
    public function clientError(): Response
    {
        $this->abortInProduction();

        return Inertia::render('debug/client-error');
    }

    private function abortInProduction(): void
    {
        abort_if(app()->isProduction(), 404);
    }
}
