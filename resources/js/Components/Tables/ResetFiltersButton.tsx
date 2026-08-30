import { t } from '@/Lib/i18n';

interface ResetFiltersButtonProps {
    /** Clears every filter the page owns and reloads the list. */
    onReset: () => void;
    /**
     * Whether any filter currently deviates from the page's defaults. The
     * button stays visible either way (its position must not jump) but is
     * disabled when there is nothing to clear.
     */
    active?: boolean;
}

/**
 * The one way a list page's filters are cleared — an EXPLICIT icon button
 * the user presses (30/08/2026).
 *
 * Filters are never reset as a side effect of something else: applying an
 * avance used to drop the cashier back onto an unfiltered list, so a filter
 * set up once had to be retyped after every single application. Mutations
 * now redirect back preserving the query string
 * (`Concerns\RedirectsPreservingFilters`), and clearing is this button's
 * job alone.
 *
 * Use through `TableToolbar`'s `onReset` prop so it lands in the same spot
 * on every page.
 */
export default function ResetFiltersButton({ onReset, active = true }: ResetFiltersButtonProps) {
    const label = t('Clear filters');

    return (
        <button
            type="button"
            className="btn btn-outline-light border d-inline-flex align-items-center gap-1"
            onClick={onReset}
            disabled={!active}
            title={label}
            aria-label={label}
        >
            <i className="ti ti-filter-off fs-16" aria-hidden="true" />
            <span className="d-none d-xl-inline">{label}</span>
        </button>
    );
}
