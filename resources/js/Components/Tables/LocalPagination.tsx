interface LocalPaginationProps {
    page: number;
    pageCount: number;
    onPageChange: (page: number) => void;
    total: number;
    perPage: number;
}

/**
 * Same Bootstrap `.pagination` markup as Pagination.tsx, but paging a plain
 * client-side array instead of a Laravel paginator — for lists that are
 * entirely in-memory (e.g. the payment form's fee-line cards, where every
 * line is a live input feeding one submission, not a fetched page).
 * Switching pages never touches the underlying data.
 */
export default function LocalPagination({ page, pageCount, onPageChange, total, perPage }: LocalPaginationProps) {
    if (pageCount <= 1) {
        return null;
    }

    const from = (page - 1) * perPage + 1;
    const to = Math.min(page * perPage, total);

    return (
        <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 py-2">
            <p className="text-muted mb-0">
                Affichage de {from} à {to} sur {total} résultats
            </p>
            <nav aria-label="Pagination">
                <ul className="pagination mb-0">
                    <li className={`page-item${page === 1 ? ' disabled' : ''}`} aria-disabled={page === 1 ? true : undefined}>
                        <button
                            type="button"
                            className="page-link border-0"
                            onClick={() => onPageChange(page - 1)}
                            disabled={page === 1}
                            aria-label="Précédent"
                        >
                            ‹
                        </button>
                    </li>
                    {Array.from({ length: pageCount }, (_, i) => i + 1).map((n) => (
                        <li className={`page-item${n === page ? ' active' : ''}`} aria-current={n === page ? 'page' : undefined} key={n}>
                            <button type="button" className="page-link border-0" onClick={() => onPageChange(n)}>
                                {n}
                            </button>
                        </li>
                    ))}
                    <li
                        className={`page-item${page === pageCount ? ' disabled' : ''}`}
                        aria-disabled={page === pageCount ? true : undefined}
                    >
                        <button
                            type="button"
                            className="page-link border-0"
                            onClick={() => onPageChange(page + 1)}
                            disabled={page === pageCount}
                            aria-label="Suivant"
                        >
                            ›
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    );
}
