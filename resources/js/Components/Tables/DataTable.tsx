import type { PropsWithChildren, ReactNode } from 'react';

interface DataTableProps extends PropsWithChildren {
    head?: ReactNode;
    hover?: boolean;
    /** Dims the table and shows a corner spinner while an Inertia request (search/filter/sort/page) is in flight — pass useInertiaLoading(). */
    loading?: boolean;
}

/**
 * Markup matches components/backoffice/ui/table.blade.php exactly — visual
 * reference only (docs/react-theme-file-map.md §3): responsive wrapper +
 * static markup, no client-side sort/filter/pagination logic. Server-side
 * pagination stays the rule (CLAUDE.md § DataTables) once a real paginated
 * module is converted.
 */
export default function DataTable({ head, hover = true, loading = false, children }: DataTableProps) {
    return (
        <div className="position-relative">
            {loading && (
                <div
                    className="position-absolute top-0 end-0 p-2"
                    style={{ zIndex: 1 }}
                    role="status"
                    aria-live="polite"
                >
                    <span className="spinner-border spinner-border-sm text-primary" aria-hidden="true" />
                </div>
            )}
            <div className={`table-responsive${loading ? ' opacity-50' : ''}`} style={{ transition: 'opacity 0.15s ease' }}>
                <table className={`table${hover ? ' table-hover' : ''}`}>
                    {head && <thead className="thead-light">{head}</thead>}
                    <tbody>{children}</tbody>
                </table>
            </div>
        </div>
    );
}
