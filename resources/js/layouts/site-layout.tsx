import type { ReactNode } from 'react';
import { SiteFooter } from '@/components/site-footer';
import { SiteHeader } from '@/components/site-header';

/** The app shell used by every page: header, main content area, footer. */
export default function SiteLayout({ children }: { children: ReactNode }) {
    return (
        <div className="flex min-h-svh flex-col bg-background text-foreground">
            <a
                href="#main"
                className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-md focus:bg-background focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:shadow-lg focus:ring-[3px] focus:ring-ring/50"
            >
                Skip to main content
            </a>
            <SiteHeader />
            <main id="main" className="flex flex-1 flex-col">
                {children}
            </main>
            <SiteFooter />
        </div>
    );
}
