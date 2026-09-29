import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vite-plus/test';
import SiteLayout from '@/layouts/site-layout';
import Welcome from '@/pages/welcome';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({
        href,
        children,
        ...rest
    }: {
        href: unknown;
        children: ReactNode;
    }) => (
        <a href={typeof href === 'string' ? href : '/'} {...rest}>
            {children}
        </a>
    ),
    usePage: () => ({
        props: { name: 'Shared Reading Lists', auth: { user: null } },
    }),
    router: {},
}));
vi.mock('@/routes', () => ({
    dashboard: () => '/dashboard',
    logout: () => '/logout',
}));
vi.mock('@/routes/profile', () => ({ edit: () => '/settings/profile' }));

const html = renderToStaticMarkup(
    <SiteLayout>
        <Welcome />
    </SiteLayout>,
);

const text = (fragment: string) =>
    fragment
        .replace(/<[^>]+>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

const links = [...html.matchAll(/<a\b([^>]*)>([\s\S]*?)<\/a>/g)].map((m) => ({
    href: /href="([^"]*)"/.exec(m[1])?.[1],
    name: text(m[2]),
}));

describe('landing page in the app shell (signed out)', () => {
    it('has exactly one h1, containing the product name', () => {
        const h1s = [...html.matchAll(/<h1\b[^>]*>([\s\S]*?)<\/h1>/g)];
        expect(h1s).toHaveLength(1);
        expect(text(h1s[0][1])).toContain('Shared Reading Lists');
    });

    it('links "Sign up" to /signup and "Sign in" to /login', () => {
        expect(links).toContainEqual({ href: '/signup', name: 'Sign up' });
        expect(links).toContainEqual({ href: '/login', name: 'Sign in' });
        // every link with one of those exact names points at the right page
        for (const l of links.filter((l) => l.name === 'Sign up')) {
            expect(l.href).toBe('/signup');
        }
        for (const l of links.filter((l) => l.name === 'Sign in')) {
            expect(l.href).toBe('/login');
        }
    });

    it('renders the shell: header with product name and primary navigation, main, footer', () => {
        expect(html).toMatch(/<header\b/);
        expect(html).toMatch(/<nav aria-label="Primary"/);
        expect(html).toMatch(/<main id="main"/);
        expect(html).toMatch(/<footer\b/);
        expect(links).toContainEqual({
            href: '/',
            name: 'Shared Reading Lists',
        });
        expect(links[0]).toEqual({
            href: '#main',
            name: 'Skip to main content',
        });
    });

    it('explains what the product does', () => {
        const body = text(html);
        expect(body).toContain('Create reading lists');
        expect(body).toContain('Add books');
        expect(body).toContain('Share read-only');
    });

    it('keeps decorative images out of the accessibility tree', () => {
        for (const svg of html.match(/<svg\b[^>]*>/g) ?? []) {
            expect(svg).toContain('aria-hidden="true"');
        }
    });
});
