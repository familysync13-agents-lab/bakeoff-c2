import type { ReactElement, ReactNode } from 'react';
import { isValidElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vite-plus/test';
import ClientError, { CLIENT_ERROR_MESSAGE } from '@/pages/debug/client-error';

vi.mock('@inertiajs/react', () => ({
    Head: ({ children }: { children?: ReactNode }) => <>{children}</>,
}));

type ButtonProps = { onClick?: () => void; children?: ReactNode };

function findButton(node: ReactNode): ReactElement<ButtonProps> | null {
    if (!isValidElement<ButtonProps>(node)) {
        return null;
    }

    if (node.props.children === 'Trigger client error') {
        return node;
    }

    for (const child of [node.props.children].flat()) {
        const found = findButton(child);

        if (found) {
            return found;
        }
    }

    return null;
}

describe('/debug/client-error', () => {
    it('shows the "Trigger client error" button', () => {
        const html = renderToStaticMarkup(<ClientError />);

        expect(html).toContain('>Trigger client error</button>');
        expect(html).toContain('<h1');
    });

    it('throws an unhandled error when the button is pressed', () => {
        const button = findButton(ClientError());

        expect(button?.props.onClick).toBeTypeOf('function');
        expect(() => button?.props.onClick?.()).toThrow(CLIENT_ERROR_MESSAGE);
    });
});
