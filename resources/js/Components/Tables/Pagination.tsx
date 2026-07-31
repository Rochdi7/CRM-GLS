import { router } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import type { PaginatedData } from '@/Types';
import { t } from '@/Lib/i18n';

interface PaginationProps<T> {
    paginator: PaginatedData<T>;
    /** Extra query params to preserve (filters) when clicking a page link. */
    preserveScroll?: boolean;
    /** Shows a "go to page" number input next to Previous/Next — worth it once there are enough pages that clicking through is slow. */
    showJumpToPage?: boolean;
}

const LABEL_MAP: Record<string, string> = {
    '&laquo; Previous': '‹',
    'Previous': '‹',
    'Next &raquo;': '›',
    'Next': '›',
};

function safeLabel(label: string): string {
    return LABEL_MAP[label] ?? label.replace(/&laquo;|&raquo;/g, '').trim();
}

/**
 * Markup matches components/backoffice/ui/pagination.blade.php +
 * vendor/pagination/backoffice-links.blade.php exactly (Bootstrap 5
 * `.pagination`, "Showing X to Y of Z results" summary). Navigates via
 * Inertia router.get (not raw <a href>) so filters already in the URL are
 * preserved and the page never fully reloads.
 */
export default function Pagination<T>({ paginator, preserveScroll = true, showJumpToPage = false }: PaginationProps<T>) {
    const [jumpValue, setJumpValue] = useState(String(paginator.current_page));

    useEffect(() => {
        setJumpValue(String(paginator.current_page));
    }, [paginator.current_page]);

    if (paginator.last_page <= 1) {
        return null;
    }

    function visit(url: string | null) {
        if (!url) {
            return;
        }

        router.get(
            url,
            {},
            {
                preserveScroll,
                preserveState: true,
                replace: true,
            },
        );
    }

    function jumpToPage(event: FormEvent) {
        event.preventDefault();

        const page = Number(jumpValue);
        if (!Number.isInteger(page) || page < 1 || page > paginator.last_page || page === paginator.current_page) {
            setJumpValue(String(paginator.current_page));
            return;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('page', String(page));
        visit(url.pathname + url.search);
    }

    return (
        <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 p-3">
            <p className="text-muted mb-0">
                Affichage de {paginator.from ?? 0} à {paginator.to ?? 0} sur {paginator.total} résultats
            </p>
            <nav aria-label={t('Pagination')}>
                <ul className="pagination mb-0">
                    {paginator.links.map((link, index) => {
                        const isEllipsis = link.label.includes('...') && !link.url;
                        const isPrevNext = index === 0 || index === paginator.links.length - 1;

                        if (isEllipsis) {
                            return (
                                <li className="page-item disabled" aria-disabled="true" key={`ellipsis-${index}`}>
                                    <span className="page-link">…</span>
                                </li>
                            );
                        }

                        return (
                            <li
                                className={`page-item${link.active ? ' active' : ''}${!link.url ? ' disabled' : ''}`}
                                aria-current={link.active ? 'page' : undefined}
                                aria-disabled={!link.url ? true : undefined}
                                key={`${link.label}-${index}`}
                            >
                                {link.url && !link.active ? (
                                    <button
                                        type="button"
                                        className="page-link border-0"
                                        onClick={() => visit(link.url)}
                                        aria-label={isPrevNext ? safeLabel(link.label) : undefined}
                                    >
                                        {isPrevNext ? safeLabel(link.label) : link.label}
                                    </button>
                                ) : (
                                    <span className="page-link" aria-hidden={!link.active ? true : undefined}>
                                        {isPrevNext ? safeLabel(link.label) : link.label}
                                    </span>
                                )}
                            </li>
                        );
                    })}
                </ul>
            </nav>
            {showJumpToPage && paginator.last_page > 5 && (
                <form className="d-flex align-items-center gap-2" onSubmit={jumpToPage}>
                    <label className="text-muted mb-0" htmlFor="pagination-jump-to-page">
                        {t('Go to page')}
                    </label>
                    <input
                        id="pagination-jump-to-page"
                        type="number"
                        min={1}
                        max={paginator.last_page}
                        className="form-control form-control-sm"
                        style={{ width: 70 }}
                        value={jumpValue}
                        onChange={(event) => setJumpValue(event.target.value)}
                        onBlur={jumpToPage}
                    />
                    <span className="text-muted">/ {paginator.last_page}</span>
                </form>
            )}
        </div>
    );
}
