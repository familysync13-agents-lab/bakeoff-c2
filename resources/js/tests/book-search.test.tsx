import { renderToStaticMarkup } from 'react-dom/server';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import BookSearch, {
    fetchResults,
    SearchOutcome,
} from '@/components/book-search';
import type { State } from '@/components/book-search';

vi.mock('@inertiajs/react', () => ({ router: {} }));

const outcome = (state: State) =>
    renderToStaticMarkup(
        <SearchOutcome state={state} adding={null} onAdd={() => {}} />,
    );

const dune = [
    { key: '/works/1', title: 'Dune', authors: ['Frank Herbert'], year: 1965 },
    {
        key: '/works/2',
        title: 'Dune: House Atreides',
        authors: ['Brian Herbert', 'Kevin J. Anderson'],
        year: 1999,
    },
    { key: null, title: 'Untitled', authors: [], year: null },
];

describe('book search', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('renders a labelled search field and a "Search" button', () => {
        const html = renderToStaticMarkup(<BookSearch listId={1} />);
        expect(html).toMatch(
            /<label[^>]*for="book-query"[^>]*>Search books<\/label>/,
        );
        expect(html).toMatch(/<input[^>]*id="book-query"/);
        expect(html).toMatch(
            /<button[^>]*type="submit"[^>]*>[\s\S]*Search<\/button>/,
        );
        expect(html).not.toContain('Search results');
    });

    it('renders one item per result in API order with title, authors, year and "Add"', () => {
        const html = outcome({ status: 'done', results: dune });
        expect(html).toMatch(/^<section aria-label="Search results"/);
        const items = html.match(/<li[\s\S]*?<\/li>/g) ?? [];
        expect(items).toHaveLength(3);
        expect(items[0]).toContain('Dune</p>');
        expect(items[0]).toContain('Frank Herbert');
        expect(items[0]).toContain('1965');
        expect(items[1]).toContain('Brian Herbert, Kevin J. Anderson');
        expect(items[2]).toContain('Unknown author');
        items.forEach((item) => expect(item).toMatch(/>Add<\/button>/));
    });

    it('shows "No books found" without items', () => {
        const html = outcome({ status: 'done', results: [] });
        expect(html).toContain('No books found');
        expect(html).not.toContain('<li');
    });

    it('shows an alert when the search is unavailable', () => {
        const html = outcome({ status: 'error' });
        expect(html).toMatch(/role="alert"[\s\S]*Book search is unavailable/);
    });

    it('rejects failed and malformed responses', async () => {
        const signal = new AbortController().signal;
        vi.stubGlobal(
            'fetch',
            vi.fn().mockResolvedValueOnce(Response.json({ results: dune })),
        );
        await expect(fetchResults('/x', signal)).resolves.toEqual(dune);

        vi.stubGlobal(
            'fetch',
            vi.fn().mockResolvedValue(Response.json({}, { status: 503 })),
        );
        await expect(fetchResults('/x', signal)).rejects.toThrow();

        vi.stubGlobal(
            'fetch',
            vi
                .fn()
                .mockResolvedValue(Response.json({ results: [{ title: 1 }] })),
        );
        await expect(fetchResults('/x', signal)).rejects.toThrow();
    });
});
