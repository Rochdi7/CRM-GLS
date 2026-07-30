import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DetailRow from '@/Components/Details/DetailRow';
import StatusBadge from '@/Components/Details/StatusBadge';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import type { InscriptionDetails } from '@/Types';

interface InscriptionShowProps {
    inscription: InscriptionDetails;
}

function feeStatusVariant(statut: string): 'success' | 'warning' | 'danger' {
    if (statut === 'Payé') return 'success';
    if (statut === 'Payé partiellement') return 'warning';
    return 'danger';
}

/**
 * Replaces resources/views/backoffice/inscriptions/show.blade.php exactly
 * — registration summary, payment summary (due/paid/remaining, all
 * server-computed), fee-lines table. Read-only, no edit controls.
 */
export default function InscriptionShow({ inscription }: InscriptionShowProps) {
    const reste = Number(inscription.reste);

    return (
        <BackofficeLayout
            title={`Inscription ${inscription.reference}`}
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Inscriptions', href: '/backoffice/inscriptions' },
                { label: inscription.reference },
            ]}
        >
            <div className="row">
                <div className="col-xl-4">
                    <Card title="Inscription">
                        <div className="d-flex justify-content-between mb-2">
                            <span className="text-muted">Étudiant</span>
                            {inscription.studentShowUrl ? (
                                <a href={inscription.studentShowUrl} className="fw-medium">
                                    {inscription.student}
                                </a>
                            ) : (
                                <span className="fw-medium">—</span>
                            )}
                        </div>
                        <DetailRow label="Groupe" value={inscription.groupe} />
                        <DetailRow label="Année scolaire" value={inscription.anneeScolaire} />
                        <DetailRow label="Date" value={inscription.date} />
                        <div className="d-flex justify-content-between">
                            <span className="text-muted">Statut</span>
                            <StatusBadge label={inscription.statut} />
                        </div>
                    </Card>

                    <Card title="Résumé des paiements">
                        <div className="d-flex justify-content-between mb-2">
                            <span className="text-muted">Total dû</span>
                            <span className="fw-semibold">{Number(inscription.totalDu).toFixed(2)} MAD</span>
                        </div>
                        <div className="d-flex justify-content-between mb-2">
                            <span className="text-muted">Payé</span>
                            <span className="fw-semibold text-success">
                                {Number(inscription.totalPaye).toFixed(2)} MAD
                            </span>
                        </div>
                        <div className="d-flex justify-content-between border-top pt-2">
                            <span className="text-muted">Restant</span>
                            <span className={`fw-semibold ${reste > 0 ? 'text-danger' : 'text-success'}`}>
                                {reste.toFixed(2)} MAD
                            </span>
                        </div>
                    </Card>
                </div>

                <div className="col-xl-8">
                    <Card title="Lignes de frais">
                        <RelatedRecordsTable
                            isEmpty={inscription.fees.length === 0}
                            emptyTitle="Aucune ligne de frais"
                            emptyIcon="ti ti-receipt"
                            head={
                                <tr>
                                    <th>Nom du frais</th>
                                    <th>Montant</th>
                                    <th>Payé</th>
                                    <th>Échéance</th>
                                    <th>Statut</th>
                                </tr>
                            }
                        >
                            {inscription.fees.map((fee) => (
                                <tr key={fee.nom}>
                                    <td className="fw-medium">{fee.nom}</td>
                                    <td>{Number(fee.montant).toFixed(2)} MAD</td>
                                    <td>{Number(fee.paye).toFixed(2)} MAD</td>
                                    <td>{fee.dateEcheance ?? '—'}</td>
                                    <td>
                                        <span className={`badge badge-soft-${feeStatusVariant(fee.statut)}`}>
                                            {fee.statut}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </RelatedRecordsTable>
                    </Card>
                </div>
            </div>
        </BackofficeLayout>
    );
}
