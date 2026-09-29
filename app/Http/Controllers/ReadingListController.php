<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReadingListRequest;
use App\Models\ReadingList;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReadingListController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('lists/index', [
            'lists' => $this->user($request)->readingLists()
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get(['id', 'name', 'updated_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('lists/create');
    }

    public function store(ReadingListRequest $request): RedirectResponse
    {
        $list = $this->user($request)->readingLists()->create($request->validated());

        return to_route('lists.show', $list);
    }

    public function show(ReadingList $list): Response
    {
        Gate::authorize('view', $list);

        return Inertia::render('lists/show', [
            'list' => $list->only('id', 'name', 'description'),
            'books' => $list->books()->orderBy('id')->get(['id', 'title', 'authors', 'first_publish_year']),
        ]);
    }

    public function edit(ReadingList $list): Response
    {
        Gate::authorize('update', $list);

        return Inertia::render('lists/edit', ['list' => $list->only('id', 'name', 'description')]);
    }

    public function update(ReadingListRequest $request, ReadingList $list): RedirectResponse
    {
        // Ownership is authorized by ReadingListRequest.
        $list->update($request->validated());

        return to_route('lists.show', $list);
    }

    public function destroy(ReadingList $list): RedirectResponse
    {
        Gate::authorize('delete', $list);

        $list->delete();

        return to_route('lists.index');
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        assert($user instanceof User);

        return $user;
    }
}
