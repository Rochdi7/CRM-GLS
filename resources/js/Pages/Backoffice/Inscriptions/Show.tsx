import { useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DetailRow from '@/Components/Details/DetailRow';
import StatusBadge from '@/Components/Details/StatusBadge';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import LocalPagination from '@/Components/Tables/LocalPagination';
import type { InscriptionDetails } from '@/Types';

interface InscriptionShowProps {
    inscription: InscriptionDetails;
}

/**
 * A group can assign a dozen-plus catalog fees (one per month plus exam and
 * inscription fees), which made the "Lignes de frais" card dwarf the rest of
 * the page. The lines arrive whole as an Inertia prop, so paging them is
 * purely client-side — no extra round-trip, and the payment summary above
 * still totals every line, not just the visible page.
 */
const FEES_PER_PAGE = 5;

function feeStatusVariant(statut: string): 'success' | 'warning' | 'danger' {
    if (statut === 'Payé') return 'success';
    if (statut === 'Payé partiellement') return 'warning';
    return 'danger';
}

/**
 * Replaces resources/views/backoffice/inscriptions/show.blade.php — the top
 * "Informations" card lays its fields out as label/value pairs across two
 * columns (Référence/Statut, Étudiant/Groupe, Année scolaire/Enseignant,
 * Date d'inscription/Date de fin), followed by the payment summary, the fee
 * lines table and the full payments history (GetInscriptionPayments). All
 * totals (due/paid/remaining) are server-computed. Read-only, no edit
 * controls.
 */
export default function InscriptionShow({ inscription }: InscriptionShowProps) {
    const reste = Number(inscription.reste);
    const [feesPage, setFeesPage] = useState(1);

    const feesPageCount = Math.max(1, Math.ceil(inscription.fees.length / FEES_PER_PAGE));
    // Clamped rather than stored, so the page can never point past the end.
    const currentFeesPage = Math.min(feesPage, feesPageCount);
    const visibleFees = inscription.fees.slice(
        (currentFeesPage - 1) * FEES_PER_PAGE,
        currentFeesPage * FEES_PER_PAGE,
    );

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
                <div className="col-12">
                    <Card title="Inscription">
                        <div className="row">
                            <div className="col-md-6">
                                <DetailRow label="Référence" value={<code>{inscription.reference}</code>} />
                                <DetailRow
                                    label="Étudiant"
                                    value={
                                        inscription.studentShowUrl ? (
                                            <a href={inscription.studentShowUrl}>
                                                {inscription.student} <i className="ti ti-external-link fs-14" />
                                            </a>
                                        ) : (
                                            inscription.student
                                        )
                                    }
                                />
                                <DetailRow label="Année scolaire" value={inscription.anneeScolaire} />
                                <DetailRow label="Date d'inscription" value={inscription.dateDebut ?? inscription.date} />
                            </div>
                            <div className="col-md-6">
                                <div className="d-flex justify-content-between mb-2">
                                    <span className="text-muted">Statut</span>
                                    <StatusBadge label={inscription.statut} />
                                </div>
                                <DetailRow
                                    label="Groupe"
                                    value={
                                        inscription.groupShowUrl ? (
                                            <a href={inscription.groupShowUrl}>
                                                {inscription.groupe} <i className="ti ti-external-link fs-14" />
                                            </a>
                                        ) : (
                                            inscription.groupe
                                        )
                                    }
                                />
                                <DetailRow label="Enseignant" value={inscription.enseignant} />
                                <DetailRow label="Date de fin" value={inscription.dateFin} />
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <div className="row">
                <div className="col-xl-4">
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
                                    <th>Reste</th>
                                    <th>Échéance</th>
                                    <th>Statut</th>
                                </tr>
                            }
                        >
                            {visibleFees.map((fee) => {
                                // Derived here rather than server-side: montant and paye are
                                // both already on the prop, so a `reste` key would be redundant
                                // state that could drift from them.
                                const reste = Math.max(0, Number(fee.montant) - Number(fee.paye));

                                return (
                                <tr key={fee.nom}>
                                    <td className="fw-medium">{fee.nom}</td>
                                    <td>{Number(fee.montant).toFixed(2)} MAD</td>
                                    <td>{Number(fee.paye).toFixed(2)} MAD</td>
                                    <td className={`fw-semibold ${reste > 0 ? 'text-danger' : 'text-success'}`}>
                                        {reste.toFixed(2)} MAD
                                    </td>
                                    <td>{fee.dateEcheance ?? '—'}</td>
                                    <td>
                                        <span className={`badge badge-soft-${feeStatusVariant(fee.statut)}`}>
                                            {fee.statut}
                                        </span>
                                    </td>
                                </tr>
                                );
                            })}
                        </RelatedRecordsTable>
                        <LocalPagination
                            total={inscription.fees.length}
                            perPage={FEES_PER_PAGE}
                            page={currentFeesPage}
                            pageCount={feesPageCount}
                            onPageChange={setFeesPage}
                        />
                    </Card>
                </div>
            </div>

            <div className="row">
                <div className="col-12">
                    <Card title="Paiements">
                        <RelatedRecordsTable
                            isEmpty={inscription.payments.length === 0}
                            emptyTitle="Aucun paiement enregistré"
                            emptyIcon="ti ti-cash-banknote"
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th>Frais</th>
                                    <th>Montant</th>
                                    <th>Méthode</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            }
                        >
                            {inscription.payments.map((payment) => (
                                <tr key={payment.id}>
                                    <td>
                                        <code>{payment.reference}</code>
                                    </td>
                                    <td>{payment.feeNom ?? '—'}</td>
                                    <td className="fw-medium">{Number(payment.montant).toFixed(2)} MAD</td>
                                    <td>{payment.methode}</td>
                                    <td>{payment.datePaiement ?? '—'}</td>
                                    <td>
                                        {payment.rembourse ? (
                                            <span className="badge badge-soft-warning">Remboursé</span>
                                        ) : (
                                            <span className="badge badge-soft-success">Encaissé</span>
                                        )}
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
