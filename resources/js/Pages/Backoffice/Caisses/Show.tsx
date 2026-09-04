import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DetailRow from '@/Components/Details/DetailRow';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import type { CaisseDetails } from '@/Types';

interface CaisseShowProps {
    caisse: CaisseDetails;
}

/**
 * Replaces resources/views/backoffice/caisses/show.blade.php exactly —
 * balance, 4 recent-movement lists (last 10 each, matching the original
 * Blade view's own inline query limit). Read-only, no create/update/delete.
 */
export default function CaisseShow({ caisse }: CaisseShowProps) {
    return (
        <BackofficeLayout
            title={caisse.nom}
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Caisses', href: '/backoffice/caisses' },
                { label: caisse.nom },
            ]}
        >
            <div className="row">
                <div className="col-xl-4">
                    <Card title="Caisse">
                        <div className="text-center border-bottom pb-3 mb-3">
                            <p className="text-muted mb-1">Solde</p>
                            <h3 className="mb-0">{Number(caisse.solde).toFixed(2)} DH</h3>
                        </div>
                        <DetailRow label="Centre" value={caisse.centre} />
                        <DetailRow label="Responsable" value={caisse.responsable} />
                        <div className="d-flex justify-content-between">
                            <span className="text-muted">Statut</span>
                            <span className={`badge ${caisse.statut === 'Active' ? 'badge-soft-success' : 'badge-soft-secondary'}`}>
                                {caisse.statut}
                            </span>
                        </div>
                    </Card>

                    <div className="alert alert-info d-flex align-items-center" role="alert">
                        Le solde est maintenu par l'application : il ne bouge qu'à travers les paiements, dépenses,
                        remboursements et transferts validés.
                    </div>
                </div>

                <div className="col-xl-8">
                    <Card
                        title="Paiements récents"
                        tools={<span className="badge badge-soft-secondary">{caisse.encaissements.length}</span>}
                    >
                        <RelatedRecordsTable
                            isEmpty={caisse.encaissements.length === 0}
                            emptyTitle="Aucun paiement"
                            emptyIcon="ti ti-cash"
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th>Étudiant</th>
                                    <th>Centre</th>
                                    <th>Date</th>
                                    <th>Méthode</th>
                                    <th className="text-end">Montant</th>
                                </tr>
                            }
                        >
                            {caisse.encaissements.map((row) => (
                                <tr key={row.reference}>
                                    <td>
                                        <code>{row.reference}</code>
                                    </td>
                                    <td>{row.label}</td>
                                    <td>{row.centre ?? '—'}</td>
                                    <td>{row.date ?? '—'}</td>
                                    <td>
                                        <span className="badge badge-soft-info">{row.extra}</span>
                                    </td>
                                    <td className="text-end fw-medium">{Number(row.montant).toFixed(2)} DH</td>
                                </tr>
                            ))}
                        </RelatedRecordsTable>
                    </Card>

                    <Card
                        title="Dépenses récentes"
                        tools={<span className="badge badge-soft-secondary">{caisse.depenses.length}</span>}
                    >
                        <RelatedRecordsTable
                            isEmpty={caisse.depenses.length === 0}
                            emptyTitle="Aucune dépense"
                            emptyIcon="ti ti-receipt"
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th>Type de dépense</th>
                                    <th>Date</th>
                                    <th className="text-end">Montant</th>
                                </tr>
                            }
                        >
                            {caisse.depenses.map((row) => (
                                <tr key={row.reference}>
                                    <td>
                                        <code>{row.reference}</code>
                                    </td>
                                    <td>{row.label}</td>
                                    <td>{row.date ?? '—'}</td>
                                    <td className="text-end fw-medium">{Number(row.montant).toFixed(2)} DH</td>
                                </tr>
                            ))}
                        </RelatedRecordsTable>
                    </Card>

                    <Card
                        title="Remboursements récents"
                        tools={<span className="badge badge-soft-secondary">{caisse.remboursements.length}</span>}
                    >
                        <RelatedRecordsTable
                            isEmpty={caisse.remboursements.length === 0}
                            emptyTitle="Aucun remboursement"
                            emptyIcon="ti ti-arrow-back-up"
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th>Bénéficiaire</th>
                                    <th>Date</th>
                                    <th className="text-end">Montant</th>
                                </tr>
                            }
                        >
                            {caisse.remboursements.map((row) => (
                                <tr key={row.reference}>
                                    <td>
                                        <code>{row.reference}</code>
                                    </td>
                                    <td>{row.label}</td>
                                    <td>{row.date ?? '—'}</td>
                                    <td className="text-end fw-medium">{Number(row.montant).toFixed(2)} DH</td>
                                </tr>
                            ))}
                        </RelatedRecordsTable>
                    </Card>

                    <Card
                        title="Transferts récents"
                        tools={<span className="badge badge-soft-secondary">{caisse.transfers.length}</span>}
                    >
                        <RelatedRecordsTable
                            isEmpty={caisse.transfers.length === 0}
                            emptyTitle="Aucun transfert"
                            emptyIcon="ti ti-transfer"
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th>Direction</th>
                                    <th>Contrepartie</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th className="text-end">Montant</th>
                                </tr>
                            }
                        >
                            {caisse.transfers.map((row) => (
                                <tr key={row.reference}>
                                    <td>
                                        <code>{row.reference}</code>
                                    </td>
                                    <td>
                                        <span
                                            className={`badge ${row.direction === 'out' ? 'badge-soft-danger' : 'badge-soft-success'}`}
                                        >
                                            {row.direction === 'out' ? 'Sortant' : 'Entrant'}
                                        </span>
                                    </td>
                                    <td>{row.label}</td>
                                    <td>{row.date ?? '—'}</td>
                                    <td>
                                        <span className="badge badge-soft-info">{row.statut}</span>
                                    </td>
                                    <td className="text-end fw-medium">{Number(row.montant).toFixed(2)} DH</td>
                                </tr>
                            ))}
                        </RelatedRecordsTable>
                    </Card>
                </div>
            </div>
        </BackofficeLayout>
    );
}
