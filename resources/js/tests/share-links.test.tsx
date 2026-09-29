import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vite-plus/test';
import ShareLinkForm, { ShareLinkField } from '@/components/share-link-form';
import BookList from '@/components/book-list';
import SharedList from '@/pages/share/show';

vi.mock('@inertiajs/react', () => ({
    router: {},
    Head: () => null,
}));

describe('share link form (owner)', () => {
    it('renders the "Link expires in" select (default "7 days") and a "Create share link" button', () => {
        const html = renderToStaticMarkup(<ShareLinkForm listId={1} />);
        expect(html).toMatch(
            /<label[^>]*for="share-expires-in"[^>]*>Link expires in<\/label>/,
        );
        expect(html).toMatch(/<select[^>]*id="share-expires-in"/);
        const options = [...html.matchAll(/<option[^>]*>([^<]*)<\/option>/g)];
        expect(options.map((option) => option[1])).toEqual([
            '1 minute',
            '1 day',
            '7 days',
        ]);
        expect(html).toMatch(/<option value="7d" selected="">7 days<\/option>/);
        expect(html).toMatch(
            /<button[^>]*type="submit"[^>]*>[\s\S]*Create share link<\/button>/,
        );
        expect(html).not.toContain('Share link</label>');
    });

    it('shows the created link in a read-only field labelled "Share link"', () => {
        const link = 'http://app:8080/s/1.1790000000.' + 'a'.repeat(64);
        const html = renderToStaticMarkup(<ShareLinkField link={link} />);
        expect(html).toMatch(
            /<label[^>]*for="share-link"[^>]*>Share link<\/label>/,
        );
        const input = html.match(/<input[^>]*id="share-link"[^>]*>/)?.[0];
        expect(input).toMatch(/readonly=""/i);
        expect(input).toContain(`value="${link}"`);
    });
});

describe('shared list page (anyone)', () => {
    const books = [
        {
            id: 1,
            title: 'Dune',
            authors: 'Frank Herbert',
            first_publish_year: 1965,
        },
        {
            id: 2,
            title: 'Dune: House Atreides',
            authors: 'Brian Herbert, Kevin J. Anderson',
            first_publish_year: 1999,
        },
        { id: 3, title: 'Untitled', authors: '', first_publish_year: null },
    ];

    it('shows the list name and all its books without any editing controls', () => {
        const html = renderToStaticMarkup(
            <SharedList
                list={{ name: 'Sci-fi Classics', description: null }}
                books={books}
            />,
        );
        expect(html).toMatch(/<h1[^>]*>Sci-fi Classics<\/h1>/);
        const items = html.match(/<li[\s\S]*?<\/li>/g) ?? [];
        expect(items).toHaveLength(3);
        expect(items[0]).toContain('Dune</p>');
        expect((items[0] ?? '').replace(/<[^>]*>/g, '')).toContain(
            'Frank Herbert · 1965',
        );
        expect(items[1]).toContain('Brian Herbert, Kevin J. Anderson');
        expect(items[2]).toContain('Unknown author');
        expect(html).not.toMatch(/<(button|form|input|select|a)\b/);
        for (const label of [
            'Edit',
            'Delete list',
            'Search',
            'Add',
            'Create share link',
        ]) {
            expect(html).not.toContain(`>${label}<`);
        }
    });

    it('renders the "Books" section exactly like the owner\'s list page', () => {
        const html = renderToStaticMarkup(
            <SharedList
                list={{ name: 'Sci-fi Classics', description: null }}
                books={books}
            />,
        );
        expect(html).toContain(
            renderToStaticMarkup(<BookList books={books} />),
        );
    });
});
