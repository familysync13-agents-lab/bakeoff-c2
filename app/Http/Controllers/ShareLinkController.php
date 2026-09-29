<?php

namespace App\Http\Controllers;

use App\Models\ReadingList;
use App\Services\ShareLinks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareLinkController extends Controller
{
    /**
     * Issues a share link for the owner's list; its absolute URL is flashed back to the list page.
     */
    public function store(Request $request, ReadingList $list, ShareLinks $links): RedirectResponse
    {
        // Ownership is checked before validation: everyone else gets a 404 whatever they submit.
        Gate::authorize('update', $list);

        /** @var array{expires_in: key-of<ShareLinks::EXPIRIES>} $input */
        $input = $request->validate([
            'expires_in' => ['required', 'string', Rule::in(array_keys(ShareLinks::EXPIRIES))],
        ]);

        Inertia::flash('shareLink', rtrim((string) config('app.url'), '/').'/s/'.$links->create($list, $input['expires_in']));

        return to_route('lists.show', $list);
    }

    /**
     * The public, read-only view of a shared list (no sign-in). Tokens this app did not issue and links of deleted
     * lists answer 404, expired links 410.
     */
    public function show(Request $request, string $token, ShareLinks $links): Response
    {
        $claims = $links->verify($token);
        abort_if($claims === null, 404);
        abort_if(now()->getTimestamp() >= $claims['expires'], 410);

        $list = ReadingList::query()->find($claims['list']);
        abort_if($list === null, 404);

        $response = Inertia::render('share/show', [
            'list' => $list->only('name', 'description'),
            'books' => $list->books()->orderBy('id')->get(['id', 'title', 'authors', 'first_publish_year']),
        ])->toResponse($request);
        $response->headers->add([
            // Never serve a shared list from a cache once the link has expired, and keep the token out of referrers.
            'Cache-Control' => 'no-store, private',
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);

        return $response;
    }
}
