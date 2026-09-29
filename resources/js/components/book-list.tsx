export type ListBook = {
    id: number;
    title: string;
    authors: string;
    first_publish_year: number | null;
};

/**
 * The "Books" section of a list: each added book with its title and author(s) (and first publish year). Shared by
 * the owner's list page and the read-only share page, so both show a list's books identically.
 */
export default function BookList({ books }: { books: ListBook[] }) {
    return (
        <section aria-labelledby="books-heading" className="mt-8 grid gap-3">
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
                                {book.first_publish_year !== null && (
                                    <> · {book.first_publish_year}</>
                                )}
                            </p>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
