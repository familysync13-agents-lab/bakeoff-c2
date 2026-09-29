import { Head, Link } from '@inertiajs/react';
import ListForm from '@/components/list-form';
import { Button } from '@/components/ui/button';
import { index, store } from '@/routes/lists';

export default function ListsCreate() {
    return (
        <>
            <Head title="New list" />
            <h1 className="mb-6 text-2xl font-semibold tracking-tight sm:text-3xl">
                New list
            </h1>
            <ListForm
                target={store.form()}
                submitLabel="Create list"
                cancel={
                    <Button asChild variant="ghost">
                        <Link href={index()}>Cancel</Link>
                    </Button>
                }
            />
        </>
    );
}
