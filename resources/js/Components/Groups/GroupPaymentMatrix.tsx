import type { GroupPaymentCell, GroupPaymentMatrix, GroupPaymentSort } from '@/Types';

interface GroupPaymentMatrixProps {
    matrix: GroupPaymentMatrix | null;
    loading: boolean;
    sort: GroupPaymentSort;
    onSortChange: (sort: GroupPaymentSort) => void;
}

const SORT_OPTIONS: Array<{ value: GroupPaymentSort; label: string }> = [
    { value: 'date', label: 'Trier par date' },
    { value: 'nom', label: 'Trier par nom (A → Z)' },
    { value: 'nom_desc', label: 'Trier par nom (Z → A)' },
];

/**
 * Cell background per state — the EXACT fills of the legacy CRM's
 * « Statistique de groupe » screen (whose cells carry them as inline
 * `background: rgb(...)`), so a cashier reading the two side by side sees
 * the same colours:
 *
 *   green   payé      rien ne reste dû
 *   orange  partiel   une partie est payée, un reste court
 *   red     impayé    le frais est affecté et 0 DH est payé (recouvrement)
 *   grey    absent    le frais n'est PAS sur l'inscription de cet étudiant
 *                     (jamais ajouté, ou retiré) — rien n'est dû, la cellule
 *                     reste vide
 */
const CELL_FILL: Record<GroupPaymentCell['state'], string> = {
    paye: 'rgb(132, 251, 164)',
    partiel: 'rgb(227, 166, 105)',
    impaye: 'rgb(246, 45, 81)',
};

/** Row (N° + name) background per inscription statut. */
const ROW_FILL: Record<string, string | undefined> = {
    Active: undefined,
    Changement: 'rgb(170, 170, 170)',
    Annulée: 'rgb(246, 45, 81)',
};

const ABSENT_FILL = 'rgb(170, 170, 170)';

/** Every cell of the legacy grid: 5px padding, centered, dark text. */
const CELL_STYLE: React.CSSProperties = {
    padding: '5px',
    textAlign: 'center',
    color: '#000',
};

/** A money cell adds bold on top of that. */
const MONEY_CELL_STYLE: React.CSSProperties = {
    ...CELL_STYLE,
    fontWeight: 'bold',
};

function money(value: string): string {
    return `${Math.round(Number(value))} DH`;
}

function cellTitle(cell: GroupPaymentCell): string | undefined {
    if (cell.state === 'paye') {
        return undefined;
    }

    return `Reste à payer ${money(cell.reste)}`;
}

/**
 * « Statistique de groupe » — one row per inscription of the group, one
 * column per fee assigned to the group (ordered by due date, earliest
 * first), one cell per inscription × fee holding what that student paid on
 * that line.
 *
 * Markup and styling mirror the legacy CRM screen one-for-one: a plain
 * Bootstrap `.table-bordered` grid, 5px cell padding, everything centered,
 * bold on the money cells, a `bg-light` header row, and no sticky columns.
 * Anything richer (gutters, cards, rounded corners, sticky headers) makes
 * it read as a set of coloured tiles rather than one sheet. The one
 * departure is the scroll box around the table — see the comment on it.
 */
export default function GroupPaymentMatrixTable({ matrix, loading, sort, onSortChange }: GroupPaymentMatrixProps) {
    return (
        <>
            <div className="row g-3 align-items-end mb-3">
                <div className="col-md-3">
                    <label className="form-label" htmlFor="matrix-sort">
                        Trier par
                    </label>
                    <select
                        id="matrix-sort"
                        className="form-select"
                        value={sort}
                        disabled={loading}
                        onChange={(event) => onSortChange(event.target.value as GroupPaymentSort)}
                    >
                        {SORT_OPTIONS.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            <MatrixBody matrix={matrix} loading={loading} />
        </>
    );
}

function MatrixBody({ matrix, loading }: { matrix: GroupPaymentMatrix | null; loading: boolean }) {
    if (loading) {
        return (
            <div className="py-5 text-center">
                <span className="spinner-border text-primary" role="status" />
                <p className="text-muted mt-3 mb-0">Chargement des paiements…</p>
            </div>
        );
    }

    if (!matrix) {
        return null;
    }

    if (matrix.rows.length === 0) {
        return (
            <div className="py-5 text-center">
                <i className="ti ti-cash-off fs-32 text-muted" />
                <p className="text-muted mt-2 mb-0">Aucune inscription dans ce groupe.</p>
            </div>
        );
    }

    if (matrix.columns.length === 0) {
        return (
            <div className="py-5 text-center">
                <i className="ti ti-briefcase-off fs-32 text-muted" />
                <p className="text-muted mt-2 mb-0">Aucun frais assigné à ce groupe.</p>
            </div>
        );
    }

    return (
        <>
            {/* Caps the grid at a readable height instead of letting a
                45-student group stretch the dialog past the viewport: the box
                scrolls in BOTH directions (down through the students, sideways
                through a year of fee columns) while the modal itself stays a
                centred dialog. */}
            <div className="table-responsive" style={{ maxHeight: '75vh' }}>
                <table className="table table-bordered w-100 mb-0 gls-payment-matrix">
                    <thead>
                        <tr className="bg-light">
                            <th className="h6 border-top-0" style={CELL_STYLE}>
                                N°
                            </th>
                            <th className="h6 border-top-0" style={CELL_STYLE}>
                                Étudiant
                            </th>
                            {matrix.columns.map((column) => (
                                <th
                                    key={column.key}
                                    className="h6 border-top-0"
                                    style={CELL_STYLE}
                                    title={column.dateEcheance ? `Échéance ${column.dateEcheance}` : undefined}
                                >
                                    {column.nom}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {matrix.rows.map((row) => {
                            const rowFill = ROW_FILL[row.statut];

                            return (
                                <tr key={row.key}>
                                    <td style={{ ...CELL_STYLE, background: rowFill }}>{row.numero}</td>
                                    <td
                                        style={{ ...CELL_STYLE, background: rowFill }}
                                        title={`${row.reference} — ${row.statut}`}
                                    >
                                        {row.studentShowUrl ? (
                                            <a href={row.studentShowUrl} className="text-reset">
                                                {row.student ?? '—'}
                                            </a>
                                        ) : (
                                            (row.student ?? '—')
                                        )}
                                    </td>
                                    {matrix.columns.map((column) => {
                                        const cell = row.cells[column.key];

                                        // No cell at all = the fee is not on
                                        // this student's inscription: grey and
                                        // empty, never a 0 DH debt.
                                        if (!cell) {
                                            return (
                                                <td
                                                    key={column.key}
                                                    style={{ ...MONEY_CELL_STYLE, background: ABSENT_FILL }}
                                                    title="Frais non affecté à cet étudiant"
                                                />
                                            );
                                        }

                                        return (
                                            <td
                                                key={column.key}
                                                style={{ ...MONEY_CELL_STYLE, background: CELL_FILL[cell.state] }}
                                                title={cellTitle(cell)}
                                            >
                                                {money(cell.montant)}
                                            </td>
                                        );
                                    })}
                                </tr>
                            );
                        })}
                        <tr className="bg-light">
                            <td style={CELL_STYLE} />
                            <td style={MONEY_CELL_STYLE}>Total</td>
                            {matrix.columns.map((column) => (
                                <td key={column.key} style={MONEY_CELL_STYLE}>
                                    {money(column.total)}
                                </td>
                            ))}
                        </tr>
                    </tbody>
                </table>
            </div>
        </>
    );
}
