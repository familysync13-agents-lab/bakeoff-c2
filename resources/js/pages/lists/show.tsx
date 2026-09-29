import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { destroy, edit, index } from '@/routes/lists';

type Props = {
    list: { id: number; name: string };
};

export default function ListsShow({ list }: Props) {
    const [deleting, setDeleting] = useState(false);

    return (
        <>
            <Head title={list.name} />
            <Link
                href={index()}
                className="mb-4 inline-flex w-fit items-center gap-1.5 rounded-md text-sm text-muted-foreground hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
            >
                <ArrowLeft aria-hidden="true" className="size-4" />
                My lists
            </Link>
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <h1 className="min-w-0 text-2xl font-semibold tracking-tight break-words sm:text-3xl">
                    {list.name}
                </h1>
                <div className="flex shrink-0 flex-wrap gap-2">
                    <Button asChild variant="outline">
                        <Link href={edit(list.id)}>
                            <Pencil aria-hidden="true" />
                            Edit
                        </Link>
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        disabled={deleting}
                        onClick={() =>
                            router.delete(destroy.url(list.id), {
                                onStart: () => setDeleting(true),
                                onFinish: () => setDeleting(false),
                            })
                        }
                    >
                        {deleting ? <Spinner /> : <Trash2 aria-hidden="true" />}
                        Delete list
                    </Button>
                </div>
            </div>
            <p className="mt-8 rounded-xl border border-dashed border-border px-6 py-10 text-center text-sm text-muted-foreground">
                This list has no books yet.
            </p>
        </>
    );
}
