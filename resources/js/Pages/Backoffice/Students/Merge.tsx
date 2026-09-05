import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import PageTabs from '@/Components/Navigation/PageTabs';
import { STUDENTS_TABS } from '@/Config/pageTabs';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import SearchInput from '@/Components/Tables/SearchInput';
import TableToolbar from '@/Components/Tables/TableToolbar';
import Modal from '@/Components/Modals/Modal';
import SelectField from '@/Components/Forms/SelectField';
import FormActions from '@/Components/Forms/FormActions';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { t } from '@/Lib/i18n';

/**
 * « Fusion de fiches & réaffectation des paiements » — écran de réparation
 * super-admin, hors barre latérale (routes students.merge.*).
 *
 * Les deux moitiés d'une même panne : recoller deux fiches d'une même
 * personne, puis rattacher chaque paiement au bon frais. Les inscriptions
 * sont listées TOUS STATUTS confondus — c'est le but de la page.
 */

interface Candidat {
    id: number;
    reference: string;
    legacyRef: string | null;
    nom: string;
    prenom: string;
    telephone: string | null;
    dateNaissance: string | null;
    centre: string | null;
    inscriptionsCount: number;
    encaissementsCount: number;
}

interface FraisLigne {
    id: number;
    nom: string;
    montant: number;
    paye: number;
    reste: number;
    statut: string;
    masque: boolean;
}

interface InscriptionLigne {
    id: number;
    reference: string;
    statut: string;
    groupe: string | null;
    annee: string | null;
    centre: string | null;
    frais: FraisLigne[];
}

interface PaiementLigne {
    id: number;
    reference: string;
    montant: number;
    methode: string;
    datePaiement: string | null;
    caisse: string | null;
    agent: string | null;
    fraisId: number | null;
    frais: string | null;
    inscriptionReference: string | null;
    estAvance: boolean;
    estRembourse: boolean;
    estApplication: boolean;
}

interface Dossier {
    etudiant: {
        id: number;
        reference: string;
        nom: string;
        prenom: string;
        telephone: string | null;
        centre: string | null;
    };
    inscriptions: InscriptionLigne[];
    paiements: PaiementLigne[];
}

interface Props {
    filters: { search: string; etudiant_id: number | null };
    candidats: Candidat[];
    dossier: Dossier | null;
}

const URL = '/backoffice/students/fusion';

const money = (v: number) =>
    `${v.toLocaleString('fr-MA', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} MAD`;

/** Badge Bootstrap par statut d'inscription — un dossier clos doit se voir. */
const statutBadge = (statut: string) => {
    switch (statut) {
        case 'Active':
            return 'badge-soft-success';
        case 'Annulée':
            return 'badge-soft-danger';
        case 'Changement':
            return 'badge-soft-warning';
        default:
            return 'badge-soft-secondary';
    }
};

export default function StudentMerge({ filters, candidats, dossier }: Props) {
    const loading = useInertiaLoading();
    const [showMerge, setShowMerge] = useState(false);
    const [movePayment, setMovePayment] = useState<PaiementLigne | null>(null);
    // Les deux fiches cochees dans le tableau de resultats. La fusion se pilote
    // depuis les LIGNES et non depuis deux listes deroulantes dans la modale :
    // celles-ci se nourrissaient de `candidats`, donc elles etaient vides tant
    // qu'aucune recherche n'avait ete lancee (signale le 05/09/2026).
    const [selection, setSelection] = useState<number[]>([]);

    const reload = (next: Partial<Props['filters']>) => {
        router.get(
            URL,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const mergeForm = useForm({ garde_id: '', doublon_id: '' });
    const moveForm = useForm({ encaissement_id: '', fee_id: '' });

    const submitMerge = (e: FormEvent) => {
        e.preventDefault();
        mergeForm.post(URL, {
            preserveScroll: true,
            onSuccess: () => {
                setShowMerge(false);
                setSelection([]);
            },
        });
    };

    const submitMove = (e: FormEvent) => {
        e.preventDefault();
        moveForm.post(`${URL}/deplacer-paiement`, {
            preserveScroll: true,
            onSuccess: () => setMovePayment(null),
        });
    };

    const toggleSelection = (id: number) => {
        setSelection((prev) =>
            prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id],
        );
    };

    const selectionnes = candidats.filter((c) => selection.includes(c.id));
    // La fiche CONSERVEE est celle qui porte le plus d'historique : c'est
    // toujours celle-la qu'on veut garder, et cela evite a l'operateur de
    // choisir le mauvais sens de fusion. Il peut inverser dans la modale.
    const [gardeDefaut, doublonDefaut] = [...selectionnes].sort(
        (a, b) =>
            b.inscriptionsCount + b.encaissementsCount -
            (a.inscriptionsCount + a.encaissementsCount),
    );

    // Resolues depuis le formulaire (et non depuis la selection) pour que le
    // bouton « Inverser » se reflete tout de suite dans le recapitulatif.
    const garde = candidats.find((c) => String(c.id) === mergeForm.data.garde_id);
    const doublon = candidats.find((c) => String(c.id) === mergeForm.data.doublon_id);

    const openMerge = () => {
        mergeForm.clearErrors();
        mergeForm.setData({
            garde_id: gardeDefaut ? String(gardeDefaut.id) : '',
            doublon_id: doublonDefaut ? String(doublonDefaut.id) : '',
        });
        setShowMerge(true);
    };

    const openMove = (p: PaiementLigne) => {
        moveForm.setData({ encaissement_id: String(p.id), fee_id: p.fraisId ? String(p.fraisId) : '' });
        setMovePayment(p);
    };

    // Toutes les lignes de frais de TOUTES les inscriptions, tous statuts —
    // le statut est affiché dans le libellé pour que l'opérateur voie qu'il
    // vise un dossier clos.
    const fraisOptions = (dossier?.inscriptions ?? []).flatMap((i) =>
        i.frais.map((f) => ({
            value: String(f.id),
            label: `${i.reference} · ${i.statut}${i.annee ? ` · ${i.annee}` : ''} — ${f.nom} (${t('remaining')} ${money(f.reste)})${f.masque ? ` — ${t('hidden fee')}` : ''}`,
        })),
    );

    return (
        <BackofficeLayout
            title={t('Merge students & reassign payments')}
            breadcrumbs={[
                { label: t('Students'), href: '/backoffice/students' },
                { label: t('Merge students & reassign payments') },
            ]}
        >
            <PageTabs tabs={STUDENTS_TABS} />

            <div className="alert alert-warning d-flex align-items-start" role="alert">
                <i className="ti ti-alert-triangle me-2 fs-18" aria-hidden="true" />
                <div>
                    <strong>{t('Super-admin repair tool.')}</strong>{' '}
                    {t(
                        'Registrations of every status are listed, across all centres and years. No amount, payment date, cash register or agent is ever modified — only which student record and which fee a row is attached to.',
                    )}
                </div>
            </div>

            <Card title={t('Find a student record')}>
                <TableToolbar
                    search={
                        <SearchInput
                            value={filters.search}
                            onSearch={(search) => reload({ search })}
                            placeholder={t('Name, reference, legacy ref or phone…')}
                        />
                    }
                />

                {candidats.length === 0 ? (
                    <EmptyState
                        icon="ti ti-user-search"
                        title={
                            filters.search.trim().length < 2
                                ? t('Search for a student record')
                                : t('No student record found')
                        }
                        message={
                            filters.search.trim().length < 2
                                ? t('Type at least 2 characters to search.')
                                : t('No record matches this search.')
                        }
                    />
                ) : (
                    <div className={`table-responsive${loading ? ' opacity-50' : ''}`}>
                        <table className="table table-hover">
                            <thead>
                                <tr>
                                    <th style={{ width: '1%' }} aria-label={t('Select')} />
                                    <th>{t('Reference')}</th>
                                    <th>{t('Student')}</th>
                                    <th className="text-normal-case">{t('Phone')}</th>
                                    <th>{t('Birth date')}</th>
                                    <th>{t('Centre')}</th>
                                    <th className="text-center">{t('Registrations')}</th>
                                    <th className="text-center">{t('Payments')}</th>
                                    <th className="text-end">{t('Action')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {candidats.map((c) => (
                                    <tr key={c.id} className={c.id === filters.etudiant_id ? 'table-active' : undefined}>
                                        <td>
                                            <div className="form-check">
                                                <input
                                                    type="checkbox"
                                                    className="form-check-input"
                                                    id={`fiche-${c.id}`}
                                                    checked={selection.includes(c.id)}
                                                    onChange={() => toggleSelection(c.id)}
                                                    aria-label={`${c.prenom} ${c.nom}`}
                                                />
                                            </div>
                                        </td>
                                        <td>
                                            {c.reference}
                                            {c.legacyRef && (
                                                <span className="text-muted ms-1 text-normal-case">({c.legacyRef})</span>
                                            )}
                                        </td>
                                        <td>
                                            {c.prenom} {c.nom}
                                        </td>
                                        <td className="text-normal-case">{c.telephone ?? '—'}</td>
                                        <td>{c.dateNaissance ?? '—'}</td>
                                        <td>{c.centre ?? '—'}</td>
                                        <td className="text-center">{c.inscriptionsCount}</td>
                                        <td className="text-center">{c.encaissementsCount}</td>
                                        <td className="text-end">
                                            <button
                                                type="button"
                                                className="btn btn-sm btn-outline-primary"
                                                onClick={() => reload({ etudiant_id: c.id })}
                                            >
                                                <i className="ti ti-folder-open me-1" aria-hidden="true" />
                                                {t('Open file')}
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <div className="d-flex justify-content-between align-items-center mt-3">
                    <span className="text-muted">
                        {selection.length === 0
                            ? t('Tick the two records of the same person.')
                            : `${selection.length} ${t('record(s) selected')}`}
                    </span>
                    <button
                        type="button"
                        className="btn btn-warning"
                        disabled={selection.length !== 2}
                        title={selection.length !== 2 ? t('Tick exactly two records.') : undefined}
                        onClick={openMerge}
                    >
                        <i className="ti ti-arrow-merge me-1" aria-hidden="true" />
                        {t('Merge two records')}
                    </button>
                </div>
            </Card>

            {dossier && (
                <>
                    <Card
                        title={`${t('Registrations of')} ${dossier.etudiant.prenom} ${dossier.etudiant.nom} (${dossier.etudiant.reference})`}
                    >
                        {dossier.inscriptions.length === 0 ? (
                            <EmptyState
                                icon="ti ti-file-off"
                                title={t('This student has no registration')}
                                message={t('Their payments can only be advances until a registration exists.')}
                            />
                        ) : (
                            dossier.inscriptions.map((i) => (
                                <div key={i.id} className="mb-4">
                                    <div className="d-flex align-items-center gap-2 mb-2">
                                        <strong>{i.reference}</strong>
                                        <span className={`badge ${statutBadge(i.statut)}`}>{i.statut}</span>
                                        {i.groupe && <span className="text-muted">{i.groupe}</span>}
                                        {i.annee && <span className="text-muted">· {i.annee}</span>}
                                        {i.centre && <span className="text-muted">· {i.centre}</span>}
                                    </div>
                                    <div className="table-responsive">
                                        <table className="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>{t('Fee')}</th>
                                                    <th className="text-end">{t('Amount')}</th>
                                                    <th className="text-end">{t('Paid')}</th>
                                                    <th className="text-end">{t('Remaining')}</th>
                                                    <th>{t('Status')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {i.frais.map((f) => (
                                                    <tr key={f.id} className={f.masque ? 'opacity-50' : undefined}>
                                                        <td>
                                                            {f.nom}
                                                            {f.masque && (
                                                                <span className="badge badge-soft-secondary ms-2">
                                                                    {t('hidden fee')}
                                                                </span>
                                                            )}
                                                        </td>
                                                        <td className="text-end">{money(f.montant)}</td>
                                                        <td className="text-end">{money(f.paye)}</td>
                                                        <td className="text-end">{money(f.reste)}</td>
                                                        <td>{f.statut}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            ))
                        )}
                    </Card>

                    <Card title={t('Payments')}>
                        {dossier.paiements.length === 0 ? (
                            <EmptyState icon="ti ti-cash-off" title={t('No payment recorded')} />
                        ) : (
                            <div className="table-responsive">
                                <table className="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>{t('Reference')}</th>
                                            <th className="text-end">{t('Amount')}</th>
                                            <th>{t('Method')}</th>
                                            <th>{t('Date')}</th>
                                            <th>{t('Cash register')}</th>
                                            <th>{t('Agent')}</th>
                                            <th>{t('Attached to')}</th>
                                            <th className="text-end">{t('Action')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {dossier.paiements.map((p) => (
                                            <tr key={p.id}>
                                                <td>{p.reference}</td>
                                                <td className="text-end">{money(p.montant)}</td>
                                                <td>{p.methode}</td>
                                                <td>{p.datePaiement ?? '—'}</td>
                                                <td>{p.caisse ?? '—'}</td>
                                                <td>{p.agent ?? '—'}</td>
                                                <td>
                                                    {p.estAvance ? (
                                                        <span className="badge badge-soft-info">{t('Advance')}</span>
                                                    ) : (
                                                        <>
                                                            {p.frais}
                                                            {p.inscriptionReference && (
                                                                <span className="text-muted ms-1">
                                                                    ({p.inscriptionReference})
                                                                </span>
                                                            )}
                                                        </>
                                                    )}
                                                    {p.estRembourse && (
                                                        <span className="badge badge-soft-danger ms-2">
                                                            {t('Refunded')}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="text-end">
                                                    <button
                                                        type="button"
                                                        className="btn btn-sm btn-outline-primary"
                                                        disabled={p.estRembourse}
                                                        title={
                                                            p.estRembourse
                                                                ? t('A refunded payment cannot be moved.')
                                                                : undefined
                                                        }
                                                        onClick={() => openMove(p)}
                                                    >
                                                        <i className="ti ti-exchange me-1" aria-hidden="true" />
                                                        {t('Move')}
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </Card>
                </>
            )}

            <Modal
                show={showMerge}
                title={t('Merge two student records')}
                onClose={() => setShowMerge(false)}
                processing={mergeForm.processing}
            >
                <form onSubmit={submitMerge}>
                    <p className="text-muted">
                        {t(
                            'Everything attached to the duplicate (registrations, payments, cheques, attendance, refunds) is re-pointed at the record you keep. No amount changes and no cash register moves. The duplicate is kept and renamed « (doublon fusionné) ».',
                        )}
                    </p>

                    {/* Les deux fiches viennent des cases cochees : la modale
                        n'a plus de liste deroulante a remplir, donc elle ne
                        peut plus s'ouvrir vide. */}
                    <div className="row g-3">
                        {[
                            { fiche: garde, titre: t('Record to KEEP'), cls: 'border-success' },
                            { fiche: doublon, titre: t('Duplicate to merge INTO it'), cls: 'border-warning' },
                        ].map(({ fiche, titre, cls }) => (
                            <div className="col-md-6" key={titre}>
                                <div className={`border ${cls} rounded p-3 h-100`}>
                                    <div className="fw-semibold mb-2">{titre}</div>
                                    {fiche ? (
                                        <>
                                            <div>
                                                {fiche.prenom} {fiche.nom}
                                            </div>
                                            <div className="text-muted text-normal-case">
                                                {fiche.reference}
                                                {fiche.legacyRef ? ` (${fiche.legacyRef})` : ''}
                                            </div>
                                            <div className="text-muted">{fiche.centre ?? '—'}</div>
                                            <div className="mt-2">
                                                {fiche.inscriptionsCount} {t('registrations')} ·{' '}
                                                {fiche.encaissementsCount} {t('payments')}
                                            </div>
                                        </>
                                    ) : (
                                        <span className="text-muted">—</span>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="d-flex justify-content-center my-3">
                        <button
                            type="button"
                            className="btn btn-sm btn-outline-secondary"
                            onClick={() =>
                                mergeForm.setData({
                                    garde_id: mergeForm.data.doublon_id,
                                    doublon_id: mergeForm.data.garde_id,
                                })
                            }
                        >
                            <i className="ti ti-switch-horizontal me-1" aria-hidden="true" />
                            {t('Swap direction')}
                        </button>
                    </div>

                    {(mergeForm.errors.garde_id || mergeForm.errors.doublon_id) && (
                        <div className="alert alert-danger" role="alert">
                            {mergeForm.errors.garde_id ?? mergeForm.errors.doublon_id}
                        </div>
                    )}

                    <FormActions
                        onCancel={() => setShowMerge(false)}
                        processing={mergeForm.processing}
                        submitLabel={t('Merge')}
                    />
                </form>
            </Modal>

            <Modal
                show={movePayment !== null}
                title={t('Move this payment')}
                onClose={() => setMovePayment(null)}
                processing={moveForm.processing}
            >
                <form onSubmit={submitMove}>
                    {movePayment && (
                        <p className="text-muted">
                            {movePayment.reference} — <strong>{money(movePayment.montant)}</strong>,{' '}
                            {movePayment.methode}, {movePayment.datePaiement}.{' '}
                            {t(
                                'Only the fee it is attached to changes. Amount, method, date, cash register and agent stay exactly as they are.',
                            )}
                        </p>
                    )}

                    <SelectField
                        id="fee_id"
                        label={t('Attach to fee')}
                        value={moveForm.data.fee_id}
                        onChange={(e) => moveForm.setData('fee_id', e.target.value)}
                        error={moveForm.errors.fee_id ?? moveForm.errors.encaissement_id}
                        options={[
                            { value: '', label: t('— Detach (becomes a free advance) —') },
                            ...fraisOptions,
                        ]}
                    />

                    <FormActions
                        onCancel={() => setMovePayment(null)}
                        processing={moveForm.processing}
                        submitLabel={t('Move')}
                    />
                </form>
            </Modal>
        </BackofficeLayout>
    );
}
