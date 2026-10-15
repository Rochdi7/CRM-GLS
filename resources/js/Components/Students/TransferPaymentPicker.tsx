import { useMemo, type ReactNode } from 'react';
import StatusBadge from '@/Components/Details/StatusBadge';
import { statutVariant } from '@/Lib/inscriptionStatut';
import type { InscriptionPaiementsTransferables, PaiementTransferable, PaiementsTransferables } from '@/Types';

interface TransferPaymentPickerProps {
    tree: PaiementsTransferables | null;
    loading: boolean;
    /** Ids d'encaissements cochés. En lecture seule, c'est ce que la demande a retenu. */
    selected: number[];
    onChange?: (ids: number[]) => void;
    readOnly?: boolean;
    idPrefix?: string;
}

function fmt(montant: string | number): string {
    const n = typeof montant === 'number' ? montant : parseFloat(montant) || 0;

    return `${n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} DH`;
}

/** Les lignes qu'un clic peut cocher — jamais une ligne bloquée. */
function cochables(paiements: PaiementTransferable[]): number[] {
    return paiements.filter((p) => p.blocage === null).map((p) => p.id);
}

/** Ce que les lignes cochées emportent (le RESTE d'une avance, le montant d'un paiement). */
function emporte(rows: PaiementTransferable[]): number {
    return rows.reduce((sum, p) => sum + (parseFloat(p.emporte) || 0), 0);
}

interface FraisGroupe {
    feeId: number;
    nom: string;
    paiements: PaiementTransferable[];
}

/** Les paiements d'une inscription, regroupés par ligne de frais (l'unité de choix). */
function parFrais(paiements: PaiementTransferable[]): FraisGroupe[] {
    const map = new Map<number, FraisGroupe>();
    paiements.forEach((p) => {
        const feeId = p.feeId ?? 0;
        const groupe = map.get(feeId) ?? { feeId, nom: p.frais ?? '—', paiements: [] };
        groupe.paiements.push(p);
        map.set(feeId, groupe);
    });

    return [...map.values()];
}

/**
 * Ce qu'un transfert d'étudiant emporte (14/10/2026) — année → inscription →
 * FRAIS, une case par frais (ses paiements partent avec lui, une application
 * d'avance comprise : un paiement reste un paiement), et une case par avance,
 * qui n'emporte que son RESTE (15/10/2026 — la part déjà appliquée est sur son
 * frais et se décide là). Tout ce qui décide si une ligne est cochable, et
 * pour combien, vient du serveur (`GetPaiementsTransferables`) : ici on
 * dessine, on coche, on additionne. Partagé par le modal de demande
 * (cochable) et le dialogue de validation (lecture seule).
 */
export default function TransferPaymentPicker({ tree, loading, selected, onChange, readOnly = false, idPrefix = 'tp' }: TransferPaymentPickerProps) {
    const selectedSet = useMemo(() => new Set(selected), [selected]);

    const allRows = useMemo<PaiementTransferable[]>(() => {
        if (!tree) return [];

        return [...tree.avances, ...tree.annees.flatMap((a) => a.inscriptions.flatMap((i) => i.paiements))];
    }, [tree]);

    const allCochables = useMemo(() => cochables(allRows), [allRows]);

    const isChecked = (p: PaiementTransferable): boolean => selectedSet.has(p.id);

    const recu = (rows: PaiementTransferable[]): number => emporte(rows.filter(isChecked));

    const nbSelectionne = allRows.filter(isChecked).length;

    function toggleGroup(ids: number[]) {
        if (readOnly || !onChange || ids.length === 0) return;
        const every = ids.every((id) => selectedSet.has(id));
        const next = new Set(selectedSet);
        ids.forEach((id) => (every ? next.delete(id) : next.add(id)));
        onChange([...next]);
    }

    function groupState(ids: number[]): 'none' | 'some' | 'all' {
        if (ids.length === 0) return 'none';
        const n = ids.filter((id) => selectedSet.has(id)).length;

        return n === 0 ? 'none' : n === ids.length ? 'all' : 'some';
    }

    if (loading) {
        return (
            <div className="text-center py-4">
                <div className="spinner-border spinner-border-sm text-primary" role="status" />
                <div className="fs-12 text-muted mt-2">Chargement des paiements...</div>
            </div>
        );
    }

    if (!tree || allRows.length === 0) {
        return (
            <div className="alert alert-light border fs-13 mb-0" role="alert">
                <i className="ti ti-cash-off me-1" />
                Aucun paiement sur cette fiche — rien à emporter.
            </div>
        );
    }

    const checkbox = (id: string, ids: number[], checked: boolean, disabled: boolean, label: string) => (
        <input
            id={id}
            type="checkbox"
            className="form-check-input m-0"
            checked={checked}
            ref={(el) => {
                if (el) el.indeterminate = !checked && groupState(ids) === 'some';
            }}
            disabled={readOnly || disabled}
            onChange={() => toggleGroup(ids)}
            aria-label={label}
        />
    );

    /** Une ligne d'encaissement, sous son frais ou dans les avances. */
    const renderLigne = (p: PaiementTransferable, withCheckbox: boolean, key: string) => {
        const checked = isChecked(p);
        const disabled = p.blocage !== null;
        const inputId = `${idPrefix}-${key}-${p.id}`;

        return (
            <tr key={p.id} className={disabled ? 'text-muted' : undefined}>
                <td className="text-center" style={{ width: 36 }}>
                    {withCheckbox && checkbox(inputId, [p.id], checked, disabled, `Avance ${p.reference}`)}
                </td>
                <td className="text-normal-case">
                    <label htmlFor={withCheckbox ? inputId : undefined} className="mb-0 d-block" style={{ cursor: withCheckbox && !disabled ? 'pointer' : 'default' }}>
                        <code className="text-dark">{p.reference}</code>
                        <span className="text-muted ms-2 fs-12">{p.date ?? '—'}</span>
                        {p.type === 'application' && <span className="badge badge-soft-secondary ms-2">Application d'avance</span>}
                    </label>
                </td>
                <td className="text-normal-case fs-12">
                    {p.methode}
                    {p.caisse && <span className="text-muted"> · {p.caisse}</span>}
                </td>
                <td className="text-end text-nowrap">
                    <span className={disabled ? 'text-decoration-line-through' : ''}>{fmt(p.emporte)}</span>
                    {p.reste !== null && parseFloat(p.utilise ?? '0') > 0 && (
                        <div className="fs-12 text-muted">
                            reste sur {fmt(p.montant)} · {fmt(p.utilise ?? '0')} déjà appliqués
                        </div>
                    )}
                    {parseFloat(p.rembourse) > 0 && <div className="fs-12 text-danger">− {fmt(p.rembourse)} remboursés</div>}
                </td>
                <td className="text-normal-case fs-12">
                    {p.blocage !== null && (
                        <span className="badge badge-soft-secondary">
                            <i className="ti ti-lock me-1" />
                            {p.blocageLabel}
                        </span>
                    )}
                </td>
            </tr>
        );
    };

    /** Un frais : la case, puis ses paiements en dessous (ils partent avec lui). */
    const renderFrais = (f: FraisGroupe, key: string) => {
        const ids = cochables(f.paiements);
        const checked = ids.length > 0 && ids.every((id) => selectedSet.has(id));
        const inputId = `${idPrefix}-${key}-f${f.feeId}`;
        const total = emporte(f.paiements);

        return (
            <tbody key={key}>
                <tr className="bg-light">
                    <td className="text-center" style={{ width: 36 }}>
                        {checkbox(inputId, ids, checked, ids.length === 0, `Frais ${f.nom}`)}
                    </td>
                    <td colSpan={2} className="text-normal-case">
                        <label htmlFor={inputId} className="mb-0 d-block fw-semibold" style={{ cursor: readOnly || ids.length === 0 ? 'default' : 'pointer' }}>
                            {f.nom}
                            <span className="text-muted fw-normal fs-12 ms-2">
                                {f.paiements.length} paiement{f.paiements.length > 1 ? 's' : ''}
                            </span>
                        </label>
                    </td>
                    <td className="text-end fw-semibold text-nowrap">{fmt(total)}</td>
                    <td />
                </tr>
                {f.paiements.map((p) => renderLigne(p, false, key))}
            </tbody>
        );
    };

    const renderEntete = (key: string, title: ReactNode, ids: number[], extra?: ReactNode) => (
        <div className="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
            {!readOnly && checkbox(`${idPrefix}-${key}-all`, ids, groupState(ids) === 'all', ids.length === 0, 'Tout cocher')}
            <label htmlFor={`${idPrefix}-${key}-all`} className="mb-0 fw-semibold flex-grow-1" style={{ cursor: readOnly ? 'default' : 'pointer' }}>
                {title}
            </label>
            {extra}
        </div>
    );

    const renderInscription = (ins: InscriptionPaiementsTransferables, anneeKey: string) => {
        const key = `${anneeKey}-i${ins.id ?? 0}`;

        return (
            <div key={key} className="border rounded mb-2 overflow-hidden">
                {renderEntete(
                    key,
                    <span className="text-normal-case">
                        <span className="text-uppercase">{ins.groupe ?? '—'}</span>
                        {ins.niveau && <span className="badge badge-soft-info ms-2">{ins.niveau}</span>}
                        <code className="ms-2 fs-12 text-muted">{ins.reference}</code>
                        {ins.statut && (
                            <span className="ms-2">
                                <StatusBadge label={ins.statut} variant={statutVariant(ins.statut)} dot />
                            </span>
                        )}
                    </span>,
                    cochables(ins.paiements),
                    <span className="fs-12 text-muted text-nowrap">{fmt(recu(ins.paiements))} cochés</span>,
                )}
                <div className="table-responsive">
                    <table className="table table-sm mb-0 align-middle">
                        {parFrais(ins.paiements).map((f) => renderFrais(f, key))}
                    </table>
                </div>
            </div>
        );
    };

    return (
        <div>
            {!readOnly && (
                <div className="form-check mb-2">
                    <input
                        id={`${idPrefix}-all`}
                        type="checkbox"
                        className="form-check-input"
                        checked={groupState(allCochables) === 'all'}
                        ref={(el) => {
                            if (el) el.indeterminate = groupState(allCochables) === 'some';
                        }}
                        onChange={() => toggleGroup(allCochables)}
                    />
                    <label htmlFor={`${idPrefix}-all`} className="form-check-label fw-semibold">
                        Tout cocher
                    </label>
                </div>
            )}

            <div style={{ maxHeight: 420, overflowY: 'auto' }}>
                {tree.avances.length > 0 && (
                    <div className="border rounded mb-2 overflow-hidden">
                        {renderEntete(
                            'av',
                            <span>
                                <i className="ti ti-coins me-1 text-primary" />
                                Avances non affectées
                                <span className="text-muted fw-normal fs-12 ms-2">sans année ni dossier</span>
                            </span>,
                            cochables(tree.avances),
                            <span className="fs-12 text-muted text-nowrap">{fmt(recu(tree.avances))} cochés</span>,
                        )}
                        <div className="table-responsive">
                            <table className="table table-sm mb-0 align-middle">
                                <tbody>{tree.avances.map((p) => renderLigne(p, true, 'av'))}</tbody>
                            </table>
                        </div>
                    </div>
                )}

                {tree.annees.map((a, index) => {
                    const anneeKey = `a${index}`;

                    return (
                        <div key={anneeKey} className="mb-3">
                            <div className="d-flex align-items-center gap-2 mb-2">
                                <i className="ti ti-calendar text-primary" />
                                <span className="fw-bold">{a.annee ?? 'Année inconnue'}</span>
                                <span className="text-muted fs-12">
                                    {a.inscriptions.length} inscription{a.inscriptions.length > 1 ? 's' : ''}
                                </span>
                            </div>
                            {a.inscriptions.map((ins) => renderInscription(ins, anneeKey))}
                        </div>
                    );
                })}
            </div>

            <div className="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                <span className="fs-13 text-muted">
                    {nbSelectionne} paiement{nbSelectionne > 1 ? 's' : ''} {readOnly ? 'emporté' : 'coché'}
                    {nbSelectionne > 1 ? 's' : ''} sur {allCochables.length}
                </span>
                <span className="fw-bold">
                    {fmt(recu(allRows))}
                    <span className="text-muted fw-normal fs-12"> / {fmt(tree.total)}</span>
                </span>
            </div>
        </div>
    );
}
