import { useState } from 'react';
import { router } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import Card from '@/Components/Shared/Card';
import DetailRow from '@/Components/Details/DetailRow';
import EmptyState from '@/Components/Shared/EmptyState';
import type { EncaissementDetails } from '@/Types';

interface EncaissementShowProps {
    encaissement: EncaissementDetails;
    /** UI convenience only — the route + policy are the real gate (§5). */
    canDetach?: boolean;
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
export default function EncaissementShow({ encaissement, canDetach = false }: EncaissementShowProps) {
    const fee = encaissement.fee;
    const reste = fee ? Number(fee.reste) : 0;

    // Detaching makes a settled fee owed again, so it is confirmed rather
    // than fired on a single click.
    const [aDetacher, setADetacher] = useState<EncaissementDetails['applications'][number] | null>(null);
    const [processing, setProcessing] = useState(false);

    const detacher = () => {
        if (aDetacher === null) return;

        setProcessing(true);
        router.post(
            `/backoffice/encaissements/${aDetacher.id}/detach`,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setProcessing(false);
                    setADetacher(null);
                },
            },
        );
    };

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

                        {/* This row IS the application of an earlier avance — name it,
                            otherwise the receipt looks like fresh money arriving twice. */}
                        {encaissement.appliedFrom && (
                            <div className="d-flex justify-content-between mb-2">
                                <span className="text-muted">Provient de l&rsquo;avance</span>
                                <a href={encaissement.appliedFrom.showUrl} className="fw-medium">
                                    {encaissement.appliedFrom.reference}
                                </a>
                            </div>
                        )}

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
                    <Card title={fee === null && encaissement.isAvance ? 'Avance' : 'Frais réglé'}>
                        {fee === null && encaissement.isAvance ? (
                            // An avance settles no fee YET, so « Aucun frais lié » was the
                            // whole page — true but useless. Show what the money is doing:
                            // how much is used, how much is still available, and every fee
                            // it was applied to.
                            <>
                                <div className="d-flex justify-content-between mb-2">
                                    <span className="text-muted">Montant utilisé</span>
                                    <span className="fw-semibold">
                                        {Number(encaissement.montantUtilise).toFixed(2)} MAD
                                    </span>
                                </div>
                                <div className="d-flex justify-content-between border-top pt-2 mb-3">
                                    <span className="text-muted">Montant restant</span>
                                    <span
                                        className={`fw-semibold ${
                                            Number(encaissement.montantRestant) > 0 ? 'text-success' : 'text-muted'
                                        }`}
                                    >
                                        {Number(encaissement.montantRestant).toFixed(2)} MAD
                                    </span>
                                </div>

                                {encaissement.applications.length === 0 ? (
                                    // Detached lines are filtered out server-side, so an
                                    // avance whose applications were all detached lands
                                    // here too — « pas encore appliquée » would be wrong.
                                    <EmptyState
                                        title={
                                            Number(encaissement.montantUtilise) > 0
                                                ? 'Aucun frais réglé par cette avance'
                                                : 'Avance non encore appliquée'
                                        }
                                        icon="ti ti-clock-dollar"
                                    />
                                ) : (
                                    <>
                                        <span className="text-muted d-block mb-2">Frais réglés par cette avance</span>
                                        {encaissement.applications.map((application) => (
                                            <div key={application.reference} className="border-top pt-2 mb-2">
                                                <div className="d-flex justify-content-between">
                                                    <span className="fw-medium">{application.frais ?? '—'}</span>
                                                    <span className="fw-semibold">
                                                        {Number(application.montant).toFixed(2)} MAD
                                                    </span>
                                                </div>
                                                <div className="d-flex justify-content-between align-items-center">
                                                    <div className="text-muted fs-12">
                                                        {application.groupe && `${application.groupe} — `}
                                                        {application.date}
                                                        {' · '}
                                                        <a href={application.showUrl}>{application.reference}</a>
                                                    </div>
                                                    {canDetach && application.detachable && (
                                                        <button
                                                            type="button"
                                                            className="btn btn-sm btn-outline-danger py-0 px-2 fs-12"
                                                            onClick={() => setADetacher(application)}
                                                        >
                                                            <i className="ti ti-unlink me-1" />
                                                            Détacher
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </>
                                )}
                            </>
                        ) : fee === null ? (
                            <EmptyState title="Aucun frais lié" icon="ti ti-receipt" />
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
            <ConfirmDialog
                show={aDetacher !== null}
                title="Détacher ce paiement de son frais"
                recordLabel={
                    aDetacher === null
                        ? ''
                        : `${aDetacher.frais ?? 'Frais'} — ${Number(aDetacher.montant).toFixed(2)} MAD`
                }
                message="Le frais redeviendra dû et le montant retournera en avance réapplicable. Aucun montant ne quitte la caisse et rien n'est supprimé."
                icon="ti-unlink"
                confirmLabel="Détacher"
                processingLabel="Détachement…"
                processing={processing}
                onConfirm={detacher}
                onCancel={() => setADetacher(null)}
            />
        </BackofficeLayout>
    );
}
