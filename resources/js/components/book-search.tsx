import { router } from '@inertiajs/react';
import { AlertCircleIcon, Plus, Search } from 'lucide-react';
import { useRef, useState } from 'react';
import type { FormEvent } from 'react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { search as searchRoute, store } from '@/routes/lists/books';

export type BookResult = {
    key: string | null;
    title: string;
    authors: string[];
    year: number | null;
};

export type State =
    | { status: 'idle' }
    | { status: 'loading' }
    | { status: 'error' }
    | { status: 'done'; results: BookResult[] };

/** The server gives up on the book API after 4 s; this is only a backstop for a stalled connection to the app. */
const CLIENT_TIMEOUT_MS = 6500;

export const UNAVAILABLE_MESSAGE = 'Book search is unavailable';

function isBookResult(value: unknown): value is BookResult {
    if (typeof value !== 'object' || value === null) {
        return false;
    }
    const book = value as Record<string, unknown>;
    return (
        (book.key === null || typeof book.key === 'string') &&
        typeof book.title === 'string' &&
        Array.isArray(book.authors) &&
        book.authors.every((author) => typeof author === 'string') &&
        (book.year === null || typeof book.year === 'number')
    );
}

export async function fetchResults(
    url: string,
    signal: AbortSignal,
): Promise<BookResult[]> {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        signal,
    });
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }
    const body: unknown = await response.json();
    const results = (body as { results?: unknown } | null)?.results;
    if (!Array.isArray(results) || !results.every(isBookResult)) {
        throw new Error('Unexpected response');
    }
    return results;
}

/**
 * Owner-only book search: queries the book API through the app (GET /lists/{id}/books/search) and adds results to
 * the list. Failures only affect this component; the rest of the page keeps working.
 */
export default function BookSearch({ listId }: { listId: number }) {
    const [query, setQuery] = useState('');
    const [state, setState] = useState<State>({ status: 'idle' });
    const [adding, setAdding] = useState<number | null>(null);
    const current = useRef<AbortController | null>(null);

    const submit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const q = query.trim();
        if (q === '') {
            return;
        }

        current.current?.abort();
        const controller = new AbortController();
        current.current = controller;
        const timer = window.setTimeout(
            () => controller.abort(),
            CLIENT_TIMEOUT_MS,
        );
        setState({ status: 'loading' });

        try {
            const results = await fetchResults(
                searchRoute.url(listId, { query: { q } }),
                controller.signal,
            );
            if (current.current === controller) {
                setState({ status: 'done', results });
            }
        } catch {
            if (current.current === controller) {
                setState({ status: 'error' });
            }
        } finally {
            window.clearTimeout(timer);
        }
    };

    const add = (book: BookResult, index: number) => {
        router.post(
            store.url(listId),
            {
                key: book.key,
                title: book.title,
                authors: book.authors,
                year: book.year,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setAdding(index),
                onFinish: () => setAdding(null),
            },
        );
    };

    const loading = state.status === 'loading';

    return (
        <div className="grid gap-4">
            <form
                role="search"
                onSubmit={submit}
                className="grid max-w-xl gap-2"
            >
                <Label htmlFor="book-query">Search books</Label>
                <div className="flex gap-2">
                    <Input
                        id="book-query"
                        name="q"
                        type="search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        maxLength={200}
                        autoComplete="off"
                        placeholder="Title or author"
                    />
                    <Button type="submit" disabled={loading}>
                        {loading ? <Spinner /> : <Search aria-hidden="true" />}
                        Search
                    </Button>
                </div>
            </form>

            <SearchOutcome state={state} adding={adding} onAdd={add} />
        </div>
    );
}

/** The outcome of the latest search: an alert when the API is unavailable, otherwise the results (or "No books found"). */
export function SearchOutcome({
    state,
    adding,
    onAdd,
}: {
    state: State;
    adding: number | null;
    onAdd: (book: BookResult, index: number) => void;
}) {
    if (state.status === 'error') {
        return (
            <Alert variant="destructive" className="max-w-xl">
                <AlertCircleIcon aria-hidden="true" />
                <AlertDescription>
                    {UNAVAILABLE_MESSAGE}. Please try again later.
                </AlertDescription>
            </Alert>
        );
    }

    if (state.status !== 'done') {
        return null;
    }

    return (
        <section aria-label="Search results" className="max-w-xl">
            {state.results.length === 0 ? (
                <p className="text-sm text-muted-foreground">No books found</p>
            ) : (
                <ul className="divide-y divide-border rounded-xl border border-border">
                    {state.results.map((book, index) => (
                        <li
                            key={`${index}-${book.key ?? book.title}`}
                            className="flex items-center justify-between gap-4 px-4 py-3"
                        >
                            <div className="min-w-0">
                                <p className="font-medium break-words">
                                    {book.title}
                                </p>
                                <p className="text-sm break-words text-muted-foreground">
                                    {book.authors.length > 0
                                        ? book.authors.join(', ')
                                        : 'Unknown author'}
                                    {book.year !== null && <> · {book.year}</>}
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={adding === index}
                                onClick={() => onAdd(book, index)}
                            >
                                {adding === index ? (
                                    <Spinner />
                                ) : (
                                    <Plus aria-hidden="true" />
                                )}
                                Add
                            </Button>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
