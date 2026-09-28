import * as Sentry from '@sentry/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { initMonitoring, readMonitoringConfig } from './monitoring';

vi.mock('@sentry/react', () => ({ init: vi.fn() }));

function fakeDocument(json: string | null): Document {
    return {
        getElementById: (id: string) =>
            id === 'monitoring-config' && json !== null
                ? { textContent: json }
                : null,
    } as unknown as Document;
}

describe('browser monitoring', () => {
    beforeEach(() => {
        vi.mocked(Sentry.init).mockClear();
    });

    it('initialises Sentry with the DSN, release and environment rendered by the server', () => {
        initMonitoring(
            fakeDocument(
                '{"dsn":"http://public@ingest:9000/1","release":"abc123","environment":"preview"}',
            ),
        );

        expect(Sentry.init).toHaveBeenCalledWith({
            dsn: 'http://public@ingest:9000/1',
            release: 'abc123',
            environment: 'preview',
        });
    });

    it('does nothing without a DSN', () => {
        initMonitoring(
            fakeDocument(
                '{"dsn":null,"release":"abc123","environment":"preview"}',
            ),
        );

        expect(Sentry.init).not.toHaveBeenCalled();
    });

    it('tolerates a missing or malformed config element', () => {
        expect(readMonitoringConfig(fakeDocument(null))).toBeNull();
        expect(readMonitoringConfig(fakeDocument('{not json'))).toBeNull();
    });
});
