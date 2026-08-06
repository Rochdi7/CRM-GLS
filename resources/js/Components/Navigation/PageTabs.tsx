import { Link, usePage } from '@inertiajs/react';
import type { SharedProps } from '@/Types';

export interface PageTabDef {
    label: string;
    href: string;
    /** Tabler icon class, e.g. "ti ti-cash-banknote". */
    icon: string;
    /** ANY-of permission gate — omit for always-visible. */
    permissions?: string[];
    /** Paths that mark this tab active (prefix match); defaults to [href]. */
    matchPaths?: string[];
}

/**
 * Page-level module tabs (wimschool-inspired, rendered with the theme's
 * bordered nav-tabs — the PreSkool "file folder" style where the active
 * tab fuses with the white panel below). Each tab is a real Inertia Link
 * to an EXISTING route; visibility is UI-only (ANY-of permissions from
 * the shared auth props — the server keeps enforcing everything). Used to
 * group related modules (Finance, Groupes/Historique) as tabs instead of
 * separate sidebar entries.
 */
export default function PageTabs({ tabs }: { tabs: PageTabDef[] }) {
    const { auth } = usePage<SharedProps>().props;
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

    const visible = tabs.filter(
        (tab) =>
            !tab.permissions ||
            tab.permissions.length === 0 ||
            auth.isSuperAdmin ||
            tab.permissions.some((p) => auth.permissions.includes(p)),
    );

    if (visible.length < 2) {
        return null;
    }

    return (
        <ul className="nav nav-tabs mb-4" role="tablist">
            {visible.map((tab) => {
                const active = (tab.matchPaths ?? [tab.href]).some(
                    (path) => currentPath === path || currentPath.startsWith(`${path}/`),
                );

                return (
                    <li className="nav-item" key={tab.href} role="presentation">
                        <Link
                            href={tab.href}
                            className={`nav-link d-inline-flex align-items-center${active ? ' active' : ''}`}
                            aria-current={active ? 'page' : undefined}
                        >
                            <i className={`${tab.icon} me-2`} aria-hidden="true" />
                            {tab.label}
                        </Link>
                    </li>
                );
            })}
        </ul>
    );
}
