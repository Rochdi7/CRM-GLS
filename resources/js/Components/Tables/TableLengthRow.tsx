import type { ReactNode } from 'react';

interface TableLengthRowProps {
    /** Right side of the row — normally the page's <SearchInput/>. */
    search?: ReactNode;
}

/**
 * The PreSkool row that sits between the card header and the table.
 * It now holds only the search field — the "Lignes par page" selector was
 * removed (27/08/2026): every list page paginates at a fixed 10 rows
 * server-side, so there is nothing left to choose. Use inside a
 * `Card bodyClassName="p-0 py-3"` — the row carries its own horizontal
 * padding so the table below can run edge-to-edge.
 */
export default function TableLengthRow({ search }: TableLengthRowProps) {
    return (
        <div className="d-flex align-items-center justify-content-end flex-wrap gap-2 px-3 pb-2 gls-length-row">
            {search && <div style={{ width: 260 }}>{search}</div>}
        </div>
    );
}
