import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import FormField from '@/Components/Forms/FormField';
import SelectField from '@/Components/Forms/SelectField';
import TextareaField from '@/Components/Forms/TextareaField';
import CheckboxField from '@/Components/Forms/CheckboxField';
import DetailRow from '@/Components/Details/DetailRow';
import { t } from '@/Lib/i18n';
import type { MovePaymentPageProps, SelectOption } from '@/Types';

const URL = '/backoffice/move-payment';

interface MoveForm {
    encaissement: string;
    inscription: string;
    inscription_fee_id: number | '';
    purger_presences: boolean;
    motif: string;
}

/**
 * « Déplacer un paiement » — outil de maintenance (compte de maintenance
 * seul, CLAUDE.md §16). Deux temps : ANALYSER (GET, rien n'est écrit — le
 * diagnostic vient du serveur), puis DÉPLACER (POST, motif obligatoire).
 *
 * Le bouton se grise tant qu'un refus est affiché ; il ne redérive aucune
 * règle : `blocages`, `purgeable`, `fraisDetecte` sont calculés par
 * Domain\Payments\Queries\DiagnostiquerDeplacementPaiement, à partir des
 * mêmes actions qui refuseront côté serveur (§5).
 */
export default function MovePaymentIndex({ filters, diagnostic }: MovePaymentPageProps) {
    const [refs, setRefs] = useState({ encaissement: filters.encaissement, inscription: filters.inscription });

    const form = useForm<MoveForm>({
        encaissement: filters.encaissement,
        inscription: filters.inscription,
        inscription_fee_id: '',
        purger_presences: false,
        motif: '',
    });

    const d = diagnostic !== null && diagnostic.erreur === null ? diagnostic : null;

    function analyser(e: FormEvent) {
        e.preventDefault();
        router.get(
            URL,
            { encaissement: refs.encaissement.trim(), inscription: refs.inscription.trim() },
            { preserveState: false, replace: true },
        );
    }

    function deplacer(e: FormEvent) {
        e.preventDefault();
        form.post(URL, { preserveScroll: true });
    }

    const bloque = d === null || d.blocages.length > 0;
    const presencesBloquent = d !== null && d.presences.length > 0 && !form.data.purger_presences;
    const fraisManque =
        d === null || (d.mode === 'avance' ? form.data.inscription_fee_id === '' : d.fraisDetecte === null);
    const pret = !bloque && !presencesBloquent && !fraisManque && form.data.motif.trim().length >= 10;

    // Les refus des actions arrivent sous des clés qui ne sont pas des
    // champs de ce formulaire (encaissement_id, inscription_id…) : on les
    // affiche tous, jamais masqués (§11 « signaler plutôt que masquer »).
    const champs = new Set(['motif', 'inscription_fee_id', 'purger_presences']);
    const autresErreurs = Object.entries(form.errors).filter(([k]) => !champs.has(k));

    const feeOptions: SelectOption[] =
        d === null
            ? []
            : d.cible.frais.map((f) => {
                  const tropPetit = Number(f.reste) < Number(d.source.montant);

                  return {
                      value: f.id,
                      label: `${f.nom} — ${t('Due')} ${f.montant} / ${t('Paid')} ${f.paye} / ${t('Remaining')} ${f.reste}`,
                      disabled: f.masque || tropPetit,
                      disabledReason: f.masque ? t('Hidden') : tropPetit ? t('Remaining due too small') : undefined,
                  };
              });

    return (
        <BackofficeLayout
            title={t('Move a payment')}
            breadcrumbs={[
                { label: t('Dashboard'), href: '/backoffice/dashboard' },
                { label: t('Maintenance') },
                { label: t('Move a payment') },
            ]}
        >
            <div className="alert alert-secondary d-flex align-items-start" role="note">
                <i className="ti ti-shield-lock me-2 mt-1 fs-18" aria-hidden="true" />
                <div>
                    <div className="fw-semibold">
                        {t('Maintainer only — this screen is reachable by its link and never from the menu.')}
                    </div>
                    <div className="fs-13">
                        {t('Amount, date, agent, till and the till balance stay exactly as they are — only the assignment changes.')}
                    </div>
                </div>
            </div>

            <Card title={t('References')}>
                <p className="text-muted fs-13 mb-3">
                    {t('Enter the payment reference and the registration it should belong to, then analyse. Nothing is written until you confirm.')}
                </p>
                <form onSubmit={analyser} className="row g-3 align-items-end">
                    <div className="col-md-5">
                        <FormField
                            id="ref-encaissement"
                            label={t('Payment reference')}
                            placeholder={t('e.g. ENC-27143')}
                            className="text-normal-case"
                            value={refs.encaissement}
                            onChange={(e) => setRefs({ ...refs, encaissement: e.target.value.toUpperCase() })}
                            required
                        />
                    </div>
                    <div className="col-md-5">
                        <FormField
                            id="ref-inscription"
                            label={t('Target registration reference')}
                            placeholder={t('e.g. INS-6437')}
                            className="text-normal-case"
                            value={refs.inscription}
                            onChange={(e) => setRefs({ ...refs, inscription: e.target.value.toUpperCase() })}
                            required
                        />
                    </div>
                    <div className="col-md-2 mb-3">
                        <button type="submit" className="btn btn-primary w-100">
                            <i className="ti ti-search me-1" aria-hidden="true" />
                            {t('Analyse')}
                        </button>
                    </div>
                </form>
            </Card>

            {diagnostic !== null && diagnostic.erreur !== null && (
                <div className="alert alert-danger" role="alert">
                    {diagnostic.erreur}
                </div>
            )}

            {d !== null && (
                <form onSubmit={deplacer}>
                    <div className="row">
                        <div className="col-lg-6">
                            <Card title={t('Source payment — what will NOT change')}>
                                <DetailRow label={t('Reference')} value={<span className="text-normal-case fw-semibold">{d.source.reference}</span>} />
                                <DetailRow label={t('Amount')} value={`${d.source.montant} MAD`} />
                                <DetailRow label={t('Method')} value={d.source.methode} />
                                <DetailRow label={t('Date')} value={d.source.date ?? '—'} />
                                <DetailRow label={t('Agent')} value={d.source.agent ?? '—'} />
                                <DetailRow label={t('Till')} value={d.source.caisse ?? '—'} />
                                <DetailRow borderTop label={t('Current student')} value={d.source.etudiant ?? '—'} />
                                <DetailRow
                                    label={t('Current fee')}
                                    value={d.mode === 'avance' ? <span className="badge bg-warning-transparent">{t('Advance (no fee)')}</span> : d.source.frais}
                                />
                                <DetailRow
                                    label={t('Registration')}
                                    value={d.source.inscription ? `${d.source.inscription} — ${d.source.inscriptionStatut ?? ''}` : '—'}
                                />
                                <DetailRow label={t('Group')} value={d.source.groupe ?? '—'} />
                            </Card>
                        </div>
                        <div className="col-lg-6">
                            <Card title={t('Target registration')}>
                                <DetailRow label={t('Reference')} value={<span className="text-normal-case fw-semibold">{d.cible.reference}</span>} />
                                <DetailRow label={t('Status')} value={d.cible.statut} />
                                <DetailRow label={t('Student')} value={d.cible.etudiant ?? '—'} />
                                <DetailRow label={t('Group')} value={d.cible.groupe ?? '—'} />
                                <DetailRow label={t('Attendance lines in this group')} value={d.cible.presences} />
                                <div className="mt-3">
                                    <div className="fw-semibold mb-2">{t('Fees of the target registration')}</div>
                                    <div className="table-responsive">
                                        <table className="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>{t('Fee')}</th>
                                                    <th className="text-end">{t('Due')}</th>
                                                    <th className="text-end">{t('Paid')}</th>
                                                    <th className="text-end">{t('Remaining')}</th>
                                                    <th>{t('Status')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {d.cible.frais.map((f) => (
                                                    <tr key={f.id} className={f.masque ? 'text-muted text-decoration-line-through' : undefined}>
                                                        <td>{f.nom}</td>
                                                        <td className="text-end">{f.montant}</td>
                                                        <td className="text-end">{f.paye}</td>
                                                        <td className="text-end">{f.reste}</td>
                                                        <td>{f.masque ? t('Hidden') : f.statut}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </Card>
                        </div>
                    </div>

                    {d.blocages.length > 0 && (
                        <div className="alert alert-danger" role="alert">
                            <div className="fw-semibold mb-1">{t('This move is refused:')}</div>
                            <ul className="mb-0 ps-3">
                                {d.blocages.map((b) => (
                                    <li key={b}>{b}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {d.mode === 'frais' && d.presences.length > 0 && (
                        <div className="alert alert-warning" role="alert">
                            <div className="fw-semibold mb-1">
                                {d.presences.length} {t('attendance line(s) on the source registration block the transfer')}
                            </div>
                            <ul className="mb-2 ps-3 fs-13">
                                {d.presences.map((p) => (
                                    <li key={p.id}>
                                        {p.date} — {p.statut}
                                    </li>
                                ))}
                            </ul>
                            <CheckboxField
                                id="purger-presences"
                                label={`${t('Erase these ghost « Absent » lines before transferring (journaled)')} — ${d.presences.length}`}
                                checked={form.data.purger_presences}
                                disabled={!d.purgeable}
                                onChange={(e) => form.setData('purger_presences', e.target.checked)}
                            />
                            {!d.purgeable && d.purgeRefus && <div className="text-danger fs-13">{d.purgeRefus}</div>}
                            {form.errors.purger_presences && <div className="text-danger fs-13">{form.errors.purger_presences}</div>}
                        </div>
                    )}

                    {d.mode === 'frais' && d.fraisDetecte !== null && (
                        <div className="alert alert-info" role="status">
                            <span className="fw-semibold">{t('Detected target fee')} :</span> {d.fraisDetecte.nom} — {t('remaining due')}{' '}
                            {d.fraisDetecte.reste} MAD
                        </div>
                    )}
                    {d.mode === 'frais' && d.fraisDetecteRefus !== null && (
                        <div className="alert alert-danger" role="alert">
                            {d.fraisDetecteRefus}
                        </div>
                    )}

                    <Card>
                        {d.mode === 'avance' && (
                            <SelectField
                                id="frais-cible"
                                label={t('Target fee')}
                                placeholder={t('Choose a fee')}
                                options={feeOptions}
                                value={form.data.inscription_fee_id}
                                onChange={(e) => form.setData('inscription_fee_id', e.target.value === '' ? '' : Number(e.target.value))}
                                error={form.errors.inscription_fee_id}
                                required
                            />
                        )}

                        <TextareaField
                            id="motif"
                            label={t('Reason')}
                            rows={3}
                            placeholder={t('Explain why this money changes hands — kept in the audit journal.')}
                            value={form.data.motif}
                            onChange={(e) => form.setData('motif', e.target.value)}
                            error={form.errors.motif}
                            required
                        />

                        {autresErreurs.length > 0 && (
                            <div className="alert alert-danger" role="alert">
                                <ul className="mb-0 ps-3">
                                    {autresErreurs.map(([k, v]) => (
                                        <li key={k}>{v}</li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        <div className="d-flex justify-content-end">
                            <button type="submit" className="btn btn-danger" disabled={!pret || form.processing}>
                                {form.processing ? (
                                    <>
                                        <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" />
                                        {t('Moving…')}
                                    </>
                                ) : (
                                    <>
                                        <i className="ti ti-arrows-exchange me-1" aria-hidden="true" />
                                        {t('Move the payment')}
                                    </>
                                )}
                            </button>
                        </div>
                    </Card>
                </form>
            )}
        </BackofficeLayout>
    );
}
