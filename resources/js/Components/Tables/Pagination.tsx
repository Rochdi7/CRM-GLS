import { router } from '@inertiajs/react';
import type { PaginatedData } from '@/Types';
import { t } from '@/Lib/i18n';

interface PaginationProps<T> {
    paginator: PaginatedData<T>;
    /** Extra query params to preserve (filters) when clicking a page link. */
    preserveScroll?: boolean;
    /**
     * Inertia partial-reload prop list. When a page's heavy props are
     * closures server-side (option catalogs, stats), passing e.g.
     * `['rows', 'filters']` makes a page change recompute only those.
     */
    only?: string[];
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

/** Thousands separated by a narrow no-break space, French-style: 3 933. */
function formatTotal(total: number): string {
    return new Intl.NumberFormat('fr-FR').format(total);
}

/**
 * Markup matches components/backoffice/ui/pagination.blade.php +
 * vendor/pagination/backoffice-links.blade.php exactly (Bootstrap 5
 * `.pagination`). The footer reports the row count of the WHOLE filtered
 * result set ("3 933 total"), not just the current page, and stays visible
 * on a single-page list — the pager itself hides there. Navigates via
 * Inertia router.get (not raw <a href>) so filters already in the URL are
 * preserved and the page never fully reloads.
 */
export default function Pagination<T>({ paginator, preserveScroll = true, only }: PaginationProps<T>) {
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
                ...(only ? { only } : {}),
            },
        );
    }

    const singlePage = paginator.last_page <= 1;

    return (
        <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 p-3">
            <p className="text-muted mb-0">
                {formatTotal(paginator.total)} {t('total')}
            </p>
            {!singlePage && (
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
            )}
        </div>
    );
}
