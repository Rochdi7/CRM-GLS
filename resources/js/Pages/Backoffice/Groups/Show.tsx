import { router, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import StatusBadge from '@/Components/Details/StatusBadge';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import DateField from '@/Components/Forms/DateField';
import SelectField from '@/Components/Forms/SelectField';
import FormField from '@/Components/Forms/FormField';
import FormActions from '@/Components/Forms/FormActions';
import { blockImplicitSubmit } from '@/Lib/forms';
import type { GroupDetails, GroupEnseignantRow, SelectOption, SharedProps } from '@/Types';

interface GroupShowProps {
    group: GroupDetails;
    enseignants: Array<{ id: number; nom: string }>;
}

type ShowTab = 'frais' | 'etudiants' | 'enseignants' | 'groupe';

const TABS: Array<{ key: ShowTab; icon: string; label: string }> = [
    { key: 'frais', icon: 'ti-briefcase', label: 'Frais Scolaires' },
    { key: 'etudiants', icon: 'ti-user', label: 'Étudiants' },
    { key: 'enseignants', icon: 'ti-chalkboard', label: 'Enseignants' },
    { key: 'groupe', icon: 'ti-home', label: 'Groupe' },
];

/** Today as yyyy-mm-dd, the default changeover date of the "Changer d'enseignant" form. */
function todayIso(): string {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

function statutVariant(statut: string): 'success' | 'secondary' | 'danger' | 'warning' {
    if (statut === 'En formation') return 'success';
    if (statut === 'Fin de formation') return 'secondary';
    if (statut === 'Annulée') return 'danger';
    return 'warning';
}

/**
 * Replaces the previous card-sidebar layout with a PreSkool-style profile
 * header (GLS logo, Nom/Date début/Formation/Date fin/Statut) + pill tabs
 * (Frais Scolaires / Étudiants / Groupe) — no "Absence par groupe" tab,
 * since attendance has no backing feature/data yet. "Terminer la
 * formation" (the "Fin de formation" archive action) lives in the top
 * action row alongside Modifier/Retour, as a PLAIN HTML form posting to the
 * existing backoffice.groups.archive route. Uses the theme's primary blue
 * throughout (no yellow/warning) to stay on the app's own palette.
 */
export default function GroupShow({ group, enseignants }: GroupShowProps) {
    const [activeTab, setActiveTab] = useState<ShowTab>('frais');
    const [showEnseignantModal, setShowEnseignantModal] = useState(false);

    // Set by GroupController@changerEnseignant after a changeover — tells the
    // user their emploi du temps was stopped and a new one must be built.
    const { flash, context } = usePage<SharedProps>().props;
    const emploiDuTempsArrete = flash.emploiDuTempsArrete;

    // Super-admin only: move the whole group (inscriptions, séances,
    // payments) to another année scolaire — same rows, same counts.
    const [showMoveYearModal, setShowMoveYearModal] = useState(false);
    const moveYearForm = useForm({ annee_scolaire_id: '' as number | '', statut: '' });
    const statutOptions: SelectOption[] = ['En inscription', 'En formation', 'Fin de formation', 'Annulée'].map((s) => ({
        value: s,
        label: s === group.statut ? `${s} (statut actuel)` : s,
    }));
    const anneeOptions: SelectOption[] = (context?.availableAcademicYears ?? [])
        .filter((a) => a.id !== group.anneeScolaireId)
        .map((a) => ({ value: a.id, label: a.name }));

    function submitMoveYear(event: FormEvent) {
        event.preventDefault();
        moveYearForm.post(group.moveYearUrl, {
            preserveScroll: true,
            onSuccess: () => setShowMoveYearModal(false),
        });
    }

    const enseignantOptions: SelectOption[] = enseignants.map((e) => ({ value: e.id, label: e.nom }));

    const changerForm = useForm({
        enseignant_id: '' as number | '',
        date_debut: todayIso(),
        motif: '',
    });

    function openChangerEnseignant() {
        changerForm.clearErrors();
        changerForm.setData({ enseignant_id: '', date_debut: todayIso(), motif: '' });
        setShowEnseignantModal(true);
    }

    function submitChangerEnseignant(event: FormEvent) {
        event.preventDefault();
        changerForm.post(group.changerEnseignantUrl, {
            preserveScroll: true,
            onSuccess: () => setShowEnseignantModal(false),
        });
    }

    // Correcting an already-recorded period. A changeover stamps "today" as
    // the outgoing teacher's date de fin, so the real handover date often
    // needs fixing afterwards — hence this edit, which touches dates/motif
    // only (never the row's teacher: swapping one is a changeover).
    const [affectationEnCours, setAffectationEnCours] = useState<GroupEnseignantRow | null>(null);

    const affectationForm = useForm({
        date_debut: '',
        date_fin: '',
        motif: '',
    });

    function openModifierAffectation(row: GroupEnseignantRow) {
        affectationForm.clearErrors();
        affectationForm.setData({
            date_debut: row.dateDebutIso ?? '',
            date_fin: row.dateFinIso ?? '',
            motif: row.motif ?? '',
        });
        setAffectationEnCours(row);
    }

    function submitModifierAffectation(event: FormEvent) {
        event.preventDefault();
        if (!affectationEnCours) {
            return;
        }
        affectationForm.put(affectationEnCours.updateUrl, {
            preserveScroll: true,
            onSuccess: () => setAffectationEnCours(null),
        });
    }

    const [archiveOpen, setArchiveOpen] = useState(false);
    const [archiveError, setArchiveError] = useState<string | undefined>(undefined);
    const [archiveProcessing, setArchiveProcessing] = useState(false);

    // Inertia visit instead of a native form: the 422 from the context guard
    // is shown in the dialog, and a double click cannot post twice (F-006).
    function archiveGroup() {
        setArchiveProcessing(true);
        setArchiveError(undefined);
        router.post(group.archiveUrl, {}, {
            preserveScroll: true,
            onSuccess: () => setArchiveOpen(false),
            onError: (errors) => setArchiveError(Object.values(errors)[0] ?? 'Action impossible.'),
            onFinish: () => setArchiveProcessing(false),
        });
    }

    // « Rouvrir le groupe » — super-admin only, et uniquement sur un groupe
    // terminal (Fin de formation / Annulée). Ne modifie QUE le statut :
    // paiements, inscriptions et séances sont laissés intacts côté serveur
    // (Group::rouvrir), le snapshot d'historique est conservé.
    const [reopenOpen, setReopenOpen] = useState(false);
    const reopenForm = useForm({ statut: 'En formation' });
    const reopenOptions: SelectOption[] = ['En formation', 'En inscription'].map((s) => ({ value: s, label: s }));

    function submitReopen(event: FormEvent) {
        event.preventDefault();
        reopenForm.post(group.reopenUrl, {
            preserveScroll: true,
            onSuccess: () => setReopenOpen(false),
        });
    }

    return (
        <BackofficeLayout
            title={group.nom}
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Groupes', href: '/backoffice/groups' },
                { label: group.nom },
            ]}
        >
            {emploiDuTempsArrete && (
                <div className="alert alert-warning d-flex flex-wrap align-items-center gap-2" role="alert">
                    <i className="ti ti-alert-triangle fs-20" aria-hidden="true" />
                    <span className="flex-grow-1">
                        L'emploi du temps du groupe a été arrêté ({emploiDuTempsArrete.creneaux} créneau(x) clôturé(s),{' '}
                        {emploiDuTempsArrete.seances} séance(s) prévue(s) supprimée(s)). Créez un nouvel emploi du temps
                        pour le nouvel enseignant.
                    </span>
                    <a href={emploiDuTempsArrete.url} className="btn btn-warning btn-sm d-inline-flex align-items-center">
                        <i className="ti ti-calendar-plus me-1" />
                        Créer l'emploi du temps
                    </a>
                </div>
            )}

            <div className="card">
                <div className="bg-primary-transparent rounded-top px-4 py-3">
                    <h4 className="mb-0 text-primary">{group.statutLabel}</h4>
                </div>

                <div className="card-body">
                    <div className="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
                        <ul className="nav nav-tabs nav-tabs-solid nav-tabs-rounded-fill mb-0" role="tablist">
                            <li className="me-2" role="presentation">
                                <button type="button" className="nav-link rounded active" disabled>
                                    <i className="ti ti-file-text me-1" />
                                    Fiche
                                </button>
                            </li>
                        </ul>
                        <div className="d-flex align-items-center gap-2">
                            {group.canMoveYear && (
                                <button
                                    type="button"
                                    className="btn btn-outline-primary d-flex align-items-center"
                                    onClick={() => {
                                        moveYearForm.clearErrors();
                                        moveYearForm.setData({ annee_scolaire_id: '', statut: '' });
                                        setShowMoveYearModal(true);
                                    }}
                                >
                                    <i className="ti ti-calendar-repeat me-2" />
                                    Déplacer vers une autre année
                                </button>
                            )}
                            {group.canArchive && (
                                <button
                                    type="button"
                                    className="btn btn-outline-secondary d-flex align-items-center"
                                    onClick={() => setArchiveOpen(true)}
                                >
                                    <i className="ti ti-archive me-2" />
                                    Terminer la formation
                                </button>
                            )}
                            {group.canReopen && (
                                <button
                                    type="button"
                                    className="btn btn-outline-primary d-flex align-items-center"
                                    onClick={() => {
                                        reopenForm.clearErrors();
                                        reopenForm.setData('statut', 'En formation');
                                        setReopenOpen(true);
                                    }}
                                >
                                    <i className="ti ti-rotate-clockwise me-2" />
                                    Rouvrir le groupe
                                </button>
                            )}
                            <a href="/backoffice/groups" className="btn btn-primary d-flex align-items-center">
                                <i className="ti ti-edit me-2" />
                                Modifier
                            </a>
                            <a href="/backoffice/groups" className="btn btn-outline-secondary d-flex align-items-center">
                                <i className="ti ti-arrow-left me-2" />
                                Retour
                            </a>
                        </div>
                    </div>

                    <div className="row align-items-center mb-4">
                        <div className="col-auto">
                            <span className="avatar avatar-xxl rounded-circle bg-white border d-inline-flex align-items-center justify-content-center overflow-hidden p-2">
                                <img src="/assets/images/logo/gls-noir.png" alt="GLS" className="w-100 h-100" style={{ objectFit: 'contain' }} />
                            </span>
                        </div>
                        <div className="col">
                            <div className="row">
                                <div className="col-md-3 mb-3">
                                    <p className="text-muted mb-1">Nom</p>
                                    <p className="fw-medium mb-0">{group.nom}</p>
                                </div>
                                <div className="col-md-3 mb-3">
                                    <p className="text-muted mb-1">Formation</p>
                                    <p className="fw-medium mb-0">{group.niveau ?? '—'}</p>
                                </div>
                                <div className="col-md-3 mb-3">
                                    <p className="text-muted mb-1">Date de début</p>
                                    <p className="fw-medium mb-0">{group.dateDebutFormation ?? '—'}</p>
                                </div>
                                <div className="col-md-3 mb-3">
                                    <p className="text-muted mb-1">Date de fin</p>
                                    <p className="fw-medium mb-0">{group.dateFinFormation ?? '—'}</p>
                                </div>
                                <div className="col-md-3">
                                    <p className="text-muted mb-1">Statut</p>
                                    <StatusBadge label={group.statut} variant={statutVariant(group.statut)} dot />
                                </div>
                            </div>
                        </div>
                    </div>

                    <ul className="nav nav-tabs nav-tabs-solid nav-tabs-rounded-fill mb-3" role="tablist">
                        {TABS.map((tab) => (
                            <li className="me-2 mb-2" role="presentation" key={tab.key}>
                                <button
                                    type="button"
                                    className={`nav-link rounded${activeTab === tab.key ? ' active' : ''}`}
                                    onClick={() => setActiveTab(tab.key)}
                                >
                                    <i className={`ti ${tab.icon} me-1`} />
                                    {tab.label}
                                    {tab.key === 'frais' && (
                                        <span className={`badge ${activeTab === tab.key ? 'bg-white text-dark' : 'badge-soft-secondary'} ms-1`}>
                                            {group.fees.length}
                                        </span>
                                    )}
                                    {tab.key === 'enseignants' && (
                                        <span className={`badge ${activeTab === tab.key ? 'bg-white text-dark' : 'badge-soft-secondary'} ms-1`}>
                                            {group.enseignantsHistorique.length}
                                        </span>
                                    )}
                                    {tab.key === 'etudiants' && (
                                        <span className={`badge ${activeTab === tab.key ? 'bg-white text-dark' : 'badge-soft-secondary'} ms-1`}>
                                            {group.inscriptions.length}
                                        </span>
                                    )}
                                </button>
                            </li>
                        ))}
                    </ul>

                    {activeTab === 'frais' && (
                        <RelatedRecordsTable
                            isEmpty={group.fees.length === 0}
                            emptyTitle="Aucun frais assigné"
                            emptyIcon="ti ti-briefcase"
                            head={
                                <tr>
                                    <th>Frais</th>
                                    <th>Date d'échéance</th>
                                    <th>Montant</th>
                                </tr>
                            }
                        >
                            {group.fees.map((fee) => (
                                <tr key={fee.nom}>
                                    <td className="fw-medium">
                                        {fee.nom}
                                        {fee.classification && (
                                            <span className="badge badge-soft-info ms-1">{fee.classification}</span>
                                        )}
                                    </td>
                                    <td>{fee.dateEcheance ?? '—'}</td>
                                    <td>{Number(fee.montant).toFixed(2)} DH</td>
                                </tr>
                            ))}
                        </RelatedRecordsTable>
                    )}

                    {activeTab === 'etudiants' && (
                        <RelatedRecordsTable
                            isEmpty={group.inscriptions.length === 0}
                            emptyTitle="Aucun étudiant inscrit"
                            emptyIcon="ti ti-users"
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th>Étudiant</th>
                                    <th>Formation</th>
                                    <th>Statut</th>
                                    <th>Date d'inscription</th>
                                    <th>Date de début</th>
                                    <th>Date de fin</th>
                                    <th className="text-end">Action</th>
                                </tr>
                            }
                        >
                            {group.inscriptions.map((insc) => (
                                <tr key={insc.reference}>
                                    <td>
                                        <code>{insc.reference}</code>
                                    </td>
                                    <td>
                                        {insc.studentShowUrl ? (
                                            <a href={insc.studentShowUrl}>{insc.student}</a>
                                        ) : (
                                            (insc.student ?? '—')
                                        )}
                                    </td>
                                    <td>{group.niveau ?? '—'}</td>
                                    <td>
                                        <StatusBadge label={insc.statut} />
                                    </td>
                                    <td>{insc.date ?? '—'}</td>
                                    <td>{insc.dateDebut ?? '—'}</td>
                                    <td>{insc.dateFin ?? '—'}</td>
                                    <td className="text-end">
                                        {insc.studentShowUrl && (
                                            <a
                                                href={insc.studentShowUrl}
                                                className="btn btn-outline-light bg-white btn-icon d-inline-flex align-items-center justify-content-center rounded-circle p-0"
                                                title="Voir l'étudiant"
                                            >
                                                <i className="ti ti-eye" />
                                            </a>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </RelatedRecordsTable>
                    )}

                    {activeTab === 'enseignants' && (
                        <>
                            <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                <p className="text-muted mb-0">
                                    Historique des affectations — un seul enseignant est actif à la fois, les périodes
                                    précédentes sont conservées pour le suivi et la paie.
                                </p>
                                {group.canChangeEnseignant && (
                                    <button
                                        type="button"
                                        className="btn btn-primary d-inline-flex align-items-center"
                                        onClick={openChangerEnseignant}
                                    >
                                        <i className="ti ti-repeat me-2" />
                                        Changer d'enseignant
                                    </button>
                                )}
                            </div>

                            <RelatedRecordsTable
                                isEmpty={group.enseignantsHistorique.length === 0}
                                emptyTitle="Aucun enseignant affecté"
                                emptyIcon="ti ti-chalkboard"
                                head={
                                    <tr>
                                        <th>Enseignant</th>
                                        <th>Date de début</th>
                                        <th>Date de fin</th>
                                        <th>Motif</th>
                                        <th>Statut</th>
                                        {group.canChangeEnseignant && <th className="text-end">Actions</th>}
                                    </tr>
                                }
                            >
                                {group.enseignantsHistorique.map((row) => (
                                    <tr key={row.id}>
                                        <td className="fw-medium">{row.enseignant ?? '—'}</td>
                                        <td>{row.dateDebut ?? '—'}</td>
                                        <td>{row.dateFin ?? '—'}</td>
                                        <td>{row.motif ?? '—'}</td>
                                        <td>
                                            <StatusBadge
                                                label={row.statut}
                                                variant={row.isActif ? 'success' : 'secondary'}
                                                dot
                                            />
                                        </td>
                                        {group.canChangeEnseignant && (
                                            <td className="text-end">
                                                <button
                                                    type="button"
                                                    className="btn btn-sm btn-outline-primary d-inline-flex align-items-center"
                                                    onClick={() => openModifierAffectation(row)}
                                                >
                                                    <i className="ti ti-edit me-1" aria-hidden="true" />
                                                    Modifier
                                                </button>
                                            </td>
                                        )}
                                    </tr>
                                ))}
                            </RelatedRecordsTable>
                        </>
                    )}

                    {activeTab === 'groupe' && (
                        <RelatedRecordsTable
                            isEmpty={false}
                            emptyTitle=""
                            head={
                                <tr>
                                    <th>Nom du groupe</th>
                                    <th>Niveau</th>
                                    <th>Enseignant</th>
                                    <th>Centre</th>
                                    <th>Année scolaire</th>
                                    <th>Étudiants</th>
                                    <th>Statistique</th>
                                    <th>Statut</th>
                                </tr>
                            }
                        >
                            <tr>
                                <td className="fw-medium">{group.nom}</td>
                                <td>{group.niveau ?? '—'}</td>
                                <td>{group.enseignant ?? '—'}</td>
                                <td>{group.centre ?? '—'}</td>
                                <td>{group.anneeScolaire ?? '—'}</td>
                                <td>
                                    <span className="badge badge-soft-info d-inline-flex align-items-center gap-1">
                                        {group.etudiantsDistinctsCount}
                                        <i className="ti ti-user" aria-hidden="true" />
                                    </span>
                                </td>
                                <td>
                                    <div className="d-flex flex-wrap gap-1">
                                        <span
                                            className="badge badge-soft-success d-inline-flex align-items-center gap-1"
                                            title="Inscriptions actives"
                                        >
                                            {group.inscriptionsActivesCount}
                                            <i className="ti ti-user" aria-hidden="true" />
                                        </span>
                                        <span
                                            className="badge badge-soft-secondary d-inline-flex align-items-center gap-1"
                                            title="Inscriptions en changement"
                                        >
                                            {group.inscriptionsChangementCount}
                                            <i className="ti ti-user" aria-hidden="true" />
                                        </span>
                                        <span
                                            className="badge badge-soft-danger d-inline-flex align-items-center gap-1"
                                            title="Inscriptions annulées"
                                        >
                                            {group.inscriptionsAnnuleesCount}
                                            <i className="ti ti-user" aria-hidden="true" />
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <StatusBadge label={group.statut} variant={statutVariant(group.statut)} dot />
                                </td>
                            </tr>
                        </RelatedRecordsTable>
                    )}
                </div>
            </div>

            <Modal
                show={showMoveYearModal}
                title="Déplacer le groupe vers une autre année scolaire"
                onClose={() => setShowMoveYearModal(false)}
                processing={moveYearForm.processing}
            >
                <form onSubmit={submitMoveYear} onKeyDown={blockImplicitSubmit}>
                    <div className="alert alert-warning">
                        Le groupe <strong>{group.nom}</strong> ({group.anneeScolaire ?? '—'}) sera déplacé avec{' '}
                        <strong>toutes ses inscriptions, séances et paiements</strong> — rien n&apos;est copié ni
                        supprimé, les mêmes enregistrements changent d&apos;année (mêmes effectifs avant et après). Chaque
                        modification est tracée dans le journal d&apos;audit.
                    </div>
                    <SelectField
                        id="move-year-annee"
                        label="Année scolaire de destination"
                        required
                        placeholder="Choisir une année…"
                        options={anneeOptions}
                        value={moveYearForm.data.annee_scolaire_id}
                        error={moveYearForm.errors.annee_scolaire_id}
                        onChange={(e) =>
                            moveYearForm.setData('annee_scolaire_id', e.target.value ? Number(e.target.value) : '')
                        }
                    />
                    <SelectField
                        id="move-year-statut"
                        label="Statut du groupe dans la nouvelle année"
                        placeholder="Conserver le statut actuel"
                        options={statutOptions}
                        value={moveYearForm.data.statut}
                        error={moveYearForm.errors.statut}
                        onChange={(e) => moveYearForm.setData('statut', e.target.value)}
                    />
                    <small className="text-muted d-block mb-3">
                        « Fin de formation » et « Annulée » écrivent l&apos;historique du groupe comme les actions
                        Terminer / Annuler habituelles. Un groupe déjà en « Fin de formation » ne peut plus changer de
                        statut.
                    </small>
                    <FormActions
                        submitLabel="Déplacer le groupe"
                        processing={moveYearForm.processing}
                        onCancel={() => setShowMoveYearModal(false)}
                    />
                </form>
            </Modal>

            <Modal
                show={showEnseignantModal}
                title="Changer d'enseignant"
                onClose={() => setShowEnseignantModal(false)}
                processing={changerForm.processing}
            >
                <form onSubmit={submitChangerEnseignant} onKeyDown={blockImplicitSubmit}>
                    <div className="alert alert-info d-flex align-items-start gap-2" role="alert">
                        <i className="ti ti-info-circle fs-18 mt-1" aria-hidden="true" />
                        <span>
                            L'enseignant actuel sera archivé à la date choisie et l'emploi du temps du groupe sera
                            arrêté. Vous devrez créer un nouvel emploi du temps pour le nouvel enseignant. Les séances
                            déjà effectuées ne sont pas modifiées.
                        </span>
                    </div>

                    <div className="row">
                        <div className="col-md-6">
                            <SelectField
                                id="grp-new-ens"
                                label="Nouvel enseignant"
                                options={enseignantOptions}
                                placeholder="Aucun enseignant"
                                value={changerForm.data.enseignant_id}
                                onChange={(event) =>
                                    changerForm.setData('enseignant_id', event.target.value ? Number(event.target.value) : '')
                                }
                                error={changerForm.errors.enseignant_id}
                            />
                        </div>
                        <div className="col-md-6">
                            <DateField
                                id="grp-ens-date"
                                label="Date de prise en charge"
                                required
                                value={changerForm.data.date_debut}
                                onChange={(event) => changerForm.setData('date_debut', event.target.value)}
                                error={changerForm.errors.date_debut}
                            />
                        </div>
                        <div className="col-12">
                            <FormField
                                id="grp-ens-motif"
                                label="Motif"
                                value={changerForm.data.motif}
                                onChange={(event) => changerForm.setData('motif', event.target.value)}
                                error={changerForm.errors.motif}
                                placeholder="ex : indisponibilité de l'enseignant"
                            />
                        </div>
                    </div>

                    <FormActions
                        processing={changerForm.processing}
                        onCancel={() => setShowEnseignantModal(false)}
                        submitLabel="Confirmer le changement"
                    />
                </form>
            </Modal>

            <Modal
                show={affectationEnCours !== null}
                title="Modifier la période d'affectation"
                onClose={() => setAffectationEnCours(null)}
                processing={affectationForm.processing}
            >
                <form onSubmit={submitModifierAffectation} onKeyDown={blockImplicitSubmit}>
                    <div className="alert alert-info d-flex align-items-start gap-2" role="alert">
                        <i className="ti ti-info-circle fs-18 mt-1" aria-hidden="true" />
                        <span>
                            Correction des dates et du motif de la période de{' '}
                            <strong>{affectationEnCours?.enseignant ?? '—'}</strong>. L'enseignant de cette période ne
                            change pas ici : pour le remplacer, utilisez « Changer d'enseignant ».
                        </span>
                    </div>

                    <div className="row">
                        <div className="col-md-6">
                            <DateField
                                id="grp-aff-debut"
                                label="Date de début"
                                required
                                value={affectationForm.data.date_debut}
                                onChange={(event) => affectationForm.setData('date_debut', event.target.value)}
                                error={affectationForm.errors.date_debut}
                            />
                        </div>
                        <div className="col-md-6">
                            {affectationEnCours?.isActif ? (
                                // The active period is still running — it has
                                // no end date to correct.
                                <>
                                    <DateField id="grp-aff-fin" label="Date de fin" value="" disabled />
                                    <p className="text-muted fs-12 mt-1 mb-0">Période en cours — sans date de fin.</p>
                                </>
                            ) : (
                                <DateField
                                    id="grp-aff-fin"
                                    label="Date de fin"
                                    value={affectationForm.data.date_fin}
                                    onChange={(event) => affectationForm.setData('date_fin', event.target.value)}
                                    error={affectationForm.errors.date_fin}
                                />
                            )}
                        </div>
                        <div className="col-12">
                            <FormField
                                id="grp-aff-motif"
                                label="Motif"
                                value={affectationForm.data.motif}
                                onChange={(event) => affectationForm.setData('motif', event.target.value)}
                                error={affectationForm.errors.motif}
                                placeholder="ex : indisponibilité de l'enseignant"
                            />
                        </div>
                    </div>

                    <FormActions
                        processing={affectationForm.processing}
                        onCancel={() => setAffectationEnCours(null)}
                        submitLabel="Enregistrer"
                    />
                </form>
            </Modal>
            <Modal
                show={reopenOpen}
                title="Rouvrir le groupe"
                onClose={() => setReopenOpen(false)}
                processing={reopenForm.processing}
            >
                <form onSubmit={submitReopen}>
                    <div className="alert alert-info d-flex gap-2" role="alert">
                        <i className="ti ti-info-circle fs-20" aria-hidden="true" />
                        <span>
                            Seul le <strong>statut</strong> du groupe est modifié. Les paiements, les inscriptions,
                            les séances et l&apos;historique de présence ne sont pas touchés.
                        </span>
                    </div>

                    <p className="mb-3">
                        Le groupe <strong>{group.nom}</strong> est actuellement «&nbsp;{group.statut}&nbsp;». Il va
                        ressortir de l&apos;onglet Historique et redevenir inscriptible.
                    </p>

                    <SelectField
                        id="reopen-statut"
                        label="Rouvrir avec le statut"
                        value={reopenForm.data.statut}
                        onChange={(event) => reopenForm.setData('statut', event.target.value)}
                        options={reopenOptions}
                        error={reopenForm.errors.statut}
                    />

                    <FormActions
                        processing={reopenForm.processing}
                        onCancel={() => setReopenOpen(false)}
                        submitLabel="Rouvrir"
                    />
                </form>
            </Modal>
            <ConfirmDialog
                show={archiveOpen}
                title="Terminer la formation"
                recordLabel={group.nom}
                message="Marquer ce groupe comme terminé (Fin de formation) ? Cette action est irréversible."
                error={archiveError}
                processing={archiveProcessing}
                onConfirm={archiveGroup}
                onCancel={() => setArchiveOpen(false)}
                variant="primary"
                icon="ti-archive"
                confirmLabel="Terminer"
            />
        </BackofficeLayout>
    );
}
