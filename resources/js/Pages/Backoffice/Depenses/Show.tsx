import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DetailRow from '@/Components/Details/DetailRow';
import EmptyState from '@/Components/Shared/EmptyState';
import DocumentLink from '@/Components/Media/DocumentLink';
import type { DepenseDetails } from '@/Types';

interface DepenseShowProps {
    depense: DepenseDetails;
    /**
     * `expenses.approve` (super-admin only in practice). Gates the operation
     * trail; when false the server already omitted those fields entirely.
     */
    canAudit: boolean;
}

/**
 * Replaces resources/views/backoffice/depenses/show.blade.php exactly —
 * summary card, details card, receipts gallery. No delete action anywhere
 * (an expense is never deleted). Receipt URLs come only from the
 * already-authorized Spatie Media URL, never a filesystem path.
 */
export default function DepenseShow({ depense, canAudit }: DepenseShowProps) {
    return (
        <BackofficeLayout
            title={depense.reference}
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Dépenses', href: '/backoffice/depenses' },
                { label: depense.reference },
            ]}
            actions={
                depense.canViewList ? (
                    <a href="/backoffice/depenses" className="btn btn-light d-flex align-items-center">
                        <i className="ti ti-arrow-left me-2" />
                        Retour à la liste
                    </a>
                ) : undefined
            }
        >
            {/* Correction comptable — a cancelled dépense keeps its row (money
                records are append-only); the compensating credit that put
                the money back is shown here, read from the caisse journal. */}
            {depense.isAnnulee && (
                <div className="alert alert-secondary d-flex align-items-start mb-4">
                    <i className="ti ti-receipt-refund fs-20 me-3 mt-1" />
                    <div className="flex-fill">
                        <h6 className="mb-2">Correction comptable — dépense annulée</h6>
                        <div className="row g-2">
                            <div className="col-md-3">
                                <span className="text-muted d-block">Référence</span>
                                <code>{depense.annulation?.correction ?? '—'}</code>
                            </div>
                            <div className="col-md-3">
                                <span className="text-muted d-block">Caisse recréditée</span>
                                <span className="fw-medium text-success">
                                    + {Number(depense.annulation?.montant ?? depense.montant).toFixed(2)} MAD
                                </span>
                            </div>
                            <div className="col-md-3">
                                <span className="text-muted d-block">Le</span>
                                <span className="fw-medium">{depense.annulation?.date ?? '—'}</span>
                            </div>
                            <div className="col-md-3">
                                <span className="text-muted d-block">Par</span>
                                <span className="fw-medium">{depense.annulation?.par ?? '—'}</span>
                            </div>
                            {depense.annulation?.motif && (
                                <div className="col-12">
                                    <span className="text-muted d-block">Motif</span>
                                    <span className="fw-medium">{depense.annulation.motif}</span>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}

            <div className="row">
                <div className="col-xl-4">
                    <Card>
                        <div className="text-center mb-3">
                            <span className="avatar avatar-xxl rounded-circle bg-danger-transparent d-inline-flex align-items-center justify-content-center mb-2">
                                <i className="ti ti-cash-banknote fs-24 text-danger" />
                            </span>
                            <h5 className={`mb-1 ${depense.isAnnulee ? 'text-muted text-decoration-line-through' : 'text-danger'}`}>
                                - {Number(depense.montant).toFixed(2)} MAD
                            </h5>
                            <p className="text-muted mb-2">
                                <code>{depense.reference}</code>
                            </p>
                            {depense.typeDepense && <span className="badge badge-soft-info">{depense.typeDepense}</span>}
                            {depense.isAnnulee && <span className="badge badge-soft-secondary ms-1">Annulée</span>}
                        </div>
                        <div className="border-top pt-3">
                            <DetailRow label="Date de la dépense" value={depense.dateDepense} />
                            {/* « Paiement prof » only — the teaching period
                                the payment covers. */}
                            {depense.periodeDebut && depense.periodeFin && (
                                <DetailRow
                                    label="Période payée"
                                    value={`${depense.periodeDebut} → ${depense.periodeFin}`}
                                />
                            )}
                            <DetailRow label="Caisse" value={depense.caisse} />
                            <DetailRow label="Centre" value={depense.centre} />
                            <DetailRow label="Enregistré par" value={depense.agent} />
                            <DetailRow label="Enregistré le" value={depense.recordedAt} />
                            {/* Operation trail — super-admin only. The server
                                sends createdAt/updatedAt to nobody else
                                (DepenseController::show), so this simply does
                                not render for them. */}
                            {canAudit && (
                                <>
                                    <DetailRow label="Date d'opération (création)" value={depense.createdAt} />
                                    <DetailRow
                                        label="Date d'opération (modification)"
                                        value={depense.wasEdited ? depense.updatedAt : 'Jamais modifiée'}
                                    />
                                </>
                            )}
                        </div>
                    </Card>
                </div>

                <div className="col-xl-8">
                    <Card title="Détails">
                        <div className="mb-3">
                            <span className="text-muted d-block mb-1">Description</span>
                            <span className="fw-medium">{depense.description || '—'}</span>
                        </div>
                        <div className="mb-3">
                            <span className="text-muted d-block mb-1">Mots-clés</span>
                            {depense.motsCles.length > 0 ? (
                                depense.motsCles.map((mot) => (
                                    <span className="badge badge-soft-secondary me-1" key={mot}>
                                        {mot}
                                    </span>
                                ))
                            ) : (
                                <span className="fw-medium">—</span>
                            )}
                        </div>
                        <div>
                            <span className="text-muted d-block mb-1">Note</span>
                            <span className="fw-medium">{depense.note || '—'}</span>
                        </div>
                    </Card>

                    <Card
                        title="Justificatifs"
                        tools={<span className="badge badge-soft-secondary">{depense.receipts.length}</span>}
                    >
                        {depense.receipts.length === 0 ? (
                            <EmptyState
                                title="Aucun justificatif joint"
                                message="Cette dépense n'a pas de document justificatif."
                                icon="ti ti-paperclip"
                            />
                        ) : (
                            <div className="row g-3">
                                {depense.receipts.map((file) => (
                                    <div className="col-md-4 col-sm-6" key={file.url}>
                                        <DocumentLink file={file} />
                                    </div>
                                ))}
                            </div>
                        )}
                    </Card>
                </div>
            </div>
        </BackofficeLayout>
    );
}
