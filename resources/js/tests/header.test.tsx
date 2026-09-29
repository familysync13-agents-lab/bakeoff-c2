import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vite-plus/test';
import { SiteHeader } from '@/components/site-header';

const page = vi.hoisted(() => ({ props: {} as Record<string, unknown> }));

vi.mock('@inertiajs/react', () => ({
    Link: ({
        href,
        children,
        ...rest
    }: {
        href: unknown;
        children: ReactNode;
    }) => (
        <a
            href={
                typeof href === 'string'
                    ? href
                    : String((href as { url: string }).url)
            }
            {...rest}
        >
            {children}
        </a>
    ),
    usePage: () => page,
    router: {},
}));

const render = (props: Record<string, unknown>) => {
    page.props = props;
    return renderToStaticMarkup(<SiteHeader />);
};

describe('app shell header', () => {
    it('shows a "Sign out" button and "My lists" link when signed in', () => {
        const html = render({ auth: { user: { id: 1, name: 'Alice' } } });
        expect(html).toMatch(
            /<button[^>]*>(?:<svg[\s\S]*?<\/svg>)?Sign out<\/button>/,
        );
        expect(html).toMatch(/<a[^>]*href="\/lists"[^>]*>My lists<\/a>/);
        expect(html).not.toContain('Sign in');
    });

    it('shows "Sign in" and "Sign up" links when signed out', () => {
        const html = render({ auth: { user: null } });
        expect(html).toMatch(/<a[^>]*href="\/login"[^>]*>Sign in<\/a>/);
        expect(html).toMatch(/<a[^>]*href="\/signup"[^>]*>Sign up<\/a>/);
        expect(html).not.toContain('Sign out');
    });

    it('renders signed out on pages without shared props', () => {
        expect(render({})).toContain('Sign in');
    });
});
