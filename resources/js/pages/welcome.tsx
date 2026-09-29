import { Head } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    Check,
    Link2,
    ListPlus,
    Lock,
    Search,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { PRODUCT_NAME, SIGN_UP_URL } from '@/lib/site';

type Feature = {
    icon: LucideIcon;
    title: string;
    body: string;
};

const features: Feature[] = [
    {
        icon: ListPlus,
        title: 'Create reading lists',
        body: 'Start a list for anything: your book club, a summer stack, gift ideas. Rename it or tidy it up whenever you like.',
    },
    {
        icon: Search,
        title: 'Add books in seconds',
        body: 'Search a public book catalogue by title or author and add the right book, with its authors and first publication year.',
    },
    {
        icon: Link2,
        title: 'Share read-only',
        body: 'Create a link anyone can open without an account. They can browse the list; only you can change it.',
    },
];

const promises = [
    'Your lists are private until you share them',
    'Shared links are read-only and expire when you choose',
    'Friends need no account to open a shared list',
];

const sampleBooks = [
    {
        title: 'Pride and Prejudice',
        author: 'Jane Austen',
        year: 1813,
        spine: 'bg-rose-700',
    },
    {
        title: 'Middlemarch',
        author: 'George Eliot',
        year: 1871,
        spine: 'bg-emerald-700',
    },
    {
        title: 'Moby-Dick',
        author: 'Herman Melville',
        year: 1851,
        spine: 'bg-sky-700',
    },
];

/** Decorative preview of a shared list (hidden from assistive technology). */
function ListPreview() {
    return (
        <div aria-hidden="true" className="relative mx-auto w-full max-w-md">
            <div className="absolute -inset-4 -z-10 rounded-[2rem] bg-gradient-to-br from-amber-200/60 via-orange-100/40 to-transparent blur-2xl dark:from-amber-500/15 dark:via-orange-500/5" />
            <div className="rounded-2xl border border-stone-200 bg-white p-5 shadow-xl shadow-stone-900/5 sm:p-6 dark:border-stone-800 dark:bg-stone-900">
                <div className="flex items-start justify-between gap-4">
                    <div className="min-w-0">
                        <p className="text-lg font-semibold tracking-tight text-stone-900 dark:text-stone-50">
                            Weekend reads
                        </p>
                        <p className="text-sm text-stone-600 dark:text-stone-400">
                            3 books
                        </p>
                    </div>
                    <span className="inline-flex shrink-0 items-center gap-1 rounded-full bg-stone-100 px-2.5 py-1 text-xs font-medium text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                        <Lock className="size-3" aria-hidden="true" />
                        Only you can edit
                    </span>
                </div>

                <ul className="mt-5 divide-y divide-stone-100 dark:divide-stone-800">
                    {sampleBooks.map((book) => (
                        <li
                            key={book.title}
                            className="flex items-center gap-3 py-3"
                        >
                            <span
                                className={`h-12 w-8 shrink-0 rounded-sm shadow-sm ring-1 ring-black/10 ${book.spine}`}
                            />
                            <span className="min-w-0 flex-1">
                                <span className="block truncate text-sm font-medium text-stone-900 dark:text-stone-100">
                                    {book.title}
                                </span>
                                <span className="block truncate text-sm text-stone-600 dark:text-stone-400">
                                    {book.author} · {book.year}
                                </span>
                            </span>
                        </li>
                    ))}
                </ul>

                <div className="mt-4 rounded-xl border border-dashed border-amber-300 bg-amber-50 p-3 dark:border-amber-500/40 dark:bg-amber-500/10">
                    <div className="flex items-center justify-between gap-3">
                        <span className="flex min-w-0 items-center gap-2 text-sm font-medium text-amber-900 dark:text-amber-200">
                            <Link2
                                className="size-4 shrink-0"
                                aria-hidden="true"
                            />
                            <span className="truncate">Share link</span>
                        </span>
                        <span className="shrink-0 rounded-full bg-white px-2 py-0.5 text-xs font-medium text-amber-900 ring-1 ring-amber-200 dark:bg-stone-900 dark:text-amber-200 dark:ring-amber-500/30">
                            Read-only
                        </span>
                    </div>
                    <p className="mt-1.5 text-xs text-amber-900 dark:text-amber-200/90">
                        Anyone with the link can view · expires in 7 days
                    </p>
                </div>
            </div>
        </div>
    );
}

export default function Welcome() {
    return (
        <>
            <Head title="Create and share reading lists">
                <meta
                    name="description"
                    content="Create reading lists, add books and share them with a read-only link."
                />
            </Head>

            {/* Hero */}
            <section className="relative overflow-hidden border-b border-border/60 bg-gradient-to-b from-amber-50 via-orange-50/40 to-background dark:from-stone-900 dark:via-stone-950 dark:to-background">
                <div className="mx-auto grid w-full max-w-6xl items-center gap-12 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-2 lg:gap-16 lg:px-8 lg:py-24">
                    <div className="text-center lg:text-left">
                        <p className="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-white/70 px-3 py-1 text-sm font-medium text-amber-900 dark:border-amber-500/30 dark:bg-stone-900/70 dark:text-amber-200">
                            <BookOpen className="size-4" aria-hidden="true" />
                            Reading lists, made to share
                        </p>
                        <h1 className="mt-6 text-4xl font-semibold tracking-tight text-balance text-stone-900 sm:text-5xl lg:text-6xl dark:text-stone-50">
                            {PRODUCT_NAME}
                        </h1>
                        <p className="mx-auto mt-5 max-w-xl text-lg text-pretty text-stone-700 sm:text-xl lg:mx-0 dark:text-stone-300">
                            Collect the books you want to read in tidy lists,
                            then share any list with a read-only link. Friends
                            can browse it; only you can change it.
                        </p>
                        <div className="mt-8 flex flex-col items-stretch justify-center gap-3 sm:flex-row sm:items-center lg:justify-start">
                            <Button
                                asChild
                                size="lg"
                                className="h-11 bg-amber-800 px-6 text-base text-white hover:bg-amber-900 dark:bg-amber-500 dark:text-stone-950 dark:hover:bg-amber-400"
                            >
                                <a href={SIGN_UP_URL}>
                                    Create your first list
                                    <ArrowRight aria-hidden="true" />
                                </a>
                            </Button>
                            <Button
                                asChild
                                size="lg"
                                variant="outline"
                                className="h-11 px-6 text-base"
                            >
                                <a href="#how-it-works">See how it works</a>
                            </Button>
                        </div>
                    </div>

                    <ListPreview />
                </div>
            </section>

            {/* How it works */}
            <section
                id="how-it-works"
                aria-labelledby="how-it-works-title"
                className="scroll-mt-16 py-16 sm:py-24"
            >
                <div className="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-2xl text-center">
                        <p className="text-sm font-semibold tracking-wide text-amber-800 uppercase dark:text-amber-400">
                            How it works
                        </p>
                        <h2
                            id="how-it-works-title"
                            className="mt-2 text-3xl font-semibold tracking-tight text-balance sm:text-4xl"
                        >
                            From “I should read that” to a list worth sharing
                        </h2>
                    </div>

                    <ol className="mt-12 grid gap-6 md:grid-cols-3">
                        {features.map((feature, index) => (
                            <li
                                key={feature.title}
                                className="relative rounded-2xl border border-border bg-card p-6 shadow-sm"
                            >
                                <div className="flex items-center gap-3">
                                    <span className="flex size-10 items-center justify-center rounded-xl bg-amber-100 text-amber-900 dark:bg-amber-500/15 dark:text-amber-300">
                                        <feature.icon
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <span className="text-sm font-medium text-muted-foreground">
                                        Step {index + 1}
                                    </span>
                                </div>
                                <h3 className="mt-4 text-lg font-semibold tracking-tight">
                                    {feature.title}
                                </h3>
                                <p className="mt-2 text-pretty text-muted-foreground">
                                    {feature.body}
                                </p>
                            </li>
                        ))}
                    </ol>
                </div>
            </section>

            {/* Privacy promise and closing call to action */}
            <section aria-labelledby="yours-title" className="pb-16 sm:pb-24">
                <div className="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div className="grid gap-10 rounded-3xl bg-stone-900 px-6 py-10 text-stone-100 sm:px-10 sm:py-14 lg:grid-cols-2 lg:items-center dark:bg-stone-900 dark:ring-1 dark:ring-stone-800">
                        <div>
                            <h2
                                id="yours-title"
                                className="text-3xl font-semibold tracking-tight text-balance text-white sm:text-4xl"
                            >
                                Your lists stay yours
                            </h2>
                            <p className="mt-4 max-w-lg text-pretty text-stone-300">
                                Share what you are reading without handing over
                                the keys. You decide who sees a list and for how
                                long.
                            </p>
                            <Button
                                asChild
                                size="lg"
                                className="mt-8 h-11 w-full bg-white px-6 text-base text-stone-900 hover:bg-stone-100 sm:w-auto"
                            >
                                <a href={SIGN_UP_URL}>Get started</a>
                            </Button>
                        </div>
                        <ul className="space-y-4">
                            {promises.map((promise) => (
                                <li key={promise} className="flex gap-3">
                                    <span className="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-amber-400 text-stone-950">
                                        <Check
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <span className="text-pretty text-stone-200">
                                        {promise}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </section>
        </>
    );
}
