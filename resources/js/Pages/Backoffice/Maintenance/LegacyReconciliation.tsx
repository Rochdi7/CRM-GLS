import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import SelectField from '@/Components/Forms/SelectField';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import { t } from '@/Lib/i18n';
import type { SelectOption } from '@/Types';

type Resultat = {
    apply: boolean;
    sortie: string;
    succes: boolean;
    lancee_le: string;
};

type Props = {
    etablissements: SelectOption[];
    dossierDefaut: string;
    resultat: Resultat | null;
};

/**
 * « Réconciliation des paiements importés » — l'interface de
 * `paiements:reconcilier` (docs/legacy-import-cli.md).
 *
 * Écran de MAINTENANCE, atteint par son lien direct
 * (/backoffice/reconciliation-paiements) et réservé au compte de
 * maintenance — une identité, pas une permission (§16). Comme
 * « Échéances en masse », son absence de la barre latérale n'est PAS ce qui
 * le protège : le gate serveur décide.
 *
 * Deux boutons, deux gestes distincts : « Simuler » n'écrit jamais rien,
 * « Appliquer » passe par une confirmation. Le défaut est toujours la
 * simulation, comme en ligne de commande.
 */
export default function LegacyReconciliation({ etablissements, dossierDefaut, resultat }: Props) {
    const [confirmVisible, setConfirmVisible] = useState(false);

    const form = useForm({
        dossier: dossierDefaut,
        centre: '',
        etudiant: '',
        apply: false as boolean,
    });

    function lancer(apply: boolean) {
        form.transform((data) => ({ ...data, apply }));
        form.post('/backoffice/reconciliation-paiements', {
            preserveScroll: true,
            onFinish: () => {
                form.transform((data) => data);
                setConfirmVisible(false);
            },
        });
    }

    const centreOptions: SelectOption[] = [
        { value: '', label: t('All centres') },
        ...etablissements,
    ];

    return (
        <BackofficeLayout title={t('Reconcile imported payments')}>
            <div className="row">
                <div className="col-xl-5">
                    <Card title={t('Reconcile imported payments')}>
                        <p className="text-muted fs-13 mb-3">
                            {t(
                                "Compares the old CRM export against the payments in the database and re-attaches each payment to the fee its source file names.",
                            )}
                        </p>

                        <div className="alert alert-light border fs-13 mb-4">
                            <strong className="d-block mb-1">{t('What this never changes')}</strong>
                            <span className="text-muted">
                                {t(
                                    'Amount, payment date, method, till and till balance are read-only. No money record is ever deleted, and a closed academic year is refused.',
                                )}
                            </span>
                        </div>

                        <div className="mb-3">
                            <label className="form-label">{t('Export folder')}</label>
                            <input
                                type="text"
                                className={`form-control ${form.errors.dossier ? 'is-invalid' : ''}`}
                                value={form.data.dossier}
                                onChange={(e) => form.setData('dossier', e.target.value)}
                            />
                            {form.errors.dossier && (
                                <div className="invalid-feedback">{form.errors.dossier}</div>
                            )}
                            <div className="form-text fs-12">
                                {t('Must contain "old data/" and "active data/".')}
                            </div>
                        </div>

                        <SelectField
                            id="centre"
                            label={t('Centre')}
                            value={form.data.centre}
                            options={centreOptions}
                            error={form.errors.centre}
                            onChange={(e) => form.setData('centre', e.target.value)}
                        />

                        <div className="mb-3">
                            <label className="form-label">{t('Student')}</label>
                            <input
                                type="text"
                                className={`form-control text-normal-case ${form.errors.etudiant ? 'is-invalid' : ''}`}
                                value={form.data.etudiant}
                                placeholder={t('e.g. E812 or ETU-695 — leave empty for all')}
                                onChange={(e) => form.setData('etudiant', e.target.value)}
                            />
                            {form.errors.etudiant && (
                                <div className="invalid-feedback">{form.errors.etudiant}</div>
                            )}
                        </div>

                        <div className="d-flex gap-2 mt-4">
                            <button
                                type="button"
                                className="btn btn-outline-primary"
                                disabled={form.processing}
                                onClick={() => lancer(false)}
                            >
                                <i className="ti ti-eye me-1" />
                                {t('Simulate')}
                            </button>
                            <button
                                type="button"
                                className="btn btn-primary"
                                disabled={form.processing}
                                onClick={() => setConfirmVisible(true)}
                            >
                                <i className="ti ti-check me-1" />
                                {t('Apply')}
                            </button>
                        </div>
                    </Card>
                </div>

                <div className="col-xl-7">
                    <Card
                        title={
                            resultat
                                ? `${resultat.apply ? t('Applied') : t('Simulation')} — ${resultat.lancee_le}`
                                : t('Result')
                        }
                    >
                        {resultat ? (
                            <pre
                                className="bg-dark text-light p-3 rounded fs-13 mb-0 text-normal-case"
                                style={{ maxHeight: '70vh', overflow: 'auto', whiteSpace: 'pre-wrap' }}
                            >
                                {resultat.sortie || t('No output.')}
                            </pre>
                        ) : (
                            <p className="text-muted fs-13 mb-0">
                                {t('Run a simulation to see what would change.')}
                            </p>
                        )}
                    </Card>
                </div>
            </div>

            <ConfirmDialog
                show={confirmVisible}
                title={t('Apply the corrections?')}
                recordLabel={
                    form.data.etudiant !== ''
                        ? form.data.etudiant
                        : centreOptions.find((o) => o.value === form.data.centre)?.label ?? t('All centres')
                }
                message={t(
                    'Payments will be re-attached to the fees their source file names. Make a database backup first.',
                )}
                confirmLabel={t('Apply')}
                variant="primary"
                icon="ti ti-refresh"
                processing={form.processing}
                onConfirm={() => lancer(true)}
                onCancel={() => setConfirmVisible(false)}
            />
        </BackofficeLayout>
    );
}
