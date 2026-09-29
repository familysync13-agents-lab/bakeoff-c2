import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

const messages: Record<number, { title: string; body: string }> = {
    403: {
        title: 'Access denied',
        body: 'You do not have permission to open this page.',
    },
    404: {
        title: 'Page not found',
        body: 'This page does not exist or is not available to you.',
    },
    410: {
        title: 'Link expired',
        body: 'This share link has expired. Ask the list owner for a new one.',
    },
};

export default function ErrorPage({ status }: { status: number }) {
    const { title, body } = messages[status] ?? messages[404];

    return (
        <>
            <Head title={title} />
            <div className="mx-auto flex max-w-md flex-1 flex-col items-center justify-center gap-4 py-16 text-center">
                <p className="text-sm font-semibold text-amber-800 dark:text-amber-400">
                    {status}
                </p>
                <h1 className="text-2xl font-semibold tracking-tight">
                    {title}
                </h1>
                <p className="text-muted-foreground">{body}</p>
                <Button asChild variant="outline">
                    <Link href="/">Go to the home page</Link>
                </Button>
            </div>
        </>
    );
}
