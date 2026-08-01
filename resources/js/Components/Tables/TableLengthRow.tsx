import type { ReactNode } from 'react';
import { t } from '@/Lib/i18n';

interface TableLengthRowProps {
    /** Omit the three per-page props on pages whose backend has no perPage filter — the row then holds only the search. */
    perPage?: number;
    perPageOptions?: number[];
    onPerPageChange?: (perPage: number) => void;
    /** Right side of the row — normally the page's <SearchInput/>. */
    search?: ReactNode;
}

/**
 * The PreSkool row that sits between the card header and the table
 * ("Row Per Page [10] Entries" left + search right — DataTables-generated
 * in the theme, hand-rendered here; Phase 13 UI parity). Use inside a
 * `Card bodyClassName="p-0 py-3"` — the row carries its own horizontal
 * padding so the table below can run edge-to-edge.
 */
export default function TableLengthRow({ perPage, perPageOptions, onPerPageChange, search }: TableLengthRowProps) {
    const hasPerPage = perPage !== undefined && perPageOptions !== undefined && onPerPageChange !== undefined;

    return (
        <div
            className={`d-flex align-items-center ${hasPerPage ? 'justify-content-between' : 'justify-content-end'} flex-wrap gap-2 px-3 pb-3`}
        >
            {hasPerPage && (
                <div className="d-flex align-items-center">
                    <label className="text-muted mb-0" htmlFor="table-per-page">
                        {t('Row Per Page')}
                    </label>
                    <select
                        id="table-per-page"
                        className="form-select form-select-sm mx-2"
                        style={{ width: 70 }}
                        value={perPage}
                        onChange={(event) => onPerPageChange(Number(event.target.value))}
                    >
                        {perPageOptions.map((option) => (
                            <option key={option} value={option}>
                                {option}
                            </option>
                        ))}
                    </select>
                    <span className="text-muted">{t('Entries')}</span>
                </div>
            )}
            {search && <div style={{ width: 260 }}>{search}</div>}
        </div>
    );
}
