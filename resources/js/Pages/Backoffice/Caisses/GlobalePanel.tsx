import { useState } from 'react';
import DateField from '@/Components/Forms/DateField';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import { formatMontant } from '@/Lib/money';
import type { CaisseGlobaleData } from '@/Types';

export interface GlobaleFilters {
    globaleDateFrom: string;
    globaleDateTo: string;
}

interface GlobalePanelProps {
    data: CaisseGlobaleData;
    filters: GlobaleFilters;
    onFilter: (next: Partial<GlobaleFilters>) => void;
    onReset: () => void;
    resetActive: boolean;
    /**
     * Hide the Centre column once the top-bar switcher is on a single centre
     * (§5): repeating the active context in every row is noise. Every other
     * caisse panel already receives it — this one was missed (M-13).
     */
    centerLocked: boolean;
}

/**
 * « Caisse globale » — where the money of the active centre is: one card
 * per kind of account (physical tills, TPE, bank/virement, cheques — the
 * « Externe » kind is hidden for now, see GetCaisseGlobale::LABELS) and, under the selected card, every account of that kind with its
 * stored solde. Mirrors GetCaisseGlobale; every figure is an account's own
 * CaisseLedger balance — nothing is derived, nothing is counted twice.
 *
 * The date window REWINDS those balances rather than summing a period: with
 * « Date de fin » set, all four cards and their accounts show what they HELD
 * at the end of that day (GetCaisseGlobale::soldesAt rebuilds it from the
 * journal).
 *
 * No « soldes tels qu'ils étaient le … » banner is drawn — asked for on
 * 03/09/2026, the date filter speaks for itself. The safety it used to carry
 * lives server-side instead: a « Date de fin » older than the journal's first
 * entry (GetCaisseGlobale::journalDepuis — 26/08/2026, when CaisseLedger was
 * introduced) performs NO rewind at all, because subtracting every known
 * movement printed 0.00 DH for tills that held money. Never reinstate a
 * client-side rewind or a zero fallback here.
 *
 * Layout (03/11/2026, « lisible pour le CEO ») : un bandeau « Trésorerie
 * totale » avec la répartition par type, une carte par type qui sert AUSSI
 * de sélecteur (les onglets en double ont été retirés), puis le détail des
 * comptes du type choisi, classés, avec leur part du type. Tous les
 * montants affichés sont ceux du serveur (`data.total`, `card.total`,
 * `row.solde`) — seules les PARTS (%) sont calculées ici, pour l'affichage.
 */
const TYPE_STYLE: Record<string, { variant: string; icon: string }> = {
    'Caissière': { variant: 'gls-cg-primary', icon: 'ti ti-wallet' },
    TPE: { variant: 'gls-cg-info', icon: 'ti ti-credit-card' },
    Virement: { variant: 'gls-cg-violet', icon: 'ti ti-building-bank' },
    'Chèque': { variant: 'gls-cg-pink', icon: 'ti ti-file-dollar' },
    Externe: { variant: 'gls-cg-warning', icon: 'ti ti-safe' },
};

function styleOf(type: string) {
    return TYPE_STYLE[type] ?? { variant: 'gls-cg-secondary', icon: 'ti ti-cash' };
}

/** Share of `part` in `whole`, as a 0–100 number; 0 when the whole is not positive. */
function share(part: string | number, whole: string | number): number {
    const p = Number(part);
    const w = Number(whole);

    if (!Number.isFinite(p) || !Number.isFinite(w) || w <= 0 || p <= 0) {
        return 0;
    }

    return Math.min(100, (p / w) * 100);
}

function formatPct(value: number): string {
    return `${value.toLocaleString('fr-FR', { maximumFractionDigits: value > 0 && value < 1 ? 1 : 0 })} %`;
}

function formatDate(iso: string): string {
    const [y, m, d] = iso.split('-');

    return d && m && y ? `${d}/${m}/${y}` : iso;
}

function initials(nom: string): string {
    return nom
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export default function GlobalePanel({ data, filters, onFilter, onReset, resetActive, centerLocked }: GlobalePanelProps) {
    const [active, setActive] = useState<string>(data.cards[0]?.type ?? '');
    const rows = data.comptes[active] ?? [];
    const activeCard = data.cards.find((c) => c.type === active);
    const activeStyle = styleOf(active);
    const totalComptes = data.cards.reduce((sum, card) => sum + card.count, 0);
    const totalNegatif = Number(data.total) < 0;
    // The rewind is performed only when the server says so: before the
    // journal's first entry the figures are today's stored soldes.
    const situation = data.asOf && !data.avantJournal ? `Situation au ${formatDate(data.asOf)}` : 'Soldes actuels';

    return (
        <>
            <TableToolbar onReset={onReset} resetActive={resetActive}>
                <div style={{ width: 190 }}>
                    <label className="form-label" htmlFor="caisse-globale-date-from">
                        Date de début
                    </label>
                    <DateField
                        id="caisse-globale-date-from"
                        value={filters.globaleDateFrom}
                        onChange={(event) => onFilter({ globaleDateFrom: event.target.value })}
                    />
                </div>
                <div style={{ width: 190 }}>
                    <label className="form-label" htmlFor="caisse-globale-date-to">
                        Date de fin
                    </label>
                    <DateField
                        id="caisse-globale-date-to"
                        value={filters.globaleDateTo}
                        onChange={(event) => onFilter({ globaleDateTo: event.target.value })}
                    />
                </div>
            </TableToolbar>

            {/* --- Trésorerie totale + répartition ------------------------- */}
            <div className="card gls-cg-hero border-0 mb-3">
                <div className="card-body p-4">
                    <div className="row g-4 align-items-center">
                        <div className="col-lg-5">
                            <div className="d-flex align-items-center gap-2 mb-2">
                                <span className="gls-cg-hero-icon">
                                    <i className="ti ti-building-bank" />
                                </span>
                                <span className="gls-cg-hero-label">Trésorerie totale</span>
                            </div>
                            <div className={`gls-cg-hero-amount${totalNegatif ? ' is-negative' : ''}`}>
                                {formatMontant(data.total)}
                                <span className="gls-cg-hero-unit">DH</span>
                            </div>
                            <div className="gls-cg-hero-meta">
                                <span>
                                    <i className="ti ti-calendar-event me-1" />
                                    {situation}
                                </span>
                                <span>
                                    <i className="ti ti-stack-2 me-1" />
                                    {totalComptes} compte{totalComptes > 1 ? 's' : ''}
                                </span>
                            </div>
                        </div>

                        <div className="col-lg-7">
                            <div className="gls-cg-hero-subtitle">Répartition par type de compte</div>
                            <div className="gls-cg-stack" role="img" aria-label="Répartition de la trésorerie par type de compte">
                                {data.cards.map((card) => {
                                    const pct = share(card.total, data.total);

                                    return pct > 0 ? (
                                        <span
                                            key={card.type}
                                            className={`gls-cg-stack-seg ${styleOf(card.type).variant}`}
                                            style={{ width: `${pct}%` }}
                                            title={`${card.label} : ${formatMontant(card.total)} DH (${formatPct(pct)})`}
                                        />
                                    ) : null;
                                })}
                            </div>
                            <div className="gls-cg-legend">
                                {data.cards.map((card) => (
                                    <div className="gls-cg-legend-item" key={card.type}>
                                        <span className={`gls-cg-dot ${styleOf(card.type).variant}`} />
                                        <span className="gls-cg-legend-name">{card.label}</span>
                                        <span className="gls-cg-legend-pct">{formatPct(share(card.total, data.total))}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* --- Une carte par type : sert aussi de sélecteur ------------ */}
            <div className="row g-3 mb-3">
                {data.cards.map((card) => {
                    const { variant, icon } = styleOf(card.type);
                    const isActive = active === card.type;
                    const pct = share(card.total, data.total);

                    return (
                        <div className="col-sm-6 col-xl-3" key={card.type}>
                            <button
                                type="button"
                                onClick={() => setActive(card.type)}
                                aria-pressed={isActive}
                                className={`card gls-cg-kpi ${variant} w-100 h-100 mb-0 text-start${isActive ? ' is-active' : ''}`}
                            >
                                <div className="card-body p-3 w-100">
                                    <div className="d-flex align-items-start justify-content-between gap-2 mb-3">
                                        <span className="gls-cg-kpi-icon">
                                            <i className={icon} />
                                        </span>
                                        <span className="gls-cg-kpi-pct">{formatPct(pct)}</span>
                                    </div>
                                    <div className="gls-cg-kpi-label">{card.label}</div>
                                    <div className={`gls-cg-kpi-amount${Number(card.total) < 0 ? ' text-danger' : ''}`}>
                                        {formatMontant(card.total)}
                                        <span className="gls-cg-kpi-unit">DH</span>
                                    </div>
                                    <div className="gls-cg-kpi-bar">
                                        <span style={{ width: `${pct}%` }} />
                                    </div>
                                    <div className="gls-cg-kpi-foot">
                                        <span>
                                            {card.count} compte{card.count > 1 ? 's' : ''}
                                        </span>
                                        <span className="gls-cg-kpi-cta">
                                            {isActive ? 'Affiché ci-dessous' : 'Voir le détail'}
                                            <i className={`ti ${isActive ? 'ti-chevron-down' : 'ti-chevron-right'} ms-1`} />
                                        </span>
                                    </div>
                                </div>
                            </button>
                        </div>
                    );
                })}
            </div>

            {/* --- Détail des comptes du type choisi ----------------------- */}
            <div className={`card gls-cg-detail ${activeStyle.variant}`}>
                <div className="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div className="d-flex align-items-center gap-2">
                        <span className="gls-cg-kpi-icon gls-cg-kpi-icon-sm">
                            <i className={activeStyle.icon} />
                        </span>
                        <div>
                            <h5 className="mb-0">{activeCard?.label ?? '—'}</h5>
                            <span className="fs-13 text-muted">
                                {rows.length} compte{rows.length > 1 ? 's' : ''} · {situation.toLowerCase()}
                            </span>
                        </div>
                    </div>
                    <div className="text-end">
                        <div className="fs-13 text-muted">Total</div>
                        <div className="gls-cg-detail-total">{formatMontant(activeCard?.total ?? 0)} DH</div>
                    </div>
                </div>

                <div className="card-body p-0">
                    {rows.length === 0 ? (
                        <EmptyState title="Aucun compte" message={`Aucun compte « ${activeCard?.label ?? ''} » dans ce périmètre.`} icon="ti ti-cash" />
                    ) : (
                        <DataTable
                            head={
                                <tr>
                                    <th style={{ width: 56 }}>#</th>
                                    <th>Désignation</th>
                                    {!centerLocked && <th>Centre</th>}
                                    <th style={{ minWidth: 180 }}>Part du total</th>
                                    <th className="text-end">Montant</th>
                                </tr>
                            }
                        >
                            {rows.map((row, index) => {
                                const solde = Number(row.solde);
                                const pct = share(row.solde, activeCard?.total ?? 0);
                                const showResponsable = row.responsable && row.responsable.trim() !== row.nom.trim();

                                return (
                                    <tr key={row.id} className={solde === 0 ? 'gls-cg-row-empty' : undefined}>
                                        <td className="text-muted">{index + 1}</td>
                                        <td>
                                            <div className="d-flex align-items-center gap-2">
                                                <span className="gls-cg-avatar">{initials(row.nom)}</span>
                                                <div style={{ minWidth: 0 }}>
                                                    <a href={row.showUrl} className="fw-medium">
                                                        {row.nom}
                                                    </a>
                                                    {showResponsable && (
                                                        <div className="fs-13 text-muted">Responsable : {row.responsable}</div>
                                                    )}
                                                </div>
                                            </div>
                                        </td>
                                        {!centerLocked && <td>{row.centre ?? '-'}</td>}
                                        <td>
                                            <div className="d-flex align-items-center gap-2">
                                                <div className="gls-cg-share flex-grow-1">
                                                    <span style={{ width: `${pct}%` }} />
                                                </div>
                                                <span className="fs-13 text-muted gls-cg-share-pct">{formatPct(pct)}</span>
                                            </div>
                                        </td>
                                        <td className={`text-end fw-semibold text-nowrap${solde < 0 ? ' text-danger' : ''}`}>
                                            {formatMontant(row.solde)} DH
                                        </td>
                                    </tr>
                                );
                            })}
                            <tr className="table-light">
                                <td colSpan={centerLocked ? 3 : 4} className="text-end text-muted">
                                    Total {activeCard?.label}
                                </td>
                                <td className="text-end fw-bold text-nowrap">{formatMontant(activeCard?.total ?? 0)} DH</td>
                            </tr>
                        </DataTable>
                    )}
                </div>
            </div>
        </>
    );
}
