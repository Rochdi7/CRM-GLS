import Card from '@/Components/Shared/Card';
import StatusBadge from '@/Components/Details/StatusBadge';
import { t } from '@/Lib/i18n';
import type { EspaceEnseignantData } from '@/Types';

interface Props {
    data: EspaceEnseignantData;
    onMoisChange: (mois: string) => void;
    loading: boolean;
}

/**
 * Espace enseignant sur le tableau de bord (23/09/2026) — ce que le prof a
 * enseigné ce mois, et ce qu'il a touché.
 *
 * Le composant ne décide rien : les données arrivent déjà filtrées sur le
 * prof connecté (gate serveur, `GetEspaceEnseignant`). Il PEINT les statuts
 * tels quels — un paiement « En attente » n'est pas de l'argent reçu et doit
 * se lire comme tel, jamais fondu dans un total.
 */
export default function EspaceEnseignant({ data, onMoisChange, loading }: Props) {
    function variantStatut(statut: string): 'success' | 'warning' | 'danger' | 'secondary' {
        if (statut === 'Approuvée') return 'success';
        if (statut === 'En attente') return 'warning';
        if (statut === 'Refusée' || statut === 'Annulée') return 'danger';

        return 'secondary';
    }

    function libelleStatut(statut: string): string {
        if (statut === 'Approuvée') return t('Paid');
        if (statut === 'En attente') return t('Pending');

        return statut;
    }

    function fmtDate(d: string | null): string {
        return d === null ? '-' : new Date(d + 'T00:00:00').toLocaleDateString('fr-FR');
    }

    function moisVoisin(delta: number): string {
        const [y, m] = data.mois.split('-').map(Number);
        const d = new Date(y, m - 1 + delta, 1);

        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    }

    const moisCourant = (() => {
        const now = new Date();

        return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    })();

    return (
        <>
            {/* ── Cumul ─────────────────────────────────────────────── */}
            <div className="row g-3 mb-3">
                <div className="col-6 col-md-4">
                    <Card>
                        <div className="text-muted fs-12 text-uppercase mb-1">{t('Received this year')}</div>
                        <div className="fs-24 fw-bold text-success">{data.cumul.approuve.toFixed(2)} MAD</div>
                        <small className="text-muted">
                            {t(':count payment(s)', { count: String(data.cumul.nombre) })}
                        </small>
                    </Card>
                </div>
                <div className="col-6 col-md-4">
                    <Card>
                        <div className="text-muted fs-12 text-uppercase mb-1">{t('Pending approval')}</div>
                        <div className={`fs-24 fw-bold ${data.cumul.enAttente > 0 ? 'text-warning' : 'text-muted'}`}>
                            {data.cumul.enAttente.toFixed(2)} MAD
                        </div>
                        <small className="text-muted">{t('Not received yet')}</small>
                    </Card>
                </div>
                <div className="col-12 col-md-4">
                    <Card>
                        <div className="text-muted fs-12 text-uppercase mb-1">{t('Pay mode')}</div>
                        <div className="fs-20 fw-bold">
                            {data.enseignant.mode === 'horaire' && t('Per hour')}
                            {data.enseignant.mode === 'gls' && t('GLS system')}
                            {data.enseignant.mode === 'win_win' && t('Win-win system')}
                            {data.enseignant.mode === null && <span className="text-muted">{t('Not set')}</span>}
                        </div>
                    </Card>
                </div>
            </div>

            {/* ── Groupes et séances du mois ────────────────────────── */}
            <Card
                title={t('My groups')}
                bodyClassName="p-0 py-3"
                tools={
                    <div className="d-flex align-items-center gap-2 mb-3">
                        <button
                            type="button"
                            className="btn btn-sm btn-light"
                            onClick={() => onMoisChange(moisVoisin(-1))}
                            disabled={loading}
                            aria-label={t('Previous month')}
                        >
                            <i className="ti ti-chevron-left" />
                        </button>
                        <span className="fw-semibold" style={{ minWidth: 130, textAlign: 'center' }}>
                            {data.moisLibelle}
                        </span>
                        <button
                            type="button"
                            className="btn btn-sm btn-light"
                            onClick={() => onMoisChange(moisVoisin(1))}
                            disabled={loading || data.mois >= moisCourant}
                            aria-label={t('Next month')}
                        >
                            <i className="ti ti-chevron-right" />
                        </button>
                    </div>
                }
            >
                <div className="table-responsive">
                    <table className="table table-nowrap mb-0">
                        <thead className="thead-light">
                            <tr>
                                <th>{t('Group')}</th>
                                <th className="text-center">{t('Sessions')}</th>
                                <th className="text-center">{t('Roll calls')}</th>
                                <th className="text-center">{t('Attendance rate')}</th>
                                <th>{t('Status')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.groupes.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="text-center text-muted py-4">
                                        {t('No group assigned this year.')}
                                    </td>
                                </tr>
                            )}
                            {data.groupes.map((g) => (
                                <tr key={g.id} className={g.actif ? '' : 'text-muted'}>
                                    <td>
                                        <div className="fw-semibold">{g.nom}</div>
                                        <div className="fs-12 text-muted">{g.niveau}</div>
                                    </td>
                                    <td className="text-center fw-semibold">{g.seancesCeMois}</td>
                                    <td className="text-center">
                                        {g.appels > 0 ? (
                                            <>
                                                <span className="text-success">{g.presents}</span>
                                                <span className="text-muted"> / {g.appels}</span>
                                            </>
                                        ) : (
                                            '-'
                                        )}
                                    </td>
                                    <td className="text-center">
                                        {g.tauxPresence === null ? (
                                            '-'
                                        ) : (
                                            <span
                                                className={`badge badge-soft-${
                                                    g.tauxPresence >= 75 ? 'success' : g.tauxPresence >= 50 ? 'warning' : 'danger'
                                                }`}
                                            >
                                                {g.tauxPresence} %
                                            </span>
                                        )}
                                    </td>
                                    <td>
                                        {g.actif ? (
                                            <StatusBadge label={g.statut} variant="info" />
                                        ) : (
                                            <StatusBadge label={t('Former teacher')} variant="secondary" />
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>

            {/* ── Historique des paiements ──────────────────────────── */}
            <Card title={t('My payments')} bodyClassName="p-0 py-3">
                <div className="table-responsive">
                    <table className="table table-nowrap mb-0">
                        <thead className="thead-light">
                            <tr>
                                <th>{t('Reference')}</th>
                                <th>{t('Date')}</th>
                                <th>{t('Period')}</th>
                                <th>{t('Group')}</th>
                                <th className="text-end">{t('Amount')}</th>
                                <th>{t('Status')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.paiements.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="text-center text-muted py-4">
                                        {t('No payment recorded yet.')}
                                    </td>
                                </tr>
                            )}
                            {data.paiements.map((p) => (
                                <tr key={p.id} className={p.statut === 'Refusée' || p.statut === 'Annulée' ? 'text-muted' : ''}>
                                    <td className="text-normal-case fw-semibold">{p.reference}</td>
                                    <td>{fmtDate(p.date)}</td>
                                    <td className="fs-13">
                                        {p.periodeDebut !== null ? `${fmtDate(p.periodeDebut)} → ${fmtDate(p.periodeFin)}` : '-'}
                                    </td>
                                    <td>
                                        {p.groupNom ?? '-'}
                                        {/* Ligne antérieure à la colonne enseignant_id :
                                            rattachée par le groupe, pas écrite sur la
                                            ligne. Le dire, sinon deux certitudes
                                            différentes se lisent pareil. */}
                                        {p.enseignantDeduit && (
                                            <span
                                                className="badge badge-soft-secondary ms-2 fs-12"
                                                title={t('Linked through the group (teacher not recorded on this line).')}
                                            >
                                                <i className="ti ti-link me-1" />
                                                {t('via group')}
                                            </span>
                                        )}
                                    </td>
                                    <td
                                        className={`text-end fw-semibold ${
                                            p.statut === 'Refusée' || p.statut === 'Annulée' ? 'text-decoration-line-through' : ''
                                        }`}
                                    >
                                        {p.montant.toFixed(2)} MAD
                                    </td>
                                    <td>
                                        <StatusBadge label={libelleStatut(p.statut)} variant={variantStatut(p.statut)} />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>
        </>
    );
}
