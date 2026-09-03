import { useCallback, useRef } from 'react';
import type {
    GroupPaymentCell,
    GroupPaymentColumn,
    GroupPaymentMatrix,
    GroupPaymentRow,
    GroupPaymentSort,
} from '@/Types';

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

function echeanceLine(column: GroupPaymentColumn): string | null {
    return column.dateEcheance ? `Échéance ${column.dateEcheance}` : null;
}

/**
 * What a tooltip shows: an optional heading (drawn over a rule) and the body
 * lines under it. A money cell has no heading — only the row tooltips, which
 * mirror the legacy CRM's « Annulé » / « Archivé » block, use one.
 */
interface MatrixTip {
    titre?: string;
    lines: string[];
}

/** Hover tooltip of a money cell (null = nothing to show). */
function cellTip(cell: GroupPaymentCell, column: GroupPaymentColumn): MatrixTip | null {
    const lines: string[] = [];

    if (cell.state !== 'paye') {
        lines.push(`Reste à payer ${money(cell.reste)}`);
    }

    const echeance = echeanceLine(column);
    if (echeance) {
        lines.push(echeance);
    }

    return lines.length > 0 ? { lines } : null;
}

/**
 * Heading of a row tooltip, and the verb its first line reads with — the
 * legacy CRM said « Annulé » / « Archivé » above the block rather than
 * repeating the statut in a sentence, and a cashier reading the two screens
 * side by side expects the same word.
 */
const STATUT_TITRE: Record<string, { titre: string; verbe: string }> = {
    Annulée: { titre: 'Annulé', verbe: 'Annulé' },
    Changement: { titre: 'Archivé', verbe: 'Archivé' },
    Archivée: { titre: 'Archivé', verbe: 'Archivé' },
    Expirée: { titre: 'Expiré', verbe: 'Expiré' },
};

/**
 * The hover tooltip of a student row, in the legacy CRM's own shape: a
 * heading (« Annulé » / « Archivé ») over a rule, then the date and the
 * reason as ONE sentence — « Annulé le : 22/07/2026 pour la raison :
 * Non-paiement » — and the note underneath when there is one.
 *
 * Only a row that actually ended gets it. An Active row keeps the plain
 * reference + statut line: there is nothing to explain, and a heading over
 * it would imply otherwise.
 */
function rowTip(row: GroupPaymentRow): MatrixTip {
    const entete = STATUT_TITRE[row.statut];

    if (!entete) {
        return { lines: [`${row.reference} — ${row.statut}`] };
    }

    const lines: string[] = [];

    // Date and reason read as one sentence, wrapped across two lines exactly
    // as the legacy screen wrapped it — never as two labelled fields, which
    // is what made the first version read as a debug dump.
    if (row.dateFin) {
        lines.push(`${entete.verbe} le : ${row.dateFin}`);
    }

    if (row.motifAnnulation) {
        lines.push(`pour la raison : ${row.motifAnnulation}`);
    }

    if (row.note) {
        lines.push(`Note : ${row.note}`);
    }

    // A legacy row may carry neither a date nor a reason (the old CRM never
    // exported them). The heading alone would then be a bare word, so the
    // reference stands in as the body rather than showing an empty box.
    if (lines.length === 0) {
        lines.push(row.reference);
    }

    return { titre: entete.titre, lines };
}

/**
 * Hover tooltip owned by the component instead of the native `title`
 * attribute. The browser tooltip only shows after a ~1s pause, dies on the
 * slightest mouse move, and inside a scrolling box in a modal it often
 * never appears at all — which is what read as « bugged ».
 *
 * It is driven IMPERATIVELY through a ref, not React state: a state update
 * on hover would re-render the whole grid (45 students × a year of fees) on
 * every cell change and feel sluggish. Here mouseenter just writes text +
 * position into one fixed `<div>`, anchored under the hovered cell (not the
 * cursor, so there is nothing to track on mousemove).
 */
function useMatrixTooltip() {
    const ref = useRef<HTMLDivElement>(null);

    const hide = useCallback(() => {
        const el = ref.current;
        if (el) {
            el.style.display = 'none';
        }
    }, []);

    const showFor = useCallback((tip: MatrixTip, target: HTMLElement) => {
        const el = ref.current;
        if (!el) {
            return;
        }

        el.textContent = '';

        if (tip.titre) {
            const titre = document.createElement('div');
            titre.className = 'gls-matrix-tooltip__title';
            titre.textContent = tip.titre;
            el.appendChild(titre);
        }

        for (const line of tip.lines) {
            const div = document.createElement('div');
            div.textContent = line;
            el.appendChild(div);
        }

        // The arrow is part of the box, so it has to be re-appended after the
        // content is rebuilt — and flipped when the box moves above the cell.
        const arrow = document.createElement('span');
        arrow.className = 'gls-matrix-tooltip__arrow';
        el.appendChild(arrow);

        const rect = target.getBoundingClientRect();
        el.style.display = 'block';

        const width = el.offsetWidth;
        const height = el.offsetHeight;
        const centre = rect.left + rect.width / 2;
        let left = centre - width / 2;
        left = Math.max(8, Math.min(left, window.innerWidth - width - 8));

        let top = rect.bottom + 10;
        let dessus = false;
        if (top + height > window.innerHeight - 8) {
            top = rect.top - height - 10;
            dessus = true;
        }

        el.style.left = `${left}px`;
        el.style.top = `${top}px`;

        // Point at the hovered cell even when the box was pushed sideways to
        // stay on screen: the arrow tracks the cell's centre, not the box's.
        arrow.classList.toggle('gls-matrix-tooltip__arrow--up', !dessus);
        arrow.style.left = `${Math.max(10, Math.min(centre - left, width - 10))}px`;
    }, []);

    const bind = (tip: MatrixTip | null) =>
        tip
            ? {
                  onMouseEnter: (event: React.MouseEvent<HTMLElement>) => showFor(tip, event.currentTarget),
                  onMouseLeave: hide,
              }
            : { onMouseEnter: hide };

    return { ref, bind, hide };
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
    const { ref: tipRef, bind, hide } = useMatrixTooltip();

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
            <div className="table-responsive" style={{ maxHeight: '75vh' }} onScroll={hide} onMouseLeave={hide}>
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
                                    {...bind(column.dateEcheance ? { lines: [echeanceLine(column) as string] } : null)}
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
                                    {/* The N° cell carries the same tooltip as
                                        the name: both halves are one coloured
                                        block to the eye, so hovering either
                                        must explain it. */}
                                    <td style={{ ...CELL_STYLE, background: rowFill }} {...bind(rowTip(row))}>
                                        {row.numero}
                                    </td>
                                    <td
                                        style={{ ...CELL_STYLE, background: rowFill }}
                                        {...bind(rowTip(row))}
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
                                                    {...bind({ lines: ['Frais non affecté à cet étudiant'] })}
                                                />
                                            );
                                        }

                                        return (
                                            <td
                                                key={column.key}
                                                style={{ ...MONEY_CELL_STYLE, background: CELL_FILL[cell.state] }}
                                                {...bind(cellTip(cell, column))}
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
            <div ref={tipRef} className="gls-matrix-tooltip" role="tooltip" style={{ display: 'none' }} />
        </>
    );
}
