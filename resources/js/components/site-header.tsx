import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { PRODUCT_NAME, SIGN_IN_URL, SIGN_UP_URL } from '@/lib/site';
import { dashboard } from '@/routes';

/** App shell header: product name (home link) and the primary navigation. */
export function SiteHeader() {
    const { auth } = usePage().props;
    const getInitials = useInitials();
    const user = auth.user as typeof auth.user | null;

    return (
        <header className="sticky top-0 z-40 border-b border-border/70 bg-background/90 backdrop-blur supports-[backdrop-filter]:bg-background/75">
            <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                <Link
                    href="/"
                    className="flex min-w-0 items-center gap-2.5 rounded-md text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                >
                    <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-amber-800 text-white shadow-sm dark:bg-amber-500 dark:text-stone-950">
                        <AppLogoIcon className="size-5 fill-current" />
                    </span>
                    <span className="max-w-[7.5rem] text-sm leading-tight font-semibold tracking-tight sm:max-w-none sm:text-base">
                        {PRODUCT_NAME}
                    </span>
                </Link>

                <nav aria-label="Primary" className="shrink-0">
                    {user ? (
                        <ul className="flex items-center gap-1 sm:gap-2">
                            <li>
                                <Button asChild variant="ghost" size="sm">
                                    <Link href={dashboard()}>Dashboard</Link>
                                </Button>
                            </li>
                            <li>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            className="size-10 rounded-full p-1"
                                            aria-label="Account menu"
                                        >
                                            <Avatar className="size-8 overflow-hidden rounded-full">
                                                <AvatarImage
                                                    src={user.avatar}
                                                    alt=""
                                                />
                                                <AvatarFallback className="rounded-full bg-stone-200 text-stone-900 dark:bg-stone-700 dark:text-white">
                                                    {getInitials(user.name)}
                                                </AvatarFallback>
                                            </Avatar>
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        className="w-56"
                                        align="end"
                                    >
                                        <UserMenuContent user={user} />
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </li>
                        </ul>
                    ) : (
                        <ul className="flex items-center gap-1 sm:gap-2">
                            <li>
                                <Button
                                    asChild
                                    variant="ghost"
                                    size="sm"
                                    className="px-2.5 sm:px-3"
                                >
                                    <a href={SIGN_IN_URL}>Sign in</a>
                                </Button>
                            </li>
                            <li>
                                <Button
                                    asChild
                                    size="sm"
                                    className="px-3 sm:px-4"
                                >
                                    <a href={SIGN_UP_URL}>Sign up</a>
                                </Button>
                            </li>
                        </ul>
                    )}
                </nav>
            </div>
        </header>
    );
}
