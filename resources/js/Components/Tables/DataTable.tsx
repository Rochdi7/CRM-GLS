import type { PropsWithChildren, ReactNode } from 'react';

interface DataTableProps extends PropsWithChildren {
    head?: ReactNode;
    hover?: boolean;
}

/**
 * Markup matches components/backoffice/ui/table.blade.php exactly — visual
 * reference only (docs/react-theme-file-map.md §3): responsive wrapper +
 * static markup, no client-side sort/filter/pagination logic. Server-side
 * pagination stays the rule (CLAUDE.md § DataTables) once a real paginated
 * module is converted.
 */
export default function DataTable({ head, hover = true, children }: DataTableProps) {
    return (
        <div className="table-responsive">
            <table className={`table${hover ? ' table-hover' : ''}`}>
                {head && <thead className="thead-light">{head}</thead>}
                <tbody>{children}</tbody>
            </table>
        </div>
    );
}
