import { usePage } from '@inertiajs/react';

/**
 * Single source of truth for "is this nav item active" — every component
 * that needs active-state (sidebar item, expanded parent group) calls this
 * instead of re-deriving it from window.location itself.
 */
export function useCurrentPath(): string {
    return usePage().url.split('?')[0].split('#')[0];
}

export function isPathActive(currentPath: string, matchPaths: string[]): boolean {
    return matchPaths.some((path) => currentPath === path || currentPath.startsWith(`${path}/`));
}
