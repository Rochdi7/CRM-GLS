import { router } from '@inertiajs/react';
import type { PaginatedData } from '@/Types';
import { t } from '@/Lib/i18n';
import useMediaQuery from '@/Hooks/useMediaQuery';

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

/** Thousands separated by a narrow no-break space, French-style: 3 933. */
function formatTotal(total: number): string {
    return new Intl.NumberFormat('fr-FR').format(total);
}

/**
 * Builds the page-number window around the current page: always first + last,
 * `sides` neighbours on each side of the current page, `null` for a gap.
 * On phones `sides` = 1 so the pager never overflows the card
 * (< 1 … 4 5 6 … 12 >); desktop keeps a wider window.
 */
export function pageWindow(current: number, last: number, sides: number): Array<number | null> {
    if (last <= 2 * sides + 5) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }

    const start = Math.max(2, Math.min(current - sides, last - 2 * sides - 2));
    const end = Math.min(last - 1, Math.max(current + sides, 2 * sides + 3));
    const pages: Array<number | null> = [1];

    if (start > 2) {
        pages.push(null);
    }
    for (let p = start; p <= end; p++) {
        pages.push(p);
    }
    if (end < last - 1) {
        pages.push(null);
    }
    pages.push(last);

    return pages;
}

/** Any link URL of the paginator, with its `page` query param replaced. */
function urlForPage(paginator: PaginatedData<unknown>, page: number): string | null {
    const sample = paginator.links.find((l) => l.url)?.url;
    if (!sample) {
        return null;
    }
    const url = new URL(sample, window.location.origin);
    url.searchParams.set('page', String(page));
    return url.pathname + url.search;
}

/**
 * Bootstrap 5 `.pagination` markup (PreSkool look). The footer reports the
 * count of the WHOLE filtered result set ("3 933 total" — no from–to range),
 * and stays visible on a single-page list — the pager itself hides there.
 * Page numbers are windowed client-side from current_page/last_page (Laravel's
 * own `links` array puts up to 7 numbers side by side, which overflowed the
 * card on phones). Navigates via Inertia router.get (not raw <a href>) so
 * filters already in the URL are preserved and the page never fully reloads.
 */
export default function Pagination<T>({ paginator, preserveScroll = true, only }: PaginationProps<T>) {
    const isPhone = useMediaQuery('(max-width: 575.98px)');

    function visit(page: number) {
        const url = urlForPage(paginator, page);
        if (!url || page < 1 || page > paginator.last_page || page === paginator.current_page) {
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

    const { current_page: current, last_page: last, total } = paginator;
    const singlePage = last <= 1;
    const pages = pageWindow(current, last, isPhone ? 1 : 2);

    const summary = `${formatTotal(total)} ${t('total')}`;

    return (
        <div className="gls-pagination-footer d-flex align-items-center justify-content-between flex-wrap gap-2 p-3">
            <p className="text-muted mb-0 fs-13">{summary}</p>
            {!singlePage && (
                <nav aria-label={t('Pagination')}>
                    <ul className="pagination mb-0 flex-nowrap">
                        <li className={`page-item${current <= 1 ? ' disabled' : ''}`}>
                            <button
                                type="button"
                                className="page-link border-0"
                                onClick={() => visit(current - 1)}
                                disabled={current <= 1}
                                aria-label={t('Previous')}
                                title={t('Previous')}
                            >
                                <i className="ti ti-chevron-left" aria-hidden="true" />
                            </button>
                        </li>

                        {pages.map((page, index) =>
                            page === null ? (
                                <li className="page-item disabled" aria-disabled="true" key={`gap-${index}`}>
                                    <span className="page-link">…</span>
                                </li>
                            ) : (
                                <li
                                    className={`page-item${page === current ? ' active' : ''}`}
                                    aria-current={page === current ? 'page' : undefined}
                                    key={page}
                                >
                                    {page === current ? (
                                        <span className="page-link">{page}</span>
                                    ) : (
                                        <button
                                            type="button"
                                            className="page-link border-0"
                                            onClick={() => visit(page)}
                                            aria-label={`${t('Page')} ${page}`}
                                        >
                                            {page}
                                        </button>
                                    )}
                                </li>
                            ),
                        )}

                        <li className={`page-item${current >= last ? ' disabled' : ''}`}>
                            <button
                                type="button"
                                className="page-link border-0"
                                onClick={() => visit(current + 1)}
                                disabled={current >= last}
                                aria-label={t('Next')}
                                title={t('Next')}
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
