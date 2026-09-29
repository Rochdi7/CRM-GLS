import { useCallback, useRef, useState } from 'react';
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
    /**
     * Draws the « masquer ce frais » icon on each money cell. Clicking it
     * SELECTS the cell (multi-select), and one « Masquer la sélection »
     * button hides the whole batch. UI convenience only — each hide goes
     * through the endpoint, which re-checks `registrations.manage-fees`,
     * that the fee belongs to the inscription, and the active context
     * (InscriptionController@hideFee).
     */
    canHideFees?: boolean;
    /** Re-fetches the matrix after a fee was hidden, without the full-page spinner. */
    onFeeHidden?: () => Promise<void> | void;
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
    /** The fee line's note — drawn in amber with a pencil icon so it stands out from the figures. */
    note?: string;
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

    // The fee line's own note — the one field of the inscription modal a
    // cashier cannot otherwise see from the matrix.
    if (cell.note) {
        return { lines, note: cell.note };
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
        return { lines: [`${row.reference} - ${row.statut}`] };
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

        if (tip.note) {
            const note = document.createElement('div');
            note.className = 'gls-matrix-tooltip__note';
            const icon = document.createElement('i');
            icon.className = 'ti ti-pencil';
            icon.setAttribute('aria-hidden', 'true');
            note.appendChild(icon);
            note.appendChild(document.createTextNode(tip.note));
            el.appendChild(note);
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
export default function GroupPaymentMatrixTable({
    matrix,
    loading,
    sort,
    onSortChange,
    canHideFees = false,
    onFeeHidden,
}: GroupPaymentMatrixProps) {
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

            <MatrixBody matrix={matrix} loading={loading} canHideFees={canHideFees} onFeeHidden={onFeeHidden} />
        </>
    );
}

/**
 * A cell ticked in the grid — keyed by its fee line id in the selection map.
 * A coloured cell is ticked to MASK its line; a grey cell whose line was
 * retired is ticked to RESTORE it. Both live in one selection so a single
 * click applies the whole batch.
 */
interface SelectedCell {
    action: 'hide' | 'restore';
    feeId: number;
    row: GroupPaymentRow;
    column: GroupPaymentColumn;
    /** What is paid on the line (hide only — the avance preview). */
    montant: string;
}

/**
 * POSTs to the SAME endpoints as the inscription edit modal's trash and
 * corbeille (inscriptions.fees.hide / .restore →
 * BasculerVisibiliteFraisInscription): hiding masks the line (never deletes
 * it) and releases what was paid on it as an avance; restoring makes it due
 * again and never re-attaches that avance. Nothing new server-side, so the
 * matrix shortcut and the inscription modal can never diverge.
 */
async function postFeeVisibility(
    action: 'hide' | 'restore',
    inscriptionId: string,
    feeId: number,
): Promise<{ montantLibere?: number | string }> {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const response = await fetch(`/backoffice/inscriptions/${inscriptionId}/fees/${feeId}/${action}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
    });

    if (response.status === 419) {
        throw new Error('Session expirée — rechargez la page (Ctrl + F5) puis recommencez.');
    }

    if (!response.ok) {
        let message = action === 'hide' ? 'Impossible de masquer ce frais.' : 'Impossible de restaurer ce frais.';
        try {
            const data = await response.json();
            if (typeof data?.message === 'string' && data.message !== '') {
                message = data.message;
            }
        } catch {
            // Non-JSON error page — keep the generic message.
        }
        throw new Error(message);
    }

    // A 2xx that is NOT JSON means the request was redirected (login page,
    // an HTML error rendered down the redirect path): the call did not
    // report success, so it must never be counted as one.
    if (!(response.headers.get('content-type') ?? '').includes('application/json')) {
        throw new Error('Réponse inattendue du serveur — rechargez la page et vérifiez ce frais.');
    }

    return response.json();
}

function MatrixBody({
    matrix,
    loading,
    canHideFees,
    onFeeHidden,
}: {
    matrix: GroupPaymentMatrix | null;
    loading: boolean;
    canHideFees: boolean;
    onFeeHidden?: () => Promise<void> | void;
}) {
    const { ref: tipRef, bind, hide } = useMatrixTooltip();
    // Multi-select: clicking a cell's icon toggles it in/out of this map
    // (keyed by fee line id), then ONE button masks the whole batch.
    const [selection, setSelection] = useState<Map<number, SelectedCell>>(new Map());
    const [processing, setProcessing] = useState(false);
    // Batch progress while masking: how many POSTs are done out of how many
    // were selected — drives the button's « n/total » and the bar.
    const [progress, setProgress] = useState<{ done: number; total: number } | null>(null);
    const [errors, setErrors] = useState<string[]>([]);
    const [success, setSuccess] = useState<string | null>(null);
    // Synchronous re-entry guard: `processing` only disables the button on
    // the NEXT render, so a fast double-click could start the batch twice.
    const runningRef = useRef(false);

    function toggleSelect(entry: SelectedCell) {
        setSuccess(null);
        setSelection((prev) => {
            const next = new Map(prev);
            if (next.has(entry.feeId)) {
                next.delete(entry.feeId);
            } else {
                next.set(entry.feeId, entry);
            }
            return next;
        });
    }

    /**
     * Applies the batch by POSTing each fee to its SAME per-fee endpoint
     * (hide or restore), sequentially. Each hide is its own guarded transaction server-side,
     * so a refusal mid-batch loses nothing: the ones already masked stay
     * masked, the refused ones stay SELECTED with their reason listed —
     * reported, never silently skipped (CLAUDE.md §16 « signaler plutôt
     * que masquer »).
     */
    async function confirmHide() {
        if (selection.size === 0 || runningRef.current) {
            return;
        }

        runningRef.current = true;
        setProcessing(true);
        setErrors([]);
        setSuccess(null);

        const total = selection.size;
        let traites = 0;
        setProgress({ done: 0, total });

        let masques = 0;
        let restaures = 0;
        let libere = 0;
        const failures: string[] = [];
        const failedIds = new Set<number>();

        for (const entry of selection.values()) {
            const feeId = entry.feeId;
            try {
                const data = await postFeeVisibility(entry.action, entry.row.key, feeId);
                if (entry.action === 'hide') {
                    masques += 1;
                    libere += Number(data.montantLibere ?? 0);
                } else {
                    restaures += 1;
                }
            } catch (e) {
                failedIds.add(feeId);
                failures.push(
                    `${entry.column.nom} — ${entry.row.student ?? entry.row.reference} : ${
                        e instanceof Error ? e.message : 'refusé'
                    }`,
                );
            }
            traites += 1;
            setProgress({ done: traites, total });
        }

        // Keep only the refused cells selected, so the user sees exactly
        // what did NOT go through and can retry or deselect them.
        setSelection((prev) => {
            const next = new Map<number, SelectedCell>();
            for (const [feeId, entry] of prev) {
                if (failedIds.has(feeId)) {
                    next.set(feeId, entry);
                }
            }
            return next;
        });
        setErrors(failures);

        if (masques + restaures > 0) {
            const parts: string[] = [];
            if (masques > 0) {
                parts.push(
                    `${masques} frais masqué${masques > 1 ? 's' : ''}` +
                        (libere > 0 ? ` (${money(String(libere))} libérés en avance pour les étudiants)` : ''),
                );
            }
            if (restaures > 0) {
                parts.push(`${restaures} frais restauré${restaures > 1 ? 's' : ''}`);
            }
            setSuccess(`${parts.join(', ')}.`);
            try {
                await onFeeHidden?.();
            } catch {
                // The masking itself succeeded; a failed refresh only leaves
                // the grid stale until the next sort / reopen.
            }
        }

        setProgress(null);
        setProcessing(false);
        runningRef.current = false;
    }

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

    // What the batch would release in avance — the sum of what is PAID on
    // the selected lines (the server recomputes it line by line; this figure
    // is only the banner's preview).
    const selected = [...selection.values()];
    const aMasquer = selected.filter((e) => e.action === 'hide');
    const aRestaurer = selected.filter((e) => e.action === 'restore');
    const selectionPaye = aMasquer.reduce((sum, e) => sum + Number(e.montant), 0);
    const boutonLibelle =
        aRestaurer.length === 0
            ? `Masquer la sélection (${selection.size})`
            : aMasquer.length === 0
              ? `Restaurer la sélection (${selection.size})`
              : `Appliquer la sélection (${selection.size})`;

    return (
        <>
            {success && selection.size === 0 && errors.length === 0 && (
                <div className="alert alert-success d-flex align-items-center justify-content-between py-2 mb-2">
                    <span>
                        <i className="ti ti-circle-check me-1" />
                        {success}
                    </span>
                    <button type="button" className="btn-close" aria-label="Fermer" onClick={() => setSuccess(null)} />
                </div>
            )}

            {/* Selection banner inline, not a second modal stacked on the
                matrix's own: Modal.tsx owns focus trap + Escape for ONE
                dialog, and a nested dialog would fight it. */}
            {(selection.size > 0 || errors.length > 0) && (
                <div className="alert alert-warning py-2 mb-2">
                    <div className="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <i className="ti ti-list-check me-1" />
                            <strong>{selection.size}</strong> frais sélectionné{selection.size > 1 ? 's' : ''}
                            {aMasquer.length > 0 && aRestaurer.length > 0 && (
                                <span>
                                    {' '}
                                    — {aMasquer.length} à masquer, {aRestaurer.length} à restaurer
                                </span>
                            )}
                            {aMasquer.length > 0 && (
                                <div className="fs-13 mt-1">
                                    <i className="ti ti-eye-off me-1" />
                                    {selectionPaye > 0
                                        ? `Masquer : ${money(String(selectionPaye))} déjà payés sur ces lignes seront libérés en avance pour les étudiants (rien n'est supprimé, la caisse ne bouge pas).`
                                        : "Masquer : ces frais ne seront plus dus. Ils restent restaurables."}
                                </div>
                            )}
                            {aRestaurer.length > 0 && (
                                <div className="fs-13 mt-1">
                                    <i className="ti ti-arrow-back-up me-1" />
                                    Restaurer : ces frais redeviennent dus. Un paiement libéré en avance lors du masquage n&apos;est PAS recollé automatiquement — il reste dans l&apos;onglet Avances.
                                </div>
                            )}
                            {processing && progress && (
                                <div className="d-flex align-items-center gap-2 mt-2" style={{ minWidth: '220px' }}>
                                    <span className="spinner-border spinner-border-sm text-warning" role="status" />
                                    <div
                                        className="progress flex-grow-1"
                                        style={{ height: '6px' }}
                                        role="progressbar"
                                        aria-valuenow={progress.done}
                                        aria-valuemin={0}
                                        aria-valuemax={progress.total}
                                    >
                                        <div
                                            className="progress-bar bg-warning"
                                            style={{ width: `${(progress.done / progress.total) * 100}%` }}
                                        />
                                    </div>
                                    <span className="fs-13 text-nowrap">
                                        {progress.done}/{progress.total}
                                    </span>
                                </div>
                            )}
                            {success && <div className="text-success fs-13 mt-1">{success}</div>}
                            {errors.map((message) => (
                                <div key={message} className="text-danger fs-13 mt-1">
                                    {message}
                                </div>
                            ))}
                        </div>
                        <div className="d-flex gap-2">
                            <button
                                type="button"
                                className="btn btn-sm btn-light"
                                disabled={processing}
                                onClick={() => {
                                    setSelection(new Map());
                                    setErrors([]);
                                }}
                            >
                                Tout désélectionner
                            </button>
                            <button
                                type="button"
                                className="btn btn-sm btn-danger"
                                disabled={processing || selection.size === 0}
                                onClick={() => void confirmHide()}
                            >
                                {processing ? (
                                    <>
                                        <span className="spinner-border spinner-border-sm me-1" role="status" />
                                        Traitement…{progress ? ` ${progress.done}/${progress.total}` : ''}
                                    </>
                                ) : (
                                    boutonLibelle
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Caps the grid at a readable height instead of letting a
                45-student group stretch the dialog past the viewport: the box
                scrolls in BOTH directions (down through the students, sideways
                through a year of fee columns) while the modal itself stays a
                centred dialog. */}
            <div
                className="table-responsive"
                style={{
                    maxHeight: '75vh',
                    // The grid stays visible but reads as « busy » while the
                    // batch runs — and ignores clicks, so the selection can't
                    // change mid-masquage.
                    opacity: processing ? 0.55 : undefined,
                    pointerEvents: processing ? 'none' : undefined,
                    transition: 'opacity 0.15s ease',
                }}
                aria-busy={processing}
                onScroll={hide}
                onMouseLeave={hide}
            >
                <table className="table table-bordered w-100 mb-0 gls-payment-matrix">
                    <thead>
                        <tr className="bg-light">
                            <th className="h6 border-top-0" style={CELL_STYLE}>
                                N°
                            </th>
                            <th className="h6 border-top-0" style={{ ...CELL_STYLE, textAlign: 'left' }}>
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
                                        style={{ ...CELL_STYLE, background: rowFill, textAlign: 'left' }}
                                        {...bind(rowTip(row))}
                                    >
                                        {/* Photo à GAUCHE du nom — la même source
                                            que la fiche de présence
                                            (Student::avatarUrl()), qui retombe sur
                                            l'avatar par défaut quand aucune photo
                                            n'a été téléversée : la colonne ne
                                            présente donc jamais de trou. */}
                                        <span className="d-inline-flex align-items-center gap-2">
                                            <img
                                                src={row.photoUrl ?? '/assets/images/avatar/defaultman.webp'}
                                                alt=""
                                                className="avatar avatar-sm rounded-circle flex-shrink-0"
                                            />
                                            {row.studentShowUrl ? (
                                                <a href={row.studentShowUrl} className="text-reset">
                                                    {row.student ?? '-'}
                                                </a>
                                            ) : (
                                                (row.student ?? '-')
                                            )}
                                        </span>
                                    </td>
                                    {matrix.columns.map((column) => {
                                        const cell = row.cells[column.key];

                                        // No cell at all = the fee is not on
                                        // this student's inscription: grey and
                                        // empty, never a 0 DH debt.
                                        if (!cell) {
                                            // A RETIRED line (not a never-added
                                            // one) can be restored from here.
                                            const masqueId = row.masques?.[column.key] ?? null;
                                            const canRestore = canHideFees && masqueId !== null;
                                            const restoreSelected = masqueId !== null && selection.has(masqueId);

                                            return (
                                                <td
                                                    key={column.key}
                                                    className={canRestore ? 'gls-matrix-cell--hideable' : undefined}
                                                    style={{ ...MONEY_CELL_STYLE, background: ABSENT_FILL }}
                                                    {...bind(
                                                        row.notesMasquees[column.key]
                                                            ? { lines: ['Frais retiré de cette inscription'], note: row.notesMasquees[column.key] }
                                                            : masqueId !== null
                                                              ? { lines: ['Frais retiré de cette inscription'] }
                                                              : { lines: ['Frais non affecté à cet étudiant'] },
                                                    )}
                                                >
                                                    {canRestore && (
                                                        <button
                                                            type="button"
                                                            className={
                                                                restoreSelected
                                                                    ? 'gls-matrix-hide-btn gls-matrix-restore-btn gls-matrix-hide-btn--active'
                                                                    : 'gls-matrix-hide-btn gls-matrix-restore-btn'
                                                            }
                                                            title={restoreSelected ? 'Retirer de la sélection' : 'Sélectionner pour restaurer'}
                                                            aria-pressed={restoreSelected}
                                                            aria-label={`Restaurer ${column.nom} pour ${row.student ?? row.reference}`}
                                                            disabled={processing}
                                                            onClick={(event) => {
                                                                event.stopPropagation();
                                                                hide();
                                                                toggleSelect({
                                                                    action: 'restore',
                                                                    feeId: masqueId,
                                                                    row,
                                                                    column,
                                                                    montant: '0',
                                                                });
                                                            }}
                                                        >
                                                            <i className={restoreSelected ? 'ti ti-check' : 'ti ti-arrow-back-up'} />
                                                        </button>
                                                    )}
                                                </td>
                                            );
                                        }

                                        const canHide = canHideFees && cell.feeId !== null;
                                        const selected = cell.feeId !== null && selection.has(cell.feeId);

                                        return (
                                            <td
                                                key={column.key}
                                                className={canHide ? 'gls-matrix-cell--hideable' : undefined}
                                                style={{ ...MONEY_CELL_STYLE, background: CELL_FILL[cell.state] }}
                                                {...bind(cellTip(cell, column))}
                                            >
                                                {money(cell.montant)}
                                                {canHide && (
                                                    <button
                                                        type="button"
                                                        className={
                                                            selected
                                                                ? 'gls-matrix-hide-btn gls-matrix-hide-btn--active'
                                                                : 'gls-matrix-hide-btn'
                                                        }
                                                        title={selected ? 'Retirer de la sélection' : 'Sélectionner pour masquer'}
                                                        aria-pressed={selected}
                                                        aria-label={`Masquer ${column.nom} pour ${row.student ?? row.reference}`}
                                                        disabled={processing}
                                                        onClick={(event) => {
                                                            event.stopPropagation();
                                                            hide();
                                                            toggleSelect({
                                                                action: 'hide',
                                                                feeId: cell.feeId as number,
                                                                row,
                                                                column,
                                                                montant: cell.montant,
                                                            });
                                                        }}
                                                    >
                                                        <i className={selected ? 'ti ti-check' : 'ti ti-eye-off'} />
                                                    </button>
                                                )}
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
