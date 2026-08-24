import { t } from '@/Lib/i18n';
import useMediaQuery from '@/Hooks/useMediaQuery';
import { pageWindow } from '@/Components/Tables/Pagination';

interface LocalPaginationProps {
    page: number;
    pageCount: number;
    onPageChange: (page: number) => void;
    total: number;
    /** Retained for callers' existing prop shape; the footer now reports the total only. */
    perPage?: number;
}

/**
 * Same Bootstrap `.pagination` markup as Pagination.tsx, but paging a plain
 * client-side array instead of a Laravel paginator — for lists that are
 * entirely in-memory (e.g. the payment form's fee-line cards, where every
 * line is a live input feeding one submission, not a fetched page).
 * Switching pages never touches the underlying data.
 */
export default function LocalPagination({ page, pageCount, onPageChange, total }: LocalPaginationProps) {
    const isPhone = useMediaQuery('(max-width: 575.98px)');
    const singlePage = pageCount <= 1;
    const pages = pageWindow(page, pageCount, isPhone ? 1 : 2);

    return (
        <div className="gls-pagination-footer d-flex align-items-center justify-content-between flex-wrap gap-2 py-2">
            <p className="text-muted mb-0">
                {new Intl.NumberFormat('fr-FR').format(total)} {t('total')}
            </p>
            {!singlePage && (
            <nav aria-label="Pagination">
                <ul className="pagination mb-0 flex-nowrap">
                    <li className={`page-item${page === 1 ? ' disabled' : ''}`} aria-disabled={page === 1 ? true : undefined}>
                        <button
                            type="button"
                            className="page-link border-0"
                            onClick={() => onPageChange(page - 1)}
                            disabled={page === 1}
                            aria-label="Précédent"
                        >
                            <i className="ti ti-chevron-left" aria-hidden="true" />
                        </button>
                    </li>
                    {pages.map((n, i) =>
                        n === null ? (
                            <li className="page-item disabled" aria-disabled="true" key={`gap-${i}`}>
                                <span className="page-link">…</span>
                            </li>
                        ) : (
                            <li className={`page-item${n === page ? ' active' : ''}`} aria-current={n === page ? 'page' : undefined} key={n}>
                                <button type="button" className="page-link border-0" onClick={() => onPageChange(n)}>
                                    {n}
                                </button>
                            </li>
                        ),
                    )}
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
                            <i className="ti ti-chevron-right" aria-hidden="true" />
                        </button>
                    </li>
                </ul>
            </nav>
            )}
        </div>
    );
}
