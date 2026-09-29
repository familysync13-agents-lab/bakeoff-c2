<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Models\ReadingList;
use App\Services\BookSearch;
use App\Services\BookSearchUnavailable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookController extends Controller
{
    /**
     * Searches the book API on behalf of the list's owner (JSON; the browser never talks to the API directly).
     */
    public function search(Request $request, ReadingList $list, BookSearch $books): JsonResponse
    {
        Gate::authorize('update', $list);

        $query = $request->validate(['q' => ['required', 'string', 'max:200']])['q'];
        assert(is_string($query));

        try {
            return response()->json(['results' => $books->search(trim($query))]);
        } catch (BookSearchUnavailable) {
            return response()->json(['message' => 'Book search is unavailable'], 503);
        }
    }

    public function store(BookRequest $request, ReadingList $list): RedirectResponse
    {
        /** @var array{key?: string|null, title: string, authors: list<string>, year?: int|null} $book */
        $book = $request->validated();
        $authors = implode(', ', $book['authors']);
        $year = $book['year'] ?? null;
        $identity = ($book['key'] ?? null) ?: 'sha1:'.sha1(json_encode([$book['title'], $authors, $year], JSON_THROW_ON_ERROR));

        // Adding the same book twice keeps the single existing entry (unique per list and identity).
        $list->books()->firstOrCreate(
            ['identity' => $identity],
            ['title' => $book['title'], 'authors' => $authors, 'first_publish_year' => $year],
        );

        return to_route('lists.show', $list);
    }
}
