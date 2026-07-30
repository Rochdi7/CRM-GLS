import type { PropsWithChildren, ReactNode } from 'react';

interface TableToolbarProps extends PropsWithChildren {
    search?: ReactNode;
    actions?: ReactNode;
}

/**
 * Labeled filter row above a table — React port of
 * components/backoffice/ui/filter-bar.blade.php. Every direct child is one
 * labeled filter control; `search` and `actions` are optional slots
 * rendered at the row's end (actions right-aligned on desktop).
 */
export default function TableToolbar({ search, actions, children }: TableToolbarProps) {
    return (
        <div className="d-flex align-items-end flex-wrap gap-3 mb-3 gls-filter-bar">
            {children}
            {search && <div style={{ width: 260, maxWidth: '100%' }}>{search}</div>}
            {actions && <div className="ms-lg-auto">{actions}</div>}
        </div>
    );
}
