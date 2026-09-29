import { Head, Link } from '@inertiajs/react';
import ListNameForm from '@/components/list-name-form';
import { Button } from '@/components/ui/button';
import { show, update } from '@/routes/lists';

type Props = {
    list: { id: number; name: string };
};

export default function ListsEdit({ list }: Props) {
    return (
        <>
            <Head title="Edit list" />
            <h1 className="mb-6 text-2xl font-semibold tracking-tight sm:text-3xl">
                Edit list
            </h1>
            <ListNameForm
                target={update.form(list.id)}
                defaultName={list.name}
                submitLabel="Save"
                cancel={
                    <Button asChild variant="ghost">
                        <Link href={show(list.id)}>Cancel</Link>
                    </Button>
                }
            />
        </>
    );
}
