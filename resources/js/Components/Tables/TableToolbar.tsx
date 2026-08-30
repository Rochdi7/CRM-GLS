import type { PropsWithChildren, ReactNode } from 'react';

import ResetFiltersButton from './ResetFiltersButton';

interface TableToolbarProps extends PropsWithChildren {
    search?: ReactNode;
    actions?: ReactNode;
    /**
     * Clears the page's filters. Passing it renders the shared
     * "Réinitialiser" button as the last control of the filter row — the
     * ONLY way filters are cleared on a list page (see
     * ResetFiltersButton's docblock: nothing resets them implicitly).
     */
    onReset?: () => void;
    /** False when the filters already sit at their defaults — button disabled. */
    resetActive?: boolean;
}

/**
 * Labeled filter row above a table — React port of
 * components/backoffice/ui/filter-bar.blade.php. Every direct child is one
 * labeled filter control; `search`, the reset button and `actions` are
 * optional slots rendered at the row's end (actions right-aligned on
 * desktop).
 */
export default function TableToolbar({ search, actions, onReset, resetActive, children }: TableToolbarProps) {
    return (
        <div className="d-flex align-items-end flex-wrap gap-3 mb-3 gls-filter-bar">
            {children}
            {search && <div style={{ width: 260, maxWidth: '100%' }}>{search}</div>}
            {onReset && <ResetFiltersButton onReset={onReset} active={resetActive} />}
            {actions && <div className="ms-lg-auto">{actions}</div>}
        </div>
    );
}
