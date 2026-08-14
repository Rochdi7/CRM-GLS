import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DetailRow from '@/Components/Details/DetailRow';
import EmptyState from '@/Components/Shared/EmptyState';
import type { EncaissementDetails } from '@/Types';

interface EncaissementShowProps {
    encaissement: EncaissementDetails;
}

function feeStatusVariant(statut: string): 'success' | 'warning' | 'danger' {
    if (statut === 'Payé') return 'success';
    if (statut === 'Payé partiellement') return 'warning';
    return 'danger';
}

/**
 * Replaces resources/views/backoffice/encaissements/show.blade.php exactly
 * — receipt card + fee-settled card. A payment is never deleted and its
 * amount/till are frozen — no destructive action anywhere on this page.
 */
export default function EncaissementShow({ encaissement }: EncaissementShowProps) {
    const fee = encaissement.fee;
    const reste = fee ? Number(fee.reste) : 0;

    return (
        <BackofficeLayout
            title={`Paiement ${encaissement.reference}`}
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Paiements', href: '/backoffice/encaissements' },
                { label: encaissement.reference },
            ]}
        >
            <div className="row">
                <div className="col-xl-7">
                    <Card title="Reçu">
                        <div className="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 className="mb-1">{Number(encaissement.montant).toFixed(2)} MAD</h4>
                                <p className="text-muted mb-0">Reçu le {encaissement.date ?? '—'}</p>
                            </div>
                            <span className="badge badge-soft-info">{encaissement.methode}</span>
                        </div>

                        <div className="d-flex justify-content-between mb-2">
                            <span className="text-muted">Référence</span>
                            <span className="fw-medium">
                                <code>{encaissement.reference}</code>
                            </span>
                        </div>
                        <div className="d-flex justify-content-between mb-2">
                            <span className="text-muted">Étudiant</span>
                            {encaissement.studentShowUrl ? (
                                <a href={encaissement.studentShowUrl} className="fw-medium">
                                    {encaissement.student}
                                </a>
                            ) : (
                                <span className="fw-medium">—</span>
                            )}
                        </div>
                        <div className="d-flex justify-content-between mb-2">
                            <span className="text-muted">Inscription</span>
                            {encaissement.inscriptionShowUrl ? (
                                <a href={encaissement.inscriptionShowUrl} className="fw-medium">
                                    {encaissement.inscriptionReference}
                                </a>
                            ) : (
                                <span className="fw-medium">—</span>
                            )}
                        </div>
                        <DetailRow label="Groupe" value={encaissement.groupe} />
                        <DetailRow label="Caisse" value={encaissement.caisse} />
                        <DetailRow label="Enregistré par" value={encaissement.agent} />

                        {encaissement.cheque && (
                            <div className="border-top pt-2 mt-2">
                                <DetailRow label="Numéro de chèque" value={encaissement.cheque.numero} />
                                <DetailRow label="Banque" value={encaissement.cheque.banque} />
                                <DetailRow label="Échéance du chèque" value={encaissement.cheque.dateEcheance} />
                            </div>
                        )}

                        {encaissement.note && (
                            <div className="border-top pt-2 mt-2">
                                <span className="text-muted d-block mb-1">Note</span>
                                <p className="mb-0">{encaissement.note}</p>
                            </div>
                        )}
                    </Card>
                </div>

                <div className="col-xl-5">
                    <Card title="Frais réglé">
                        {fee === null ? (
                            <EmptyState title="Aucun frais lié" icon="fa fa-receipt" />
                        ) : (
                            <>
                                <DetailRow label="Nom du frais" value={fee.nom} />
                                <DetailRow label="Échéance" value={fee.dateEcheance} />
                                <div className="d-flex justify-content-between mb-2">
                                    <span className="text-muted">Total dû</span>
                                    <span className="fw-semibold">{Number(fee.totalDu).toFixed(2)} MAD</span>
                                </div>
                                <div className="d-flex justify-content-between mb-2">
                                    <span className="text-muted">Payé</span>
                                    <span className="fw-semibold text-success">
                                        {Number(fee.totalPaye).toFixed(2)} MAD
                                    </span>
                                </div>
                                <div className="d-flex justify-content-between border-top pt-2 mb-2">
                                    <span className="text-muted">Restant</span>
                                    <span className={`fw-semibold ${reste > 0 ? 'text-danger' : 'text-success'}`}>
                                        {reste.toFixed(2)} MAD
                                    </span>
                                </div>
                                <div className="d-flex justify-content-between">
                                    <span className="text-muted">Statut</span>
                                    <span className={`badge badge-soft-${feeStatusVariant(fee.statut)}`}>
                                        {fee.statut}
                                    </span>
                                </div>
                            </>
                        )}
                    </Card>
                </div>
            </div>
        </BackofficeLayout>
    );
}
