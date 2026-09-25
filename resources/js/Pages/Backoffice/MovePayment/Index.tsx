import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import FormField from '@/Components/Forms/FormField';
import SelectField from '@/Components/Forms/SelectField';
import TextareaField from '@/Components/Forms/TextareaField';
import CheckboxField from '@/Components/Forms/CheckboxField';
import { t } from '@/Lib/i18n';
import type { MovePaymentFee, MovePaymentPageProps, SelectOption } from '@/Types';

const URL = '/backoffice/move-payment';

interface MoveForm {
    encaissement: string;
    inscription: string;
    inscription_fee_id: number | '';
    purger_presences: boolean;
    motif: string;
}

/** Une donnée figée du paiement — ce que le geste ne touchera pas. */
function Frozen({ icon, label, value }: { icon: string; label: string; value: ReactNode }) {
    return (
        <div className="col-6 col-md-4 col-xl-2">
            <div className="d-flex align-items-center">
                <span className="avatar avatar-sm bg-light rounded me-2 flex-shrink-0">
                    <i className={`ti ${icon} text-dark`} aria-hidden="true" />
                </span>
                <div className="overflow-hidden">
                    <div className="text-muted fs-12">{label}</div>
                    <div className="fw-semibold text-truncate">{value}</div>
                </div>
            </div>
        </div>
    );
}

function Row({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="d-flex justify-content-between align-items-start py-2 border-bottom">
            <span className="text-muted me-3">{label}</span>
            <span className="fw-medium text-end">{value}</span>
        </div>
    );
}

function FeeStatut({ f }: { f: MovePaymentFee }) {
    if (f.masque) {
        return <span className="badge badge-soft-secondary">{t('Hidden')}</span>;
    }
    if (Number(f.reste) <= 0) {
        return <span className="badge badge-soft-success">{f.statut}</span>;
    }
    if (Number(f.paye) > 0) {
        return <span className="badge badge-soft-warning">{f.statut}</span>;
    }

    return <span className="badge badge-soft-danger">{f.statut}</span>;
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
                      label: `${f.nom} - ${t('Due')} ${f.montant} / ${t('Paid')} ${f.paye} / ${t('Remaining')} ${f.reste}`,
                      disabled: f.masque || tropPetit,
                      disabledReason: f.masque ? t('Hidden') : tropPetit ? t('Remaining due too small') : undefined,
                  };
              });

    // La ligne cible surlignée dans le tableau : détectée (mode frais) ou choisie (mode avance).
    const cibleId = d === null ? null : d.mode === 'frais' ? (d.fraisDetecte?.id ?? null) : form.data.inscription_fee_id || null;

    return (
        <BackofficeLayout
            title={t('Move a payment')}
            breadcrumbs={[
                { label: t('Dashboard'), href: '/backoffice/dashboard' },
                { label: t('Maintenance') },
                { label: t('Move a payment') },
            ]}
        >
            <div className="row justify-content-center">
                <div className="col-xxl-10">
                    {/* ── Étape 1 : références ─────────────────────────── */}
                    <Card>
                        <div className="d-flex align-items-start mb-3">
                            <span className="avatar avatar-md bg-primary-transparent rounded me-3 flex-shrink-0">
                                <i className="ti ti-arrows-exchange text-primary fs-20" aria-hidden="true" />
                            </span>
                            <div>
                                <h5 className="mb-1">{t('Move a payment')}</h5>
                                <p className="text-muted fs-13 mb-0">
                                    {t('Enter the payment reference and the registration it should belong to, then analyse. Nothing is written until you confirm.')}
                                </p>
                            </div>
                        </div>
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
                        <div className="alert alert-danger d-flex align-items-center" role="alert">
                            <i className="ti ti-alert-circle me-2 fs-18" aria-hidden="true" />
                            {diagnostic.erreur}
                        </div>
                    )}

                    {d !== null && (
                        <form onSubmit={deplacer}>
                            {/* ── Le mouvement, en une ligne ──────────────── */}
                            <div className="card bg-light border-0">
                                <div className="card-body py-3">
                                    <div className="d-flex flex-wrap align-items-center justify-content-center gap-3 text-center">
                                        <div>
                                            <div className="text-muted fs-12">{t('Current student')}</div>
                                            <div className="fw-bold">{d.source.etudiant ?? '-'}</div>
                                            <div className="fs-12 text-muted">{d.source.inscription ?? t('Advance (no fee)')}</div>
                                        </div>
                                        <div className="px-2">
                                            <div className="fs-24 fw-bold text-primary">{d.source.montant} MAD</div>
                                            <i className="ti ti-arrow-big-right-lines fs-24 text-primary" aria-hidden="true" />
                                        </div>
                                        <div>
                                            <div className="text-muted fs-12">{t('Target registration')}</div>
                                            <div className="fw-bold">{d.cible.etudiant ?? '-'}</div>
                                            <div className="fs-12 text-muted">
                                                {d.cible.reference}
                                                {d.mode === 'frais' && d.fraisDetecte ? ` · ${d.fraisDetecte.nom}` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* ── Ce qui ne bouge PAS ─────────────────────── */}
                            <Card title={t('Source payment - what will NOT change')}>
                                <div className="row g-3">
                                    <Frozen icon="ti-hash" label={t('Reference')} value={<span className="text-normal-case">{d.source.reference}</span>} />
                                    <Frozen icon="ti-coins" label={t('Amount')} value={`${d.source.montant} MAD`} />
                                    <Frozen icon="ti-credit-card" label={t('Method')} value={d.source.methode} />
                                    <Frozen icon="ti-calendar" label={t('Date')} value={d.source.date ?? '-'} />
                                    <Frozen icon="ti-user" label={t('Agent')} value={d.source.agent ?? '-'} />
                                    <Frozen icon="ti-building-bank" label={t('Till')} value={d.source.caisse ?? '-'} />
                                </div>
                            </Card>

                            <div className="row">
                                {/* ── Source ──────────────────────────────── */}
                                <div className="col-xl-4">
                                    <Card title={t('Current student')}>
                                        <Row label={t('Student')} value={d.source.etudiant ?? '-'} />
                                        <Row
                                            label={t('Current fee')}
                                            value={d.mode === 'avance' ? <span className="badge badge-soft-warning">{t('Advance (no fee)')}</span> : d.source.frais}
                                        />
                                        <Row
                                            label={t('Registration')}
                                            value={d.source.inscription ? <span className="text-normal-case">{d.source.inscription}</span> : '-'}
                                        />
                                        <Row label={t('Status')} value={d.source.inscriptionStatut ?? '-'} />
                                        <Row label={t('Group')} value={d.source.groupe ?? '-'} />
                                        <Row
                                            label={t('Attendance lines in this group')}
                                            value={
                                                <span className={`badge ${d.presences.length > 0 ? 'badge-soft-warning' : 'badge-soft-success'}`}>
                                                    {d.presences.length}
                                                </span>
                                            }
                                        />
                                    </Card>
                                </div>

                                {/* ── Cible ───────────────────────────────── */}
                                <div className="col-xl-8">
                                    <Card title={t('Target registration')} bodyClassName="p-0">
                                        <div className="px-3 pt-2">
                                            <Row label={t('Student')} value={d.cible.etudiant ?? '-'} />
                                            <Row label={t('Registration')} value={<span className="text-normal-case">{d.cible.reference}</span>} />
                                            <Row label={t('Status')} value={d.cible.statut} />
                                            <Row label={t('Group')} value={d.cible.groupe ?? '-'} />
                                            <Row
                                                label={t('Attendance lines in this group')}
                                                value={<span className="badge badge-soft-info">{d.cible.presences}</span>}
                                            />
                                        </div>
                                        <div className="px-3 pt-3 pb-1 fw-semibold">{t('Fees of the target registration')}</div>
                                        <div className="table-responsive">
                                            <table className="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th style={{ width: 36 }} />
                                                        <th>{t('Fee')}</th>
                                                        <th className="text-end">{t('Due')}</th>
                                                        <th className="text-end">{t('Paid')}</th>
                                                        <th className="text-end">{t('Remaining')}</th>
                                                        <th>{t('Status')}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {d.cible.frais.map((f) => {
                                                        const estCible = f.id === cibleId;

                                                        return (
                                                            <tr
                                                                key={f.id}
                                                                className={estCible ? 'table-active' : f.masque ? 'text-muted' : undefined}
                                                            >
                                                                <td className="text-center">
                                                                    {estCible && <i className="ti ti-target-arrow text-primary fs-16" aria-hidden="true" />}
                                                                </td>
                                                                <td className={f.masque ? 'text-decoration-line-through' : estCible ? 'fw-bold' : undefined}>
                                                                    {f.nom}
                                                                </td>
                                                                <td className="text-end">{f.montant}</td>
                                                                <td className="text-end">{f.paye}</td>
                                                                <td className={`text-end ${Number(f.reste) > 0 && !f.masque ? 'fw-semibold text-danger' : ''}`}>
                                                                    {f.reste}
                                                                </td>
                                                                <td>
                                                                    <FeeStatut f={f} />
                                                                </td>
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                            </table>
                                        </div>
                                    </Card>
                                </div>
                            </div>

                            {/* ── Refus et conditions ─────────────────────── */}
                            {d.blocages.length > 0 && (
                                <div className="alert alert-danger" role="alert">
                                    <div className="fw-semibold mb-1">
                                        <i className="ti ti-ban me-1" aria-hidden="true" />
                                        {t('This move is refused:')}
                                    </div>
                                    <ul className="mb-0 ps-3">
                                        {d.blocages.map((b) => (
                                            <li key={b}>{b}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            {d.mode === 'frais' && d.presences.length > 0 && (
                                <div className="alert alert-warning" role="alert">
                                    <div className="fw-semibold mb-2">
                                        <i className="ti ti-alert-triangle me-1" aria-hidden="true" />
                                        {d.presences.length} {t('attendance line(s) on the source registration block the transfer')}
                                    </div>
                                    <div className="d-flex flex-wrap gap-1 mb-3">
                                        {d.presences.map((p) => (
                                            <span
                                                key={p.id}
                                                className={`badge ${p.statut === 'Absent' ? 'badge-soft-secondary' : 'badge-soft-danger'}`}
                                            >
                                                {p.date} · {p.statut}
                                            </span>
                                        ))}
                                    </div>
                                    <CheckboxField
                                        id="purger-presences"
                                        label={`${t('Erase these ghost « Absent » lines before transferring (journaled)')} - ${d.presences.length}`}
                                        checked={form.data.purger_presences}
                                        disabled={!d.purgeable}
                                        onChange={(e) => form.setData('purger_presences', e.target.checked)}
                                    />
                                    {!d.purgeable && d.purgeRefus && <div className="text-danger fs-13">{d.purgeRefus}</div>}
                                    {form.errors.purger_presences && <div className="text-danger fs-13">{form.errors.purger_presences}</div>}
                                </div>
                            )}

                            {d.mode === 'frais' && d.fraisDetecte !== null && (
                                <div className="alert alert-info d-flex align-items-center" role="status">
                                    <i className="ti ti-target-arrow me-2 fs-18" aria-hidden="true" />
                                    <span>
                                        <span className="fw-semibold">{t('Detected target fee')} :</span> {d.fraisDetecte.nom} - {t('remaining due')}{' '}
                                        <span className="fw-semibold">{d.fraisDetecte.reste} MAD</span>
                                    </span>
                                </div>
                            )}
                            {d.mode === 'frais' && d.fraisDetecteRefus !== null && (
                                <div className="alert alert-danger d-flex align-items-center" role="alert">
                                    <i className="ti ti-alert-circle me-2 fs-18" aria-hidden="true" />
                                    {d.fraisDetecteRefus}
                                </div>
                            )}

                            {/* ── Étape 2 : confirmer ──────────────────────── */}
                            <Card title={t('Move the payment')}>
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
                                    placeholder={t('Explain why this money changes hands - kept in the audit journal.')}
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
                                    <button type="submit" className="btn btn-danger btn-lg" disabled={!pret || form.processing}>
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
                </div>
            </div>
        </BackofficeLayout>
    );
}
