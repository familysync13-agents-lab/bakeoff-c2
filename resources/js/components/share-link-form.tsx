import { router } from '@inertiajs/react';
import { Check, Copy, Link2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useClipboard } from '@/hooks/use-clipboard';
import { store } from '@/routes/lists/share-links';

/** Expiry periods offered for a share link (values accepted by the server). */
export const EXPIRY_OPTIONS = [
    { value: '1m', label: '1 minute' },
    { value: '1d', label: '1 day' },
    { value: '7d', label: '7 days' },
] as const;

/**
 * Owner-only: creates an expiring, signed, read-only link to the list (POST /lists/{id}/share-links) and shows it.
 * The link is kept in local state, so it stays visible while the owner keeps working on the page.
 */
export default function ShareLinkForm({ listId }: { listId: number }) {
    const [expiresIn, setExpiresIn] = useState('7d');
    const [link, setLink] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.post(
            store.url(listId),
            { expires_in: expiresIn },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: (page) => {
                    const created = page.flash.shareLink;
                    setError(null);
                    setLink(typeof created === 'string' ? created : null);
                },
                onError: (errors) =>
                    setError(
                        errors.expires_in ?? 'The share link was not created.',
                    ),
            },
        );
    };

    return (
        <section
            aria-labelledby="share-heading"
            className="grid max-w-xl gap-4"
        >
            <h2
                id="share-heading"
                className="text-xl font-semibold tracking-tight"
            >
                Share
            </h2>
            <p className="text-sm text-muted-foreground">
                Anyone with the link can view this list and its books until the
                link expires. They cannot change anything.
            </p>
            <form
                onSubmit={submit}
                className="flex flex-col gap-2 sm:flex-row sm:items-end"
            >
                <div className="grid gap-2">
                    <Label htmlFor="share-expires-in">Link expires in</Label>
                    <select
                        id="share-expires-in"
                        name="expires_in"
                        value={expiresIn}
                        onChange={(event) => setExpiresIn(event.target.value)}
                        aria-invalid={error ? true : undefined}
                        aria-describedby={error ? 'share-error' : undefined}
                        className="h-9 rounded-md border border-input bg-background px-3 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                    >
                        {EXPIRY_OPTIONS.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </div>
                <Button type="submit" variant="outline" disabled={processing}>
                    {processing ? <Spinner /> : <Link2 aria-hidden="true" />}
                    Create share link
                </Button>
            </form>
            {error && <InputError id="share-error" message={error} />}
            {link && <ShareLinkField link={link} />}
        </section>
    );
}

/** The created link: a read-only field labelled "Share link" with a copy button. */
export function ShareLinkField({ link }: { link: string }) {
    const [copied, copy] = useClipboard();

    return (
        <div className="grid gap-2">
            <Label htmlFor="share-link">Share link</Label>
            <div className="flex gap-2">
                <Input
                    id="share-link"
                    type="text"
                    value={link}
                    readOnly
                    onFocus={(event) => event.target.select()}
                    className="font-mono"
                />
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    aria-label={copied === link ? 'Copied' : 'Copy share link'}
                    onClick={() => void copy(link)}
                >
                    {copied === link ? (
                        <Check aria-hidden="true" />
                    ) : (
                        <Copy aria-hidden="true" />
                    )}
                </Button>
            </div>
        </div>
    );
}
