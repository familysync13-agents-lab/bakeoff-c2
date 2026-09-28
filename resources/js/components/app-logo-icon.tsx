import type { SVGAttributes } from 'react';

/** Brand mark: a small stack of books on a shelf. */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
            focusable="false"
        >
            <rect x="3" y="4" width="4" height="15" rx="1" />
            <rect x="8.5" y="6.5" width="4" height="12.5" rx="1" />
            <rect
                x="14"
                y="5.5"
                width="4"
                height="13.5"
                rx="1"
                transform="rotate(14 16 19)"
            />
            <rect x="2" y="20" width="20" height="1.75" rx="0.875" />
        </svg>
    );
}
