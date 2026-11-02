import { router } from '@inertiajs/react';
import { useState } from 'react';

interface SystemePanelProps {
    /** Current switch values, as stored (AppSettings). */
    systeme: { expenseApproval: boolean; refundApproval: boolean };
    /** UI convenience only — the controller re-checks system-settings.update. */
    permissions: { create: boolean; update: boolean; delete: boolean };
}

/**
 * Paramètres → Système — application-wide switches.
 *
 * « Validation des dépenses » is the one that matters for money: while it is
 * ON, a new dépense is created "En attente" and debits NOTHING — the amount
 * stays in the till until a super-admin approves it (the debit happens then)
 * or refuses it (no movement ever). Turning it OFF restores the legacy
 * behavior where recording an expense debits the till immediately.
 *
 * ⚠ Turning it OFF does NOT release expenses that are already pending: those
 * never debited anything, so silently approving them would move cash nobody
 * decided on. They keep waiting for a decision.
 *
 * « Validation des remboursements » (02/11/2026) is the same switch for
 * refunds: ON, a refund is a REQUEST approved by a director (in their
 * centres) or a super-admin, and only the approval debits a till.
 */
export default function SystemePanel({ systeme, permissions }: SystemePanelProps) {
    const [saving, setSaving] = useState(false);

    function toggle(field: 'expense_approval' | 'refund_approval', next: boolean) {
        if (!permissions.update || saving) {
            return;
        }

        setSaving(true);
        router.put(
            '/backoffice/settings/system',
            { [field]: next },
            {
                preserveScroll: true,
                onFinish: () => setSaving(false),
            },
        );
    }

    return (
        <div className="px-3 pb-3">
            <div className="card border mb-0">
                <div className="card-body">
                    <div className="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <h5 className="mb-1">Validation des dépenses</h5>
                            <p className="text-muted mb-0">
                                Lorsque cette option est activée, toute nouvelle dépense est enregistrée
                                « En attente » et <strong>aucun montant n’est retiré de la caisse</strong>.
                                Le montant reste bloqué jusqu’à ce qu’un super-administrateur approuve la
                                demande (la caisse est débitée à ce moment-là) ou la refuse (aucun
                                mouvement).
                            </p>
                            <p className="text-muted fs-13 mb-0 mt-2">
                                <i className="ti ti-info-circle me-1" />
                                Désactiver cette option ne libère pas les dépenses déjà en attente : elles
                                doivent toujours être approuvées ou refusées.
                            </p>
                        </div>

                        <div className="form-check form-switch fs-18">
                            <input
                                className="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="sys-expense-approval"
                                checked={systeme.expenseApproval}
                                disabled={!permissions.update || saving}
                                onChange={(event) => toggle('expense_approval', event.target.checked)}
                            />
                            <label className="form-check-label fs-14 ms-2" htmlFor="sys-expense-approval">
                                {systeme.expenseApproval ? 'Activée' : 'Désactivée'}
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div className="card border mb-0 mt-3">
                <div className="card-body">
                    <div className="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <h5 className="mb-1">Validation des remboursements</h5>
                            <p className="text-muted mb-0">
                                Lorsque cette option est activée, tout nouveau remboursement est une
                                <strong> demande</strong> : <strong>aucun montant n’est retiré de la caisse</strong>{' '}
                                tant qu’un directeur (pour ses centres) ou un super-administrateur ne l’a pas
                                approuvée. À l’approbation, l’argent sort en espèces de la caisse de l’employé
                                qui a fait la demande ; seul un super-administrateur peut choisir TPE ou
                                virement (compte du centre).
                            </p>
                            <p className="text-muted fs-13 mb-0 mt-2">
                                <i className="ti ti-info-circle me-1" />
                                Désactiver cette option ne libère pas les demandes déjà en attente : elles
                                doivent toujours être approuvées ou refusées.
                            </p>
                        </div>

                        <div className="form-check form-switch fs-18">
                            <input
                                className="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="sys-refund-approval"
                                checked={systeme.refundApproval}
                                disabled={!permissions.update || saving}
                                onChange={(event) => toggle('refund_approval', event.target.checked)}
                            />
                            <label className="form-check-label fs-14 ms-2" htmlFor="sys-refund-approval">
                                {systeme.refundApproval ? 'Activée' : 'Désactivée'}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
