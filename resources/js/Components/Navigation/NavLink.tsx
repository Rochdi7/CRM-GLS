import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

interface NavLinkProps extends PropsWithChildren {
    href: string;
    /** True only for routes confirmed to return an Inertia response — everything else is a plain anchor (migration plan §"Routing"). */
    inertia?: boolean;
    className?: string;
    onClick?: () => void;
}

/**
 * Renders an Inertia <Link> for confirmed Inertia destinations, a plain
 * anchor for everything else (still-Livewire/Blade routes). Never attach
 * Inertia interception globally — only this explicit, per-item opt-in.
 */
export default function NavLink({ href, inertia = false, className, onClick, children }: NavLinkProps) {
    if (inertia) {
        return (
            <Link href={href} className={className} onClick={onClick}>
                {children}
            </Link>
        );
    }

    return (
        <a href={href} className={className} onClick={onClick}>
            {children}
        </a>
    );
}
