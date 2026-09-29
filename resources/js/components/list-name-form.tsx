import { Form } from '@inertiajs/react';
import type { ComponentProps, ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    /** Wayfinder form target (`store.form()` / `update.form(id)`). */
    target: Pick<ComponentProps<typeof Form>, 'action' | 'method'>;
    defaultName?: string;
    submitLabel: string;
    cancel: ReactNode;
};

/**
 * The reading-list name form used by "New list" and "Edit". Length limits are validated by the server (1-100
 * characters) so that every invalid submission gets the same visible, announced error message.
 */
export default function ListNameForm({
    target,
    defaultName = '',
    submitLabel,
    cancel,
}: Props) {
    return (
        <Form {...target} className="grid max-w-xl gap-6" noValidate>
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            type="text"
                            defaultValue={defaultName}
                            autoFocus
                            autoComplete="off"
                            placeholder="e.g. Summer reading"
                            aria-required="true"
                            aria-invalid={errors.name ? true : undefined}
                            aria-describedby={
                                errors.name ? 'name-error' : 'name-hint'
                            }
                        />
                        {errors.name ? (
                            <InputError id="name-error" message={errors.name} />
                        ) : (
                            <p
                                id="name-hint"
                                className="text-sm text-muted-foreground"
                            >
                                Up to 100 characters.
                            </p>
                        )}
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            {submitLabel}
                        </Button>
                        {cancel}
                    </div>
                </>
            )}
        </Form>
    );
}
