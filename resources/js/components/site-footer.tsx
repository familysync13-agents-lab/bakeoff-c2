import AppLogoIcon from '@/components/app-logo-icon';
import { PRODUCT_NAME } from '@/lib/site';

/** App shell footer. */
export function SiteFooter() {
    return (
        <footer className="border-t border-border/70 bg-stone-50 dark:bg-stone-950">
            <div className="mx-auto flex w-full max-w-6xl flex-col gap-3 px-4 py-8 text-sm text-stone-600 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8 dark:text-stone-400">
                <p className="flex items-center gap-2 font-medium text-foreground">
                    <AppLogoIcon className="size-4 fill-current text-amber-800 dark:text-amber-500" />
                    {PRODUCT_NAME}
                </p>
                <p>Make reading lists, add books and share them read-only.</p>
            </div>
        </footer>
    );
}
