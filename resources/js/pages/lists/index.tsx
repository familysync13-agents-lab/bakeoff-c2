import { Head, Link } from '@inertiajs/react';
import { BookOpen, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { create, show } from '@/routes/lists';

type Props = {
    lists: { id: number; name: string }[];
};

export default function ListsIndex({ lists }: Props) {
    return (
        <>
            <Head title="My lists" />
            <div className="flex flex-wrap items-center justify-between gap-4">
                <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                    My lists
                </h1>
                <Button asChild>
                    <Link href={create()}>
                        <Plus aria-hidden="true" />
                        New list
                    </Link>
                </Button>
            </div>

            {lists.length === 0 ? (
                <div className="mt-8 flex flex-col items-center gap-3 rounded-xl border border-dashed border-border px-6 py-12 text-center">
                    <BookOpen
                        aria-hidden="true"
                        className="size-8 text-amber-800 dark:text-amber-400"
                    />
                    <p className="font-medium">
                        You have no reading lists yet.
                    </p>
                    <p className="max-w-sm text-sm text-muted-foreground">
                        Create your first list to start collecting books. Only
                        you can see your lists.
                    </p>
                </div>
            ) : (
                <ul className="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {lists.map((list) => (
                        <li key={list.id}>
                            <Link
                                href={show(list.id)}
                                className="flex h-full items-center gap-3 rounded-xl border border-border bg-card p-4 font-medium break-words shadow-xs transition-colors hover:border-amber-700/50 hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                            >
                                <BookOpen
                                    aria-hidden="true"
                                    className="size-5 shrink-0 text-amber-800 dark:text-amber-400"
                                />
                                <span className="min-w-0">{list.name}</span>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </>
    );
}
