import { Head } from '@inertiajs/react';

type Props = {
    list: { name: string };
    books: { id: number; title: string; authors: string }[];
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

            <section
                aria-labelledby="books-heading"
                className="mt-8 grid gap-3"
            >
                <h2
                    id="books-heading"
                    className="text-xl font-semibold tracking-tight"
                >
                    Books
                </h2>
                {books.length === 0 ? (
                    <p className="rounded-xl border border-dashed border-border px-6 py-10 text-center text-sm text-muted-foreground">
                        This list has no books yet.
                    </p>
                ) : (
                    <ul className="divide-y divide-border rounded-xl border border-border">
                        {books.map((book) => (
                            <li key={book.id} className="px-4 py-3">
                                <p className="font-medium break-words">
                                    {book.title}
                                </p>
                                <p className="text-sm break-words text-muted-foreground">
                                    {book.authors || 'Unknown author'}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </>
    );
}
