import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vite-plus/test';
import ListForm from '@/components/list-form';
import SharedList from '@/pages/share/show';

const formState = vi.hoisted(() => ({
    errors: {} as Record<string, string>,
}));

vi.mock('@inertiajs/react', () => ({
    router: {},
    Head: () => null,
    Form: ({
        children,
    }: {
        children: (state: {
            processing: boolean;
            errors: Record<string, string>;
        }) => ReactNode;
    }) => (
        <form>{children({ processing: false, errors: formState.errors })}</form>
    ),
}));

const target = { action: '/lists', method: 'post' as const };

describe('list form description field', () => {
    it('has an optional textarea labelled "Description", pre-filled when editing', () => {
        formState.errors = {};
        const html = renderToStaticMarkup(
            <ListForm
                target={target}
                defaultName="Classics"
                defaultDescription="Old & gold"
                submitLabel="Save"
                cancel={null}
            />,
        );
        expect(html).toMatch(
            /<label[^>]*for="description"[^>]*>Description<\/label>/,
        );
        const textarea = html.match(/<textarea[^>]*>[^<]*<\/textarea>/)?.[0];
        expect(textarea).toContain('name="description"');
        expect(textarea).not.toContain('aria-required');
        expect(textarea).toContain('>Old &amp; gold</textarea>');
    });

    it('announces a description error as an alert', () => {
        formState.errors = {
            description:
                'The description field must not be greater than 500 characters.',
        };
        const html = renderToStaticMarkup(
            <ListForm
                target={target}
                submitLabel="Create list"
                cancel={null}
            />,
        );
        expect(html).toMatch(
            /<p role="alert" id="description-error"[^>]*>The description field must not/,
        );
        expect(html).toMatch(/<textarea[^>]*aria-invalid="true"/);
    });
});

describe('description on the shared list page', () => {
    it('is shown as text directly below the h1', () => {
        const html = renderToStaticMarkup(
            <SharedList
                list={{ name: 'Classics', description: 'Line one\n<b>two</b>' }}
                books={[]}
            />,
        );
        expect(html).toMatch(
            /<\/h1><p[^>]*>Line one\n&lt;b&gt;two&lt;\/b&gt;<\/p>/,
        );
    });

    it('renders nothing when there is no description', () => {
        const withNone = renderToStaticMarkup(
            <SharedList
                list={{ name: 'Classics', description: null }}
                books={[]}
            />,
        );
        expect(withNone).toMatch(/<\/h1><section/);
    });
});
