import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export const CLIENT_ERROR_MESSAGE =
    'Forced client error (GET /debug/client-error)';

/** Forced browser error for checking error monitoring: the thrown error is left unhandled for the Sentry browser SDK. */
export default function ClientError() {
    return (
        <>
            <Head title="Client error">
                <meta name="robots" content="noindex, nofollow" />
            </Head>
            <div className="flex max-w-xl flex-col items-start gap-4">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Client error
                </h1>
                <p className="text-muted-foreground">
                    Pressing the button throws an unhandled JavaScript error,
                    which is reported to the error-monitoring service.
                </p>
                <Button
                    type="button"
                    onClick={() => {
                        throw new Error(CLIENT_ERROR_MESSAGE);
                    }}
                >
                    Trigger client error
                </Button>
            </div>
        </>
    );
}
