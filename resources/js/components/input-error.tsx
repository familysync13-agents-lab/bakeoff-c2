import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

/** A field's validation message; announced to assistive technology as an alert. */
export default function InputError({
    message,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    return message ? (
        <p
            role="alert"
            {...props}
            className={cn('text-sm text-red-700 dark:text-red-400', className)}
        >
            {message}
        </p>
    ) : null;
}
