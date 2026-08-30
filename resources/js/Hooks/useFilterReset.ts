import { useMemo } from 'react';

/**
 * Wires a list page's "Réinitialiser les filtres" button.
 *
 * Every backoffice list page holds its filter state in ONE server-echoed
 * `filters` prop and reloads through its own `reload(partial)` (an Inertia
 * partial visit, CLAUDE.md §5). Resetting is therefore the same operation
 * everywhere: send every filter key back at its DEFAULT value.
 *
 * "Default" is not always empty — a page may open on a date window, a tab
 * or a sort direction it must keep (the Encaissements tabs, the Avances
 * « Disponibles » balance filter). So the caller passes `defaults` for the
 * keys that have one; every other key resets to an empty string.
 *
 * `active` tells the button whether anything actually deviates, so a page
 * with untouched filters shows it disabled rather than offering a no-op.
 *
 * @param filters  the page's current filters prop (server-echoed)
 * @param reload   the page's own reload(partialFilters) function
 * @param defaults values the reset must restore instead of '' (optional)
 */
export function useFilterReset<F extends object>(
    filters: F,
    reload: (next: Partial<F>) => void,
    defaults: Partial<F> = {},
) {
    const cleared = useMemo(() => {
        const next: Record<string, unknown> = {};

        for (const key of Object.keys(filters)) {
            next[key] = key in defaults ? defaults[key as keyof F] : '';
        }

        return next as Partial<F>;
    }, [filters, defaults]);

    // A filter counts as "set" when it differs from what a reset would
    // restore. Loose-ish comparison on strings only: every filter travels
    // through the query string, so null/undefined and '' are the same
    // "not set" to the server and must not light the button up.
    const active = useMemo(
        () =>
            Object.keys(filters).some((key) => {
                const current = (filters as Record<string, unknown>)[key];
                const target = cleared[key as keyof F];
                const norm = (v: unknown) => (v === null || v === undefined ? '' : String(v));

                return norm(current) !== norm(target);
            }),
        [filters, cleared],
    );

    return {
        active,
        reset: () => reload(cleared),
    };
}

export default useFilterReset;
