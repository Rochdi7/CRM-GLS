import { router, useForm } from '@inertiajs/react';
import { useRef, useState, type FormEvent } from 'react';
import { useAutoOpenCreate } from '@/Hooks/useAutoOpenCreate';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import PageTabs from '@/Components/Navigation/PageTabs';
import { STUDENTS_TABS } from '@/Config/pageTabs';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import FilterTextInput from '@/Components/Tables/FilterTextInput';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import DateField from '@/Components/Forms/DateField';
import FormField from '@/Components/Forms/FormField';
import SelectField from '@/Components/Forms/SelectField';
import TextareaField from '@/Components/Forms/TextareaField';
import PhoneField from '@/Components/Forms/PhoneField';
import FormActions from '@/Components/Forms/FormActions';
import SexeIcon from '@/Components/Details/SexeIcon';
import { splitPhone } from '@/Data/countries';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { useFilterReset } from '@/Hooks/useFilterReset';
import StatusBadge from '@/Components/Details/StatusBadge';
import TransferPaymentPicker from '@/Components/Students/TransferPaymentPicker';
import type { GroupeCibleTransfert, PaiementsTransferables, SelectOption, StudentRow, StudentsPageProps } from '@/Types';
import { enCapitales } from '@/Lib/nom';

/**
 * Ce qui empêche la suppression, servi par
 * `students.delete-blockers`. `forcable` vient du SERVEUR : la page ne
 * redérive jamais « peut-on forcer ? », sinon elle finirait par offrir un
 * bouton que `destroy()` refuse (CLAUDE.md §5).
 */
interface DeleteBlockers {
    inscriptions: number;
    encaissements: number;
    remboursements: number;
    cheques: number;
    presences: Array<{ id: number; date: string; statut: string; groupe: string }>;
    forcable: boolean;
}

interface StudentFormState {
    nom: string;
    prenom: string;
    sexe: string;
    date_naissance: string;
    phone_pays: string;
    telephone: string;
    whatsapp: string;
    email: string;
    adresse: string;
    cin: string;
    niveau: string;
    domaine: string;
    examen_type: string;
    etablissement_id: number | '';
    parent_nom: string;
    parent_relation: string;
    parent_sexe: string;
    parent_cin: string;
    parent_telephone: string;
    parent_whatsapp: string;
    note: string;
    photo: File | null;
}

/**
 * Demande de transfert vers un autre centre (25/09/2026). Le groupe
 * d'affectation dans le centre cible est OBLIGATOIRE : sans lui l'étudiant
 * arriverait sans dossier. Les groupes sont chargés à la volée pour le
 * centre choisi (student-transfers.groupes-cibles).
 */
interface TransferFormState {
    student_id: number | '';
    etablissement_cible_id: number | '';
    group_cible_id: number | '';
    motif: string;
    /** Paiements cochés (14/10/2026) — pré-remplis avec `defautIds` du serveur. */
    encaissement_ids: number[];
}

function emptyForm(defaultCountry: string, contextCenterId: number | null): StudentFormState {
    return {
        nom: '',
        prenom: '',
        sexe: '',
        date_naissance: '',
        phone_pays: defaultCountry,
        telephone: '',
        whatsapp: '',
        email: '',
        adresse: '',
        cin: '',
        niveau: '',
        domaine: '',
        examen_type: '',
        etablissement_id: contextCenterId ?? '',
        parent_nom: '',
        parent_relation: '',
        parent_sexe: '',
        parent_cin: '',
        parent_telephone: '',
        parent_whatsapp: '',
        note: '',
        photo: null,
    };
}

/**
 * Replaces App\Livewire\Backoffice\Students\StudentsIndex — same fields,
 * same search/filter/sort behavior, same modal add/edit + photo upload,
 * now driven by real HTTP requests (StudentController) instead of a Livewire
 * component. Server-side search/filter/pagination preserved exactly.
 */
export default function StudentsIndex({
    students,
    filters,
    studentOptions,
    niveauxInteret,
    domaines,
    examenTypes,
    sexes,
    parentRelations,
    niveauxAvecDomaine,
    niveauStudium,
    defaultCountry,
    etablissements,
    centerLocked,
    contextCenterId,
    transferCentres,
    permissions,
    tabCounts,
    archivesView,
}: StudentsPageProps) {
    const isLoading = useInertiaLoading();
    // Archiver (19/10/2026) : motif + date de retour obligatoires ; la fiche
    // sort de la liste courante vers l'onglet « Étudiants archivés ».
    const [archiveTarget, setArchiveTarget] = useState<StudentRow | null>(null);
    const archiveForm = useForm<{ motif: string; date_retour: string }>({ motif: '', date_retour: '' });
    const [reactivateTarget, setReactivateTarget] = useState<StudentRow | null>(null);
    const [reactivateError, setReactivateError] = useState<string | undefined>(undefined);
    const [reactivateProcessing, setReactivateProcessing] = useState(false);
    const listUrl = archivesView ? '/backoffice/students/archives' : '/backoffice/students';
    // Refus métier de l'action (fiche transférée / déjà archivée) — clé `archive`, hors des champs du formulaire.
    const archiveFormError = (archiveForm.errors as Record<string, string | undefined>).archive;
    const [transferTarget, setTransferTarget] = useState<StudentRow | null>(null);
    const [transferGroups, setTransferGroups] = useState<GroupeCibleTransfert[]>([]);
    const [transferGroupsLoading, setTransferGroupsLoading] = useState(false);
    const [transferPayments, setTransferPayments] = useState<PaiementsTransferables | null>(null);
    const [transferPaymentsLoading, setTransferPaymentsLoading] = useState(false);
    const transferForm = useForm<TransferFormState>({
        student_id: '',
        etablissement_cible_id: '',
        group_cible_id: '',
        motif: '',
        encaissement_ids: [],
    });
    const [showModal, setShowModal] = useState(false);
    const [editingStudent, setEditingStudent] = useState<StudentRow | null>(null);
    const [existingPhotoUrl, setExistingPhotoUrl] = useState<string | null>(null);
    const [photoPreview, setPhotoPreview] = useState<string | null>(null);
    const [activeTab, setActiveTab] = useState<'contact' | 'parent' | 'autre'>('contact');
    const [deleteTarget, setDeleteTarget] = useState<StudentRow | null>(null);
    const [deleteError, setDeleteError] = useState<string | undefined>(undefined);
    const [deleteProcessing, setDeleteProcessing] = useState(false);
    const [deleteBlockers, setDeleteBlockers] = useState<DeleteBlockers | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const form = useForm<StudentFormState>(emptyForm(defaultCountry, contextCenterId));

    const studentFilterOptions: SelectOption[] = studentOptions.map((s) => ({ value: s.id, label: s.label }));
    const niveauOptions: SelectOption[] = niveauxInteret.map((n) => ({ value: n, label: n }));
    const domaineOptions: SelectOption[] = domaines.map((d) => ({ value: d, label: d }));
    const examenOptions: SelectOption[] = examenTypes.map((e) => ({ value: e, label: e }));
    const parentRelationOptions: SelectOption[] = parentRelations.map((r) => ({ value: r, label: r }));
    const centerOptions: SelectOption[] = etablissements.map((etab) => ({ value: etab.id, label: etab.nom_centre }));
    const niveauFilterOptions: SelectOption[] = niveauxInteret.map((n) => ({ value: n, label: n }));
    const sexeFilterOptions: SelectOption[] = sexes.map((s) => ({ value: s, label: s }));
    // État d'inscription (reference CRM's filter) — machine values, French labels.
    const inscriptionFilterOptions: SelectOption[] = [
        { value: 'active', label: 'Avec une inscription active' },
        { value: 'cancelled', label: 'Inscription annulée ou archivée' },
        { value: 'none', label: 'Sans inscription' },
    ];
    const livreFilterOptions: SelectOption[] = [
        { value: 'avec', label: 'Avec livre' },
        { value: 'sans', label: 'Sans livre' },
    ];

    const showsDomaine = niveauxAvecDomaine.includes(form.data.niveau);
    const showsExamen = form.data.niveau === niveauStudium;

    function reload(nextFilters: Partial<typeof filters>) {
        router.get(
            listUrl,
            { ...filters, ...nextFilters, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const filterReset = useFilterReset(filters, reload, { perPage: filters.perPage });

    function openArchive(student: StudentRow) {
        archiveForm.clearErrors();
        archiveForm.setData({ motif: '', date_retour: '' });
        setArchiveTarget(student);
    }

    function closeArchive() {
        setArchiveTarget(null);
        archiveForm.reset();
        archiveForm.clearErrors();
    }

    function submitArchive(event: FormEvent) {
        event.preventDefault();
        if (!archiveTarget) {
            return;
        }
        archiveForm.post(`/backoffice/students/${archiveTarget.id}/archiver`, {
            preserveScroll: true,
            onSuccess: () => closeArchive(),
        });
    }

    function handleReactivate() {
        if (!reactivateTarget) {
            return;
        }
        setReactivateProcessing(true);
        router.post(
            `/backoffice/students/${reactivateTarget.id}/reactiver`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setReactivateTarget(null);
                    setReactivateError(undefined);
                },
                onError: (errors) => setReactivateError(errors.archive ?? 'Réactivation impossible.'),
                onFinish: () => setReactivateProcessing(false),
            },
        );
    }

    function toggleAgeSort() {
        reload({ ageSort: filters.ageSort === 'asc' ? 'desc' : 'asc' });
    }

    async function openTransfer(student: StudentRow) {
        transferForm.clearErrors();
        transferForm.setData({ student_id: student.id, etablissement_cible_id: '', group_cible_id: '', motif: '', encaissement_ids: [] });
        setTransferGroups([]);
        setTransferPayments(null);
        setTransferTarget(student);
        setTransferPaymentsLoading(true);
        try {
            const response = await fetch(`/backoffice/student-transfers/paiements/${student.id}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const tree = response.ok ? ((await response.json()) as PaiementsTransferables) : null;
            setTransferPayments(tree);
            // La sélection par défaut vient du serveur : avances + dossiers actifs.
            transferForm.setData((data) => ({ ...data, encaissement_ids: tree ? [...tree.defautIds] : [] }));
        } catch {
            setTransferPayments(null);
        } finally {
            setTransferPaymentsLoading(false);
        }
    }

    function closeTransfer() {
        setTransferTarget(null);
        setTransferGroups([]);
        setTransferPayments(null);
        transferForm.reset();
        transferForm.clearErrors();
    }

    async function chooseTransferCentre(value: string) {
        const centreId = value === '' ? '' : Number(value);
        transferForm.setData((data) => ({ ...data, etablissement_cible_id: centreId, group_cible_id: '' }));
        setTransferGroups([]);
        if (centreId === '') {
            return;
        }
        setTransferGroupsLoading(true);
        try {
            const response = await fetch(`/backoffice/student-transfers/groupes-cibles/${centreId}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            setTransferGroups(response.ok ? ((await response.json()) as GroupeCibleTransfert[]) : []);
        } catch {
            setTransferGroups([]);
        } finally {
            setTransferGroupsLoading(false);
        }
    }

    function submitTransfer(event: FormEvent) {
        event.preventDefault();
        transferForm.post('/backoffice/student-transfers', {
            preserveScroll: true,
            onSuccess: () => closeTransfer(),
        });
    }

    const transferCentreOptions: SelectOption[] = transferCentres
        .filter((c) => c.id !== transferTarget?.etablissementId)
        .map((c) => ({ value: c.id, label: c.nomCentre }));
    const transferGroupOptions: SelectOption[] = transferGroups.map((g) => ({
        value: g.id,
        label: `${g.nom}${g.niveau ? ` - ${g.niveau}` : ''}${g.annee ? ` (${g.annee})` : ''}`,
    }));

    function openCreate() {
        setEditingStudent(null);
        setExistingPhotoUrl(null);
        setPhotoPreview(null);
        setActiveTab('contact');
        form.reset();
        form.clearErrors();
        form.setData(emptyForm(defaultCountry, contextCenterId));
        setShowModal(true);
    }

    // Raccourci « Actions rapides » du tableau de bord : ?nouveau=1 ouvre
    // directement ce formulaire (confort d'interface seulement, §5).
    useAutoOpenCreate(openCreate, permissions.create);

    function openEdit(student: StudentRow) {
        setEditingStudent(student);
        setExistingPhotoUrl(student.photoUrl);
        setPhotoPreview(null);
        setActiveTab('contact');
        form.clearErrors();
        const [phonePays, telephone] = splitPhone(student.telephone);
        const [, whatsapp] = splitPhone(student.whatsapp);
        const [, parentTelephone] = splitPhone(student.parentTelephone);
        const [, parentWhatsapp] = splitPhone(student.parentWhatsapp);
        form.setData({
            nom: student.nom,
            prenom: student.prenom,
            sexe: student.sexe ?? '',
            date_naissance: student.dateNaissance ?? '',
            phone_pays: phonePays,
            telephone,
            whatsapp,
            email: student.email ?? '',
            adresse: student.adresse ?? '',
            cin: student.cin ?? '',
            niveau: student.niveau ?? '',
            domaine: student.domaine ?? '',
            examen_type: student.examenType ?? '',
            etablissement_id: student.etablissementId ?? '',
            parent_nom: student.parentNom ?? '',
            parent_relation: student.parentRelation ?? '',
            parent_sexe: student.parentSexe ?? '',
            parent_cin: student.parentCin ?? '',
            parent_telephone: parentTelephone,
            parent_whatsapp: parentWhatsapp,
            note: student.note ?? '',
            photo: null,
        });
        setShowModal(true);
    }

    function closeModal() {
        setShowModal(false);
        setEditingStudent(null);
        setExistingPhotoUrl(null);
        setPhotoPreview(null);
        form.reset();
        form.clearErrors();
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }

    function handleNiveauChange(value: string) {
        form.setData('niveau', value);
        if (!niveauxAvecDomaine.includes(value)) {
            form.setData('domaine', '');
        }
        if (value !== niveauStudium) {
            form.setData('examen_type', '');
        }
    }

    function handlePhotoChange(event: React.ChangeEvent<HTMLInputElement>) {
        const file = event.target.files?.[0] ?? null;
        form.setData('photo', file);

        if (file) {
            const reader = new FileReader();
            reader.onload = () => setPhotoPreview(reader.result as string);
            reader.readAsDataURL(file);
        } else {
            setPhotoPreview(null);
        }
    }

    function submit(event: FormEvent) {
        event.preventDefault();

        if (editingStudent) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(`/backoffice/students/${editingStudent.id}`, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => closeModal(),
                // Reset the transform so a later create on this shared form
                // doesn't POST with a stale _method=put (Phase 12 UX fix).
                onFinish: () => form.transform((data) => data),
            });

            return;
        }

        form.post('/backoffice/students', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }

    function confirmDelete(student: StudentRow) {
        setDeleteTarget(student);
        setDeleteError(undefined);
        setDeleteBlockers(null);

        // Ce qui bloque, AVANT d'échouer : le modal doit pouvoir nommer les
        // lignes d'appel au lieu de renvoyer « historique d'activité ».
        // Lecture seule — la décision reste au serveur dans destroy().
        fetch(`/backoffice/students/${student.id}/delete-blockers`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then((r) => (r.ok ? r.json() : null))
            .then((data: DeleteBlockers | null) => setDeleteBlockers(data))
            .catch(() => setDeleteBlockers(null));
    }

    function handleDelete(force = false) {
        if (!deleteTarget) {
            return;
        }

        setDeleteProcessing(true);
        router.delete(`/backoffice/students/${deleteTarget.id}`, {
            data: force ? { force: true } : {},
            preserveScroll: true,
            onSuccess: () => {
                setDeleteTarget(null);
                setDeleteError(undefined);
                setDeleteBlockers(null);
            },
            onError: (errors) => {
                setDeleteError(errors.delete ?? 'Suppression impossible.');
            },
            onFinish: () => setDeleteProcessing(false),
        });
    }

    return (
        <BackofficeLayout
            title={archivesView ? 'Étudiants archivés' : 'Étudiants'}
            breadcrumbs={
                archivesView
                    ? [
                          { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                          { label: 'Étudiants', href: '/backoffice/students' },
                          { label: 'Étudiants archivés' },
                      ]
                    : [{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Étudiants' }]
            }
            actions={
                permissions.create && !archivesView ? (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                        <i className="ti ti-square-rounded-plus me-2" />
                        Ajouter un étudiant
                    </button>
                ) : undefined
            }
        >
            <PageTabs tabs={STUDENTS_TABS} counts={tabCounts} />

            <Card title={archivesView ? 'Étudiants archivés' : 'Étudiants'} bodyClassName="p-0 py-3">
                {archivesView && (
                    <div className="alert alert-info mx-3 mt-2 mb-0 fs-13 d-flex align-items-start" role="alert">
                        <i className="ti ti-archive me-2 mt-1" aria-hidden="true" />
                        <div>
                            Fiches mises en pause : l'étudiant a interrompu ses cours et doit revenir à la date indiquée.
                            Rien d'autre ne bouge (inscriptions, présences, paiements). À son retour, « Réactiver » le
                            remet dans la liste des étudiants.
                        </div>
                    </div>
                )}
                {/* Per-column filter row (reference CRM's Étudiants filters,
                    without Catégorie d'âge) — replaces the single search box
                    AND the "Filtrer" dropdown (Niveau/Centre moved in here). */}
                <div className="px-3 pt-2">
                    <TableToolbar onReset={filterReset.reset} resetActive={filterReset.active}>
                        <div style={{ width: 150 }}>
                            <label className="form-label" htmlFor="stu-f-reference">
                                Référence
                            </label>
                            <FilterTextInput
                                id="stu-f-reference"
                                value={filters.referenceFilter}
                                onChange={(value) => reload({ referenceFilter: value })}
                                placeholder="ex : E95"
                            />
                        </div>
                        <div style={{ width: 240 }}>
                            <label className="form-label" htmlFor="stu-f-student">
                                Étudiant
                            </label>
                            <SelectField
                                id="stu-f-student"
                                options={studentFilterOptions}
                                uppercase
                                placeholder="Choisir un étudiant"
                                value={filters.studentFilter}
                                onChange={(event) => reload({ studentFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 160 }}>
                            <label className="form-label" htmlFor="stu-f-telephone">
                                Téléphone
                            </label>
                            <FilterTextInput
                                id="stu-f-telephone"
                                value={filters.telephoneFilter}
                                onChange={(value) => reload({ telephoneFilter: value })}
                                placeholder="ex : 2126619"
                            />
                        </div>
                        <div style={{ width: 180 }}>
                            <label className="form-label" htmlFor="stu-f-sexe">
                                Sexe
                            </label>
                            <SelectField
                                id="stu-f-sexe"
                                options={sexeFilterOptions}
                                placeholder="Sélectionner le sexe"
                                value={filters.sexeFilter}
                                onChange={(event) => reload({ sexeFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 240 }}>
                            <label className="form-label" htmlFor="stu-f-inscription">
                                État d'inscription
                            </label>
                            <SelectField
                                id="stu-f-inscription"
                                options={inscriptionFilterOptions}
                                placeholder="Tous"
                                value={filters.inscriptionFilter}
                                onChange={(event) => reload({ inscriptionFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 180 }}>
                            <label className="form-label" htmlFor="stu-f-livre">
                                Livre
                            </label>
                            <SelectField
                                id="stu-f-livre"
                                options={livreFilterOptions}
                                placeholder="Tous"
                                value={filters.livreFilter}
                                onChange={(event) => reload({ livreFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 180 }}>
                            <label className="form-label" htmlFor="stu-f-niveau">
                                Intéressé par
                            </label>
                            <SelectField
                                id="stu-f-niveau"
                                options={niveauFilterOptions}
                                placeholder="Tous"
                                value={filters.niveauFilter}
                                onChange={(event) => reload({ niveauFilter: event.target.value })}
                            />
                        </div>
                        {!centerLocked && (
                            <div style={{ width: 220 }}>
                                <label className="form-label" htmlFor="stu-f-centre">
                                    Centre
                                </label>
                                <SelectField
                                    id="stu-f-centre"
                                    options={centerOptions}
                                    placeholder="Tous les centres"
                                    value={filters.etablissementFilter}
                                    onChange={(event) => reload({ etablissementFilter: event.target.value })}
                                />
                            </div>
                        )}
                    </TableToolbar>
                </div>


                {students.data.length === 0 ? (
                    archivesView ? (
                        <EmptyState
                            title="Aucun étudiant archivé"
                            message="Les fiches mises en pause (menu « ⋯ » → « Archiver » sur un étudiant) apparaîtront ici."
                            icon="ti ti-archive"
                        />
                    ) : (
                        <EmptyState title="Aucun étudiant" message="Ajoutez votre premier étudiant pour commencer." icon="ti ti-school" />
                    )
                ) : (
                    <>
                        <DataTable
                            loading={isLoading}
                            head={
                                <tr>
                                    <th />
                                    <th>Référence</th>
                                    <th>Nom</th>
                                    <th>Intéressé par</th>
                                    <th>
                                        <a
                                            href="#"
                                            onClick={(event) => {
                                                event.preventDefault();
                                                toggleAgeSort();
                                            }}
                                            className="text-dark d-inline-flex align-items-center"
                                        >
                                            Âge
                                            <i
                                                className={`ti ${filters.ageSort === 'asc' ? 'ti-sort-ascending' : filters.ageSort === 'desc' ? 'ti-sort-descending' : 'ti-arrows-sort'} ms-1 text-muted`}
                                            />
                                        </a>
                                    </th>
                                    {!centerLocked && <th>Centre</th>}
                                    {archivesView && (
                                        <>
                                            <th>Motif</th>
                                            <th>Retour prévu</th>
                                            <th>Archivé le</th>
                                            <th>Par</th>
                                        </>
                                    )}
                                    <th>Livre</th>
                                    <th>Téléphone</th>
                                    <th>WhatsApp</th>
                                    <th className="text-end">Action</th>
                                </tr>
                            }
                        >
                            {students.data.map((student) => (
                                <tr key={student.id}>
                                    <td>
                                        <SexeIcon sexe={student.sexe} />
                                    </td>
                                    <td>
                                        <code>{student.reference}</code>
                                    </td>
                                    <td>
                                        <a
                                            href={permissions.view ? `/backoffice/students/${student.id}` : undefined}
                                            className="d-flex align-items-center text-dark"
                                        >
                                            <span className="avatar avatar-sm rounded-circle bg-primary-transparent me-2 d-inline-flex align-items-center justify-content-center overflow-hidden">
                                                {student.photoThumbUrl ? (
                                                    <img
                                                        src={student.photoThumbUrl}
                                                        alt=""
                                                        className="w-100 h-100"
                                                        style={{ objectFit: 'cover' }}
                                                        loading="lazy"
                                                    />
                                                ) : (
                                                    <span className="fw-bold text-primary">{student.prenom.charAt(0).toUpperCase()}</span>
                                                )}
                                            </span>
                                            <span className="fw-medium">{student.nomComplet}</span>
                                        </a>
                                        {student.statut === 'Transféré' && (
                                            <div className="mt-1">
                                                <StatusBadge label="Transféré" variant="secondary" dot />
                                                {student.transfereVers && (
                                                    <a href={`/backoffice/students/${student.transfereVers.id}`} className="ms-1 fs-12">
                                                        vers {student.transfereVers.centre ?? '-'}
                                                    </a>
                                                )}
                                            </div>
                                        )}
                                        {student.statut === 'Archivé' && (
                                            <div className="mt-1">
                                                <StatusBadge
                                                    label={student.archiveRetourDepasse ? 'Archivé — retour dépassé' : 'Archivé'}
                                                    variant={student.archiveRetourDepasse ? 'danger' : 'warning'}
                                                    dot
                                                />
                                                {student.archiveDateRetour && !archivesView && (
                                                    <span className="ms-1 fs-12 text-muted">retour prévu le {student.archiveDateRetour}</span>
                                                )}
                                            </div>
                                        )}
                                        {student.transfereDepuis && (
                                            <div className="mt-1 fs-12 text-muted">
                                                <i className="ti ti-arrows-exchange me-1" />
                                                Transféré depuis{' '}
                                                <a href={`/backoffice/students/${student.transfereDepuis.id}`}>
                                                    {student.transfereDepuis.centre ?? student.transfereDepuis.reference}
                                                </a>
                                            </div>
                                        )}
                                    </td>
                                    <td>
                                        {student.niveau ? (
                                            <>
                                                <span className="badge badge-soft-info">{student.niveau}</span>
                                                {student.orientation && (
                                                    <span className="badge badge-soft-secondary ms-1">{student.orientation}</span>
                                                )}
                                            </>
                                        ) : (
                                            '-'
                                        )}
                                    </td>
                                    <td>{student.age ?? '-'}</td>
                                    {!centerLocked && <td>{student.etablissement ?? '-'}</td>}
                                    {archivesView && (
                                        <>
                                            <td className="text-normal-case" style={{ maxWidth: 260, whiteSpace: 'normal' }}>
                                                {student.archiveMotif ?? '-'}
                                            </td>
                                            <td>
                                                {student.archiveDateRetour ? (
                                                    <span className={student.archiveRetourDepasse ? 'text-danger fw-semibold' : ''}>
                                                        {student.archiveDateRetour}
                                                        {student.archiveRetourDepasse && (
                                                            <span className="badge badge-soft-danger ms-1">Dépassé</span>
                                                        )}
                                                    </span>
                                                ) : (
                                                    '-'
                                                )}
                                            </td>
                                            <td>{student.archiveLe ?? '-'}</td>
                                            <td>{student.archivePar ?? '-'}</td>
                                        </>
                                    )}
                                    <td>
                                        {student.livres.length === 0 ? (
                                            <span className="badge badge-soft-secondary">Sans livre</span>
                                        ) : (
                                            student.livres.map((livre, index) => (
                                                <span
                                                    key={index}
                                                    className="badge badge-soft-success me-1 mb-1"
                                                    title={[livre.groupe, livre.date ? `remis le ${livre.date}` : null]
                                                        .filter(Boolean)
                                                        .join(' — ')}
                                                >
                                                    <i className="ti ti-book me-1" />
                                                    {livre.nom}
                                                </span>
                                            ))
                                        )}
                                    </td>
                                    <td>{student.telephone ?? '-'}</td>
                                    <td>{student.whatsapp ?? '-'}</td>
                                    <td className="text-end">
                                        {(permissions.update || permissions.delete || permissions.transfer || permissions.archive) && (
                                            <RowActions>
                                                {permissions.update && (
                                                    <RowActionItem icon="ti-edit" onClick={() => openEdit(student)}>
                                                        Modifier
                                                    </RowActionItem>
                                                )}
                                                {permissions.archive && student.statut === 'Actif' && (
                                                    <RowActionItem icon="ti-archive" onClick={() => openArchive(student)}>
                                                        Archiver
                                                    </RowActionItem>
                                                )}
                                                {permissions.archive && student.statut === 'Archivé' && (
                                                    <RowActionItem
                                                        icon="ti-archive-off"
                                                        onClick={() => {
                                                            setReactivateError(undefined);
                                                            setReactivateTarget(student);
                                                        }}
                                                    >
                                                        Réactiver
                                                    </RowActionItem>
                                                )}
                                                {permissions.transfer && student.statut === 'Actif' && (
                                                    <RowActionItem icon="ti-arrows-exchange" onClick={() => void openTransfer(student)}>
                                                        Demander un transfert
                                                    </RowActionItem>
                                                )}
                                                {permissions.delete && (
                                                    <RowActionItem icon="ti-trash" danger onClick={() => confirmDelete(student)}>
                                                        Supprimer
                                                    </RowActionItem>
                                                )}
                                            </RowActions>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </DataTable>
                        <Pagination paginator={students} />
                    </>
                )}
            </Card>

            <Modal
                show={showModal}
                title={editingStudent ? "Modifier l'étudiant" : 'Ajouter un étudiant'}
                onClose={closeModal}
                processing={form.processing}
                size="xl"
            >
                <form onSubmit={submit}>
                    <div className="d-flex align-items-center mb-4">
                        <span className="avatar avatar-xl rounded-circle bg-light me-3 overflow-hidden d-inline-flex align-items-center justify-content-center">
                            {photoPreview ? (
                                <img src={photoPreview} alt="" className="w-100 h-100" style={{ objectFit: 'cover' }} />
                            ) : existingPhotoUrl ? (
                                <img src={existingPhotoUrl} alt="" className="w-100 h-100" style={{ objectFit: 'cover' }} />
                            ) : (
                                <i className="ti ti-camera fs-24 text-muted" />
                            )}
                        </span>
                        <div>
                            <label className="form-label mb-1" htmlFor="stu-photo">
                                Photo
                            </label>
                            <input
                                ref={fileInputRef}
                                id="stu-photo"
                                type="file"
                                accept="image/*"
                                className={`form-control form-control-sm${form.errors.photo ? ' is-invalid' : ''}`}
                                onChange={handlePhotoChange}
                            />
                            {form.errors.photo && <div className="invalid-feedback d-block">{form.errors.photo}</div>}
                        </div>
                    </div>

                    <div className="row">
                        <div className="col-md-4">
                            <FormField
                                id="stu-nom"
                                label="Nom"
                                required
                                value={form.data.nom}
                                onChange={(event) => form.setData('nom', event.target.value)}
                                error={form.errors.nom}
                                placeholder="ex : Rafik"
                            />
                        </div>
                        <div className="col-md-4">
                            <FormField
                                id="stu-prenom"
                                label="Prénom"
                                required
                                value={form.data.prenom}
                                onChange={(event) => form.setData('prenom', event.target.value)}
                                error={form.errors.prenom}
                                placeholder="ex : Mohammed"
                            />
                        </div>
                        <div className="col-md-4">
                            <div className="mb-3">
                                <label className="form-label d-block">Genre</label>
                                <div className="d-flex gap-2" role="group" aria-label="Genre">
                                    {sexes.map((sexe) => (
                                        <div key={sexe} className="flex-fill d-grid">
                                            <input
                                                type="radio"
                                                className="btn-check"
                                                name="stu-sexe"
                                                id={`stu-sexe-${sexe}`}
                                                value={sexe}
                                                checked={form.data.sexe === sexe}
                                                onChange={() => form.setData('sexe', sexe)}
                                                autoComplete="off"
                                            />
                                            <label
                                                className="btn btn-outline-primary d-inline-flex align-items-center justify-content-center px-4"
                                                htmlFor={`stu-sexe-${sexe}`}
                                            >
                                                <i className={`ti ${sexe === 'Homme' ? 'ti-man' : 'ti-woman'} me-1`} />
                                                {sexe === 'Homme' ? 'Masculin' : 'Féminin'}
                                            </label>
                                        </div>
                                    ))}
                                </div>
                                {form.errors.sexe && <div className="text-danger fs-12 mt-1">{form.errors.sexe}</div>}
                            </div>
                        </div>
                        <div className="col-md-4">
                            <DateField
                                id="stu-naissance"
                                label="Date de naissance"
                                value={form.data.date_naissance}
                                onChange={(event) => form.setData('date_naissance', event.target.value)}
                                error={form.errors.date_naissance}
                            />
                        </div>
                        <div className="col-md-4">
                            <FormField
                                id="stu-cin"
                                label="Carte d'identité"
                                value={form.data.cin}
                                onChange={(event) => form.setData('cin', event.target.value)}
                                error={form.errors.cin}
                                placeholder="ex : AB123456"
                            />
                        </div>
                        <div className="col-md-4">
                            <SelectField
                                id="stu-niveau"
                                label="Intéressé par"
                                options={niveauOptions}
                                placeholder="Choisir…"
                                value={form.data.niveau}
                                onChange={(event) => handleNiveauChange(event.target.value)}
                                error={form.errors.niveau}
                            />
                        </div>
                        {showsDomaine && (
                            <div className="col-md-4">
                                <SelectField
                                    id="stu-domaine"
                                    label="Domaine"
                                    required
                                    options={domaineOptions}
                                    placeholder="Choisir…"
                                    value={form.data.domaine}
                                    onChange={(event) => form.setData('domaine', event.target.value)}
                                    error={form.errors.domaine}
                                />
                            </div>
                        )}
                        {showsExamen && (
                            <div className="col-md-4">
                                <SelectField
                                    id="stu-examen"
                                    label="Examen d'entrée"
                                    required
                                    options={examenOptions}
                                    placeholder="Choisir…"
                                    value={form.data.examen_type}
                                    onChange={(event) => form.setData('examen_type', event.target.value)}
                                    error={form.errors.examen_type}
                                />
                            </div>
                        )}
                    </div>

                    <ul className="nav nav-tabs p-0 border-bottom rounded-0 mt-2 mb-4" role="tablist">
                        <li className="nav-item">
                            <button
                                type="button"
                                className={`nav-link d-inline-flex align-items-center${activeTab === 'contact' ? ' active' : ''}`}
                                onClick={() => setActiveTab('contact')}
                            >
                                <i className="ti ti-mail me-1" />
                                Contact
                            </button>
                        </li>
                        <li className="nav-item">
                            <button
                                type="button"
                                className={`nav-link d-inline-flex align-items-center${activeTab === 'parent' ? ' active' : ''}`}
                                onClick={() => setActiveTab('parent')}
                            >
                                <i className="ti ti-user me-1" />
                                Parent
                            </button>
                        </li>
                        <li className="nav-item">
                            <button
                                type="button"
                                className={`nav-link d-inline-flex align-items-center${activeTab === 'autre' ? ' active' : ''}`}
                                onClick={() => setActiveTab('autre')}
                            >
                                <i className="ti ti-info-circle me-1" />
                                Autres informations
                            </button>
                        </li>
                    </ul>

                    {activeTab === 'contact' && (
                        <div className="row">
                            <div className="col-md-4">
                                <PhoneField
                                    id="stu-tel"
                                    label="Téléphone"
                                    countryIso={form.data.phone_pays}
                                    national={form.data.telephone}
                                    onCountryChange={(iso) => form.setData('phone_pays', iso)}
                                    onNationalChange={(value) => form.setData('telephone', value)}
                                    error={form.errors.telephone}
                                />
                            </div>
                            <div className="col-md-4">
                                <PhoneField
                                    id="stu-wa"
                                    label="WhatsApp"
                                    countryIso={form.data.phone_pays}
                                    national={form.data.whatsapp}
                                    onCountryChange={(iso) => form.setData('phone_pays', iso)}
                                    onNationalChange={(value) => form.setData('whatsapp', value)}
                                    error={form.errors.whatsapp}
                                />
                            </div>
                            <div className="col-md-4">
                                <FormField
                                    id="stu-email"
                                    label="Email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(event) => form.setData('email', event.target.value)}
                                    error={form.errors.email}
                                    placeholder="ex : nom@domaine.com"
                                />
                            </div>
                            <div className="col-md-4">
                                <FormField
                                    id="stu-adresse"
                                    label="Adresse"
                                    value={form.data.adresse}
                                    onChange={(event) => form.setData('adresse', event.target.value)}
                                    error={form.errors.adresse}
                                    placeholder="ex : 7 rue des fleurs"
                                />
                            </div>
                            {!centerLocked && (
                                <div className="col-md-4">
                                    <SelectField
                                        id="stu-etab"
                                        label="Centre"
                                        options={centerOptions}
                                        placeholder="Choisir…"
                                        value={form.data.etablissement_id}
                                        onChange={(event) =>
                                            form.setData('etablissement_id', event.target.value ? Number(event.target.value) : '')
                                        }
                                        error={form.errors.etablissement_id}
                                    />
                                </div>
                            )}
                        </div>
                    )}

                    {activeTab === 'parent' && (
                        <div className="row">
                            <div className="col-md-4">
                                <SelectField
                                    id="stu-prelation"
                                    label="Catégorie"
                                    options={parentRelationOptions}
                                    placeholder="Choisir la relation"
                                    value={form.data.parent_relation}
                                    onChange={(event) => form.setData('parent_relation', event.target.value)}
                                    error={form.errors.parent_relation}
                                />
                            </div>
                            <div className="col-md-4">
                                <FormField
                                    id="stu-pnom"
                                    label="Nom du parent"
                                    value={form.data.parent_nom}
                                    onChange={(event) => form.setData('parent_nom', event.target.value)}
                                    error={form.errors.parent_nom}
                                    placeholder="ex : Alaoui"
                                />
                            </div>
                            <div className="col-md-4">
                                <div className="mb-3">
                                    <label className="form-label d-block">Genre</label>
                                    <div className="d-flex gap-2" role="group" aria-label="Genre du parent">
                                        {sexes.map((sexe) => (
                                            <div key={sexe} className="flex-fill d-grid">
                                                <input
                                                    type="radio"
                                                    className="btn-check"
                                                    name="stu-psexe"
                                                    id={`stu-psexe-${sexe}`}
                                                    value={sexe}
                                                    checked={form.data.parent_sexe === sexe}
                                                    onChange={() => form.setData('parent_sexe', sexe)}
                                                    autoComplete="off"
                                                />
                                                <label
                                                    className="btn btn-outline-primary d-inline-flex align-items-center justify-content-center px-4"
                                                    htmlFor={`stu-psexe-${sexe}`}
                                                >
                                                    <i className={`ti ${sexe === 'Homme' ? 'ti-man' : 'ti-woman'} me-1`} />
                                                    {sexe === 'Homme' ? 'Masculin' : 'Féminin'}
                                                </label>
                                            </div>
                                        ))}
                                    </div>
                                    {form.errors.parent_sexe && <div className="text-danger fs-12 mt-1">{form.errors.parent_sexe}</div>}
                                </div>
                            </div>
                            <div className="col-md-4">
                                <FormField
                                    id="stu-pcin"
                                    label="CIN"
                                    value={form.data.parent_cin}
                                    onChange={(event) => form.setData('parent_cin', event.target.value)}
                                    error={form.errors.parent_cin}
                                    placeholder="ex : AB123456"
                                />
                            </div>
                            <div className="col-md-4">
                                <PhoneField
                                    id="stu-ptel"
                                    label="Téléphone du parent"
                                    countryIso={form.data.phone_pays}
                                    national={form.data.parent_telephone}
                                    onCountryChange={(iso) => form.setData('phone_pays', iso)}
                                    onNationalChange={(value) => form.setData('parent_telephone', value)}
                                    error={form.errors.parent_telephone}
                                />
                            </div>
                            <div className="col-md-4">
                                <PhoneField
                                    id="stu-pwa"
                                    label="WhatsApp du parent"
                                    countryIso={form.data.phone_pays}
                                    national={form.data.parent_whatsapp}
                                    onCountryChange={(iso) => form.setData('phone_pays', iso)}
                                    onNationalChange={(value) => form.setData('parent_whatsapp', value)}
                                    error={form.errors.parent_whatsapp}
                                />
                            </div>
                        </div>
                    )}

                    {activeTab === 'autre' && (
                        <TextareaField
                            id="stu-note"
                            label="Note"
                            rows={3}
                            value={form.data.note}
                            onChange={(event) => form.setData('note', event.target.value)}
                            error={form.errors.note}
                            placeholder="Notes complémentaires sur cet étudiant"
                        />
                    )}

                    <div className="d-flex justify-content-end gap-2 mt-4">
                        <FormActions onCancel={closeModal} processing={form.processing} />
                    </div>
                </form>
            </Modal>

            <Modal
                show={transferTarget !== null}
                title="Demander un transfert vers un autre centre"
                onClose={closeTransfer}
                processing={transferForm.processing}
                size="lg"
            >
                <form onSubmit={submitTransfer}>
                    <div className="alert alert-info fs-13" role="alert">
                        <i className="ti ti-info-circle me-1" />
                        Validation par le backoffice. <strong className="text-uppercase">{transferTarget?.nomComplet}</strong> sera inscrit(e)
                        dans le nouveau groupe avec les paiements cochés ci-dessous ; le reste demeure sur la fiche d'origine.
                    </div>
                    {transferForm.errors.student_id && (
                        <div className="alert alert-danger fs-13" role="alert">
                            {transferForm.errors.student_id}
                        </div>
                    )}
                    <SelectField
                        id="tr-centre"
                        label="Centre cible"
                        options={transferCentreOptions}
                        placeholder="Choisir un centre"
                        value={transferForm.data.etablissement_cible_id}
                        onChange={(event) => void chooseTransferCentre(event.target.value)}
                        error={transferForm.errors.etablissement_cible_id}
                        required
                    />
                    <SelectField
                        id="tr-groupe"
                        label="Groupe d'affectation dans le centre cible"
                        options={transferGroupOptions}
                        placeholder={
                            transferForm.data.etablissement_cible_id === ''
                                ? "Choisir d'abord un centre"
                                : transferGroupsLoading
                                  ? 'Chargement des groupes...'
                                  : transferGroups.length === 0
                                    ? 'Aucun groupe ouvert dans ce centre'
                                    : 'Choisir un groupe'
                        }
                        value={transferForm.data.group_cible_id}
                        onChange={(event) =>
                            transferForm.setData('group_cible_id', event.target.value === '' ? '' : Number(event.target.value))
                        }
                        error={transferForm.errors.group_cible_id}
                        disabled={transferForm.data.etablissement_cible_id === '' || transferGroupsLoading}
                        required
                    />

                    <div className="mb-3">
                        <label className="form-label d-flex align-items-center gap-2">
                            <i className="ti ti-cash" />
                            Paiements à emporter
                            <span className="text-muted fw-normal fs-12">par année et par inscription</span>
                        </label>
                        <TransferPaymentPicker
                            tree={transferPayments}
                            loading={transferPaymentsLoading}
                            selected={transferForm.data.encaissement_ids}
                            onChange={(ids) => transferForm.setData('encaissement_ids', ids)}
                            idPrefix="tr-pay"
                        />
                        {transferForm.errors.encaissement_ids && (
                            <div className="text-danger fs-12 mt-1">{transferForm.errors.encaissement_ids}</div>
                        )}
                    </div>

                    <TextareaField
                        id="tr-motif"
                        label="Motif du transfert"
                        rows={3}
                        value={transferForm.data.motif}
                        onChange={(event) => transferForm.setData('motif', event.target.value)}
                        error={transferForm.errors.motif}
                        maxLength={1000}
                        required
                    />
                    <div className="d-flex justify-content-end gap-2 mt-4">
                        <FormActions onCancel={closeTransfer} processing={transferForm.processing} submitLabel="Envoyer la demande" />
                    </div>
                </form>
            </Modal>

            <Modal show={archiveTarget !== null} title="Archiver l'étudiant" onClose={closeArchive} processing={archiveForm.processing}>
                <form onSubmit={submitArchive}>
                    <div className="alert alert-info fs-13 d-flex align-items-start" role="alert">
                        <i className="ti ti-archive me-2 mt-1" aria-hidden="true" />
                        <div>
                            <span className="fw-semibold">{enCapitales(archiveTarget?.nomComplet)}</span> interrompt ses cours et
                            reviendra plus tard. La fiche passe dans « Étudiants archivés » jusqu'à sa réactivation ; aucune
                            inscription ni aucun paiement n'est modifié.
                        </div>
                    </div>
                    <TextareaField
                        id="arch-motif"
                        label="Motif"
                        rows={3}
                        value={archiveForm.data.motif}
                        onChange={(event) => archiveForm.setData('motif', event.target.value)}
                        error={archiveForm.errors.motif}
                        maxLength={1000}
                        placeholder="ex : voyage, examens, raisons de santé…"
                        required
                    />
                    <DateField
                        id="arch-date-retour"
                        label="Date de retour prévue"
                        value={archiveForm.data.date_retour}
                        onChange={(event) => archiveForm.setData('date_retour', event.target.value)}
                        error={archiveForm.errors.date_retour}
                        required
                    />
                    {archiveFormError && <div className="alert alert-danger fs-13">{archiveFormError}</div>}
                    <div className="d-flex justify-content-end gap-2 mt-4">
                        <FormActions onCancel={closeArchive} processing={archiveForm.processing} submitLabel="Archiver" />
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={reactivateTarget !== null}
                title="Réactiver l'étudiant"
                recordLabel={enCapitales(reactivateTarget?.nomComplet)}
                message="L'étudiant est de retour ? Sa fiche redevient active et réapparaît dans la liste des étudiants."
                error={reactivateError}
                processing={reactivateProcessing}
                icon="ti-archive-off"
                variant="primary"
                confirmLabel="Oui, réactiver"
                processingLabel="Réactivation..."
                onConfirm={handleReactivate}
                onCancel={() => {
                    setReactivateTarget(null);
                    setReactivateError(undefined);
                }}
            >
                {reactivateTarget?.archiveMotif && (
                    <p className="fs-13 text-muted mb-0">
                        Archivé le {reactivateTarget.archiveLe ?? '-'} — retour prévu le {reactivateTarget.archiveDateRetour ?? '-'} :{' '}
                        {reactivateTarget.archiveMotif}
                    </p>
                )}
            </ConfirmDialog>

            <ConfirmDialog
                show={deleteTarget !== null}
                title="Supprimer l'étudiant"
                recordLabel={enCapitales(deleteTarget?.nomComplet)}
                message="Voulez-vous vraiment supprimer cet étudiant ?"
                error={deleteError}
                processing={deleteProcessing}
                confirmLabel={deleteBlockers?.forcable ? 'Oui, supprimer la fiche et ces présences' : undefined}
                onConfirm={() => handleDelete(deleteBlockers?.forcable === true)}
                onCancel={() => {
                    setDeleteTarget(null);
                    setDeleteError(undefined);
                    setDeleteBlockers(null);
                }}
            >
                {deleteBlockers?.forcable && (
                    /*
                     * ⚠ L'alerte NOMME ce qui va être détruit. Une
                     * suppression en aveugle n'est pas une décision : sans
                     * la date, le statut et le groupe, l'utilisateur ne peut
                     * pas juger si ces appels sont des scories ou la trace
                     * d'un vrai passage en classe.
                     */
                    <div className="alert alert-warning mb-0" role="alert">
                        <div className="d-flex align-items-start">
                            <i className="ti ti-alert-triangle me-2 mt-1 fs-18" aria-hidden="true" />
                            <div>
                                <p className="fw-semibold mb-1">
                                    Cette fiche porte {deleteBlockers.presences.length} ligne
                                    {deleteBlockers.presences.length > 1 ? 's' : ''} de présence, qui ser
                                    {deleteBlockers.presences.length > 1 ? 'ont' : 'a'} également supprimée
                                    {deleteBlockers.presences.length > 1 ? 's' : ''}.
                                </p>
                                <p className="mb-2 fs-13">
                                    Aucune inscription, aucun paiement : ces appels ne rattachent la fiche à
                                    rien. Cette suppression est définitive et sera journalisée.
                                </p>
                                <ul className="mb-0 ps-3 fs-13">
                                    {deleteBlockers.presences.map((p) => (
                                        <li key={p.id}>
                                            {p.date} - {p.statut} - {p.groupe}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    </div>
                )}
            </ConfirmDialog>
        </BackofficeLayout>
    );
}
