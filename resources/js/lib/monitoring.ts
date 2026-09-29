import * as Sentry from '@sentry/react';

type MonitoringConfig = {
    dsn: string | null;
    release: string | null;
    environment: string | null;
};

/**
 * Reads the public monitoring settings rendered by the server into app.blade.php.
 */
export function readMonitoringConfig(
    doc: Document = document,
): MonitoringConfig | null {
    const element = doc.getElementById('monitoring-config');

    if (!element?.textContent) {
        return null;
    }

    try {
        return JSON.parse(element.textContent) as MonitoringConfig;
    } catch {
        return null;
    }
}

/**
 * Initialises the Sentry browser SDK when PUBLIC_SENTRY_DSN is set; a no-op otherwise.
 */
export function initMonitoring(doc: Document = document): void {
    const config = readMonitoringConfig(doc);

    if (!config?.dsn) {
        return;
    }

    Sentry.init({
        dsn: config.dsn,
        release: config.release ?? undefined,
        environment: config.environment ?? undefined,
    });
}
