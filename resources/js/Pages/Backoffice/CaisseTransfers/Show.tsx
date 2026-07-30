import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DetailRow from '@/Components/Details/DetailRow';
import type { CaisseTransferDetails } from '@/Types';

interface CaisseTransferShowProps {
    transfer: CaisseTransferDetails;
}

function statusVariant(statut: string): 'success' | 'secondary' | 'warning' {
    if (statut === 'Validé') return 'success';
    if (statut === 'Annulé') return 'secondary';
    return 'warning';
}

/**
 * Replaces resources/views/backoffice/caisse-transfers/show.blade.php
 * exactly — transfer summary, approval trail, before/after balance
 * snapshots. No action here: validation/cancellation live on the Livewire
 * caisse-transfers index tab (two-step workflow, structure doc §7).
 */
export default function CaisseTransferShow({ transfer }: CaisseTransferShowProps) {
    return (
        <BackofficeLayout
            title={`Transfert ${transfer.reference}`}
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Transferts de caisse', href: '/backoffice/caisse-transfers' },
                { label: transfer.reference },
            ]}
        >
            <div className="row">
                <div className="col-xl-5">
                    <Card title="Transfert">
                        <DetailRow label="Référence" value={transfer.reference} />
                        <DetailRow label="Caisse source" value={transfer.caisseSource} />
                        <DetailRow label="Caisse destination" value={transfer.caisseDestination} />
                        <div className="d-flex justify-content-between mb-2">
                            <span className="text-muted">Montant</span>
                            <span className="fw-semibold">{Number(transfer.montant).toFixed(2)} DH</span>
                        </div>
                        <DetailRow label="Date" value={transfer.date} />
                        <div className="d-flex justify-content-between">
                            <span className="text-muted">Statut</span>
                            <span className={`badge badge-soft-${statusVariant(transfer.statut)}`}>
                                {transfer.statut}
                            </span>
                        </div>
                    </Card>

                    <Card title="Suivi d'approbation">
                        <DetailRow label="Demandé par" value={transfer.requestedBy} />
                        <DetailRow label="Validé par" value={transfer.validatedBy ?? 'Pas encore validé'} />
                        <div className="d-flex justify-content-between">
                            <span className="text-muted">Note</span>
                            <span className="fw-medium text-end">{transfer.note ?? '—'}</span>
                        </div>
                    </Card>
                </div>

                <div className="col-xl-7">
                    <Card title="Instantanés des soldes">
                        {transfer.isPending && (
                            <div className="alert alert-warning" role="alert">
                                Les soldes n'ont pas encore bougé — ce transfert est en attente de validation par un
                                autre employé.
                            </div>
                        )}

                        <div className="table-responsive">
                            <table className="table">
                                <thead className="thead-light">
                                    <tr>
                                        <th>Caisse</th>
                                        <th className="text-end">Solde avant</th>
                                        <th className="text-end">Solde après</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td className="fw-medium">{transfer.caisseSource ?? '—'}</td>
                                        <td className="text-end">
                                            {transfer.soldeSourceAvant === null
                                                ? '—'
                                                : `${Number(transfer.soldeSourceAvant).toFixed(2)} DH`}
                                        </td>
                                        <td className="text-end">
                                            {transfer.soldeSourceApres === null
                                                ? '—'
                                                : `${Number(transfer.soldeSourceApres).toFixed(2)} DH`}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td className="fw-medium">{transfer.caisseDestination ?? '—'}</td>
                                        <td className="text-end">
                                            {transfer.soldeDestAvant === null
                                                ? '—'
                                                : `${Number(transfer.soldeDestAvant).toFixed(2)} DH`}
                                        </td>
                                        <td className="text-end">
                                            {transfer.soldeDestApres === null
                                                ? '—'
                                                : `${Number(transfer.soldeDestApres).toFixed(2)} DH`}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>
            </div>
        </BackofficeLayout>
    );
}
