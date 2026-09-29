import { Head } from '@inertiajs/react';
import BookList from '@/components/book-list';
import type { ListBook } from '@/components/book-list';
import ListDescription from '@/components/list-description';

type Props = {
    list: { name: string; description: string | null };
    books: ListBook[];
};

/** A list opened through a share link: read-only, available to anyone holding a valid link. */
export default function SharedList({ list, books }: Props) {
    return (
        <>
            <Head title={list.name}>
                <meta name="referrer" content="no-referrer" />
                <meta name="robots" content="noindex, nofollow" />
            </Head>
            <p className="mb-2 text-sm text-muted-foreground">
                Shared reading list (read-only)
            </p>
            <h1 className="min-w-0 text-2xl font-semibold tracking-tight break-words sm:text-3xl">
                {list.name}
            </h1>
            <ListDescription description={list.description} />

            <BookList books={books} />
        </>
    );
}
