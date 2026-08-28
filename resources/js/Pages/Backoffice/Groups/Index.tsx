import { router, useForm, usePage } from '@inertiajs/react';
import { pageWindow } from '@/Components/Tables/Pagination';
import { useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import FilterTextInput from '@/Components/Tables/FilterTextInput';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionDivider, RowActionItem } from '@/Components/Tables/RowActions';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import DateField from '@/Components/Forms/DateField';
import FormField from '@/Components/Forms/FormField';
import SelectField from '@/Components/Forms/SelectField';
import FormActions from '@/Components/Forms/FormActions';
import StatusBadge from '@/Components/Details/StatusBadge';
import GroupPaymentMatrixTable from '@/Components/Groups/GroupPaymentMatrix';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { blockImplicitSubmit } from '@/Lib/forms';
import type {
    GroupFraisCatalogOption,
    GroupFraisLigne,
    GroupPaymentMatrix,
    GroupPaymentSort,
    GroupRow,
    GroupsPageProps,
    GroupStudentSegmentRow,
    SelectOption,
    SharedProps,
} from '@/Types';

type StatsSegment = 'total' | 'active' | 'annulee' | 'changement' | 'etudiants';

/** Rows per page of the "Frais du groupe" table — client-side only, no server round trip for this in-memory list. */
const GROUP_FRAIS_PER_PAGE = 5;

const STATS_SEGMENT_LABELS: Record<StatsSegment, string> = {
    total: 'Total inscriptions',
    active: 'Inscriptions actives',
    annulee: 'Inscriptions annulées',
    changement: 'Inscriptions en changement',
    etudiants: 'Étudiants',
};

const STATUT_TABS: Array<{ key: string; icon: string; label: string }> = [
    { key: 'En formation', icon: 'ti-school', label: 'En formation' },
    { key: 'En inscription', icon: 'ti-folder', label: 'En inscription' },
    { key: 'Fin de formation', icon: 'ti-history', label: 'Historique' },
];

type LifecycleAction = 'archive' | 'annuler' | 'reactiver' | 'activer' | 'retourner-en-inscription';

/** Per-action copy for the row-menu lifecycle ConfirmDialog — one place to keep title/message/icon/labels in sync per action, instead of parallel ternary chains that can drift apart. */
const LIFECYCLE_CONFIRM_COPY: Record<
    LifecycleAction,
    { title: string; message: string; icon: string; variant: 'danger' | 'primary'; confirmLabel: string; processingLabel: string }
> = {
    archive: {
        title: 'Terminer la formation',
        message: 'Marquer ce groupe comme terminé (Fin de formation) ? Cette action est irréversible.',
        icon: 'ti-archive',
        variant: 'danger',
        confirmLabel: 'Oui, terminer',
        processingLabel: 'Finalisation…',
    },
    annuler: {
        title: 'Annuler le groupe',
        message: 'Voulez-vous vraiment annuler ce groupe ?',
        icon: 'ti-x',
        variant: 'danger',
        confirmLabel: 'Oui, annuler',
        processingLabel: 'Annulation…',
    },
    reactiver: {
        title: 'Réactiver le groupe',
        message: 'Voulez-vous vraiment réactiver ce groupe (retour à En inscription) ?',
        icon: 'ti-refresh',
        variant: 'primary',
        confirmLabel: 'Oui, réactiver',
        processingLabel: 'Réactivation…',
    },
    activer: {
        title: 'Activer le groupe',
        message: 'Démarrer la formation pour ce groupe (passage à En formation) ?',
        icon: 'ti-player-play',
        variant: 'primary',
        confirmLabel: 'Oui, activer',
        processingLabel: 'Activation…',
    },
    'retourner-en-inscription': {
        title: 'Retourner en inscription',
        message: 'Ramener ce groupe à "En inscription" (arrête la formation en cours) ?',
        icon: 'ti-player-pause',
        variant: 'primary',
        confirmLabel: 'Oui, retourner',
        processingLabel: 'Retour en cours…',
    },
};

interface GroupFormState {
    nom: string;
    niveau: string;
    enseignant_id: number | '';
    /**
     * Changeover date + reason, sent ONLY when the edit modal actually swaps
     * the teacher of an existing group. They are the very fields the
     * dedicated « Changer d'enseignant » form on the group detail page asks
     * for, so a change made from here lands in "Historique des affectations"
     * with a real date and motif instead of a silent, undated one.
     */
    enseignant_date_debut: string;
    enseignant_motif: string;
    statut: string;
    date_debut_formation: string;
    date_fin_formation: string;
    fraisLignes: Record<number, GroupFraisLigne>;
}

/** Today as yyyy-mm-dd — the default changeover date, same as Groups/Show.tsx. */
function todayIso(): string {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

/**
 * The due date a monthly fee implies: the month from its own name
 * (`moisEcheance`, resolved server-side by FraisEcheanceResolver) on the
 * group's start day, in the current year — the client-side twin of that
 * resolver, kept in sync with it so the pre-filled value the user sees is
 * the same one the server would have derived from a blank field.
 * Returns '' when the fee names no month, leaving the date to be typed.
 */
function defaultEcheance(moisEcheance: number | null, dateDebutFormation: string): string {
    if (moisEcheance === null) {
        return '';
    }

    const annee = new Date().getFullYear();
    const jourDebut = dateDebutFormation !== '' ? Number(dateDebutFormation.slice(8, 10)) : 1;
    const jour = Number.isFinite(jourDebut) && jourDebut > 0 ? jourDebut : 1;
    // Clamp to the month's real length, same as the server: "31" in February
    // becomes the 28th/29th instead of spilling into March.
    const dernierJour = new Date(annee, moisEcheance, 0).getDate();
    const jourClamped = Math.min(jour, dernierJour);

    return `${annee}-${String(moisEcheance).padStart(2, '0')}-${String(jourClamped).padStart(2, '0')}`;
}

/**
 * Fee lines for a NEW group: every active catalog fee pre-filled with its
 * own catalog default amount and derived due date. These are defaults, not
 * locks — each row stays editable, and the edited value is what is saved.
 * Editing an existing group never runs through here; that form shows the
 * amounts the group was actually saved with (see openEdit).
 */
function defaultFraisLignes(fraisCatalog: GroupFraisCatalogOption[], dateDebutFormation: string): Record<number, GroupFraisLigne> {
    const lignes: Record<number, GroupFraisLigne> = {};
    fraisCatalog.forEach((f) => {
        lignes[f.id] = {
            montant: f.montantDefaut,
            date_echeance: defaultEcheance(f.moisEcheance, dateDebutFormation),
            classification: '',
        };
    });

    return lignes;
}

/** Blank lines, used only as the fallback base when loading an existing group. */
function emptyFraisLignes(fraisCatalog: GroupFraisCatalogOption[]): Record<number, GroupFraisLigne> {
    const lignes: Record<number, GroupFraisLigne> = {};
    fraisCatalog.forEach((f) => {
        lignes[f.id] = { montant: '0', date_echeance: '', classification: '' };
    });

    return lignes;
}

function emptyForm(fraisCatalog: GroupFraisCatalogOption[]): GroupFormState {
    return {
        nom: '',
        niveau: '',
        enseignant_id: '',
        enseignant_date_debut: todayIso(),
        enseignant_motif: '',
        statut: 'En inscription',
        date_debut_formation: '',
        date_fin_formation: '',
        fraisLignes: defaultFraisLignes(fraisCatalog, ''),
    };
}

/**
 * Replaces App\Livewire\Backoffice\Groups\GroupsIndex — same fields (Name,
 * Level, Teacher, Status, dates, fee lines), same status tabs, same
 * "every active catalog fee always assigned" behavior, now driven by real
 * HTTP requests (GroupController) instead of a Livewire component.
 * Deliberately does NOT include room/capacity/schedule fields — confirmed
 * absent from the current Livewire form (docs/phase-8-students-groups-
 * inventory.md).
 */
/**
 * Removes or restores one « Frais du groupe » line — a plain JSON request
 * rather than an Inertia visit.
 *
 * Both fire from inside the OPEN edit modal, which updates its own fee table
 * from local state afterwards. Routing them through `router.delete()`/
 * `router.post()` made Inertia re-run the index controller and rebuild the
 * whole page payload for every single click. Nothing behind the modal depends
 * on the result. Mirrors postFeeVisibility() in the Inscriptions page.
 *
 * Throws on a non-2xx response so the caller's `.finally()` still clears its
 * spinner and the optimistic UI update is skipped.
 */
async function postGroupFrais(
    url: string,
    method: 'DELETE' | 'POST',
): Promise<{ message?: string; ligne?: { montant: string; date_echeance: string; classification: string } }> {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const response = await fetch(url, {
        method,
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error('group fee visibility request failed');
    }

    return response.json();
}

export default function GroupsIndex({
    groups,
    statutCounts,
    filters,
    niveaux,
    enseignants,
    fraisCatalog,
}: GroupsPageProps) {
    const { auth, flash } = usePage<SharedProps>().props;
    // Same banner as Groups/Show.tsx: a teacher swap made from the edit modal
    // stops the emploi du temps, and the user has to rebuild it.
    const emploiDuTempsArrete = flash.emploiDuTempsArrete;
    const isLoading = useInertiaLoading();
    // UI convenience only — backoffice.groups.payment-matrix carries the real
    // permission:payments.view gate (CLAUDE.md §5).
    const canViewPayments = auth.isSuperAdmin || auth.permissions.includes('payments.view');
    const [showModal, setShowModal] = useState(false);
    const [editingGroup, setEditingGroup] = useState<GroupRow | null>(null);
    const [studentsModal, setStudentsModal] = useState<{ group: GroupRow; segment: StatsSegment } | null>(null);
    const [studentsRows, setStudentsRows] = useState<GroupStudentSegmentRow[]>([]);
    const [loadingStudents, setLoadingStudents] = useState(false);
    const [paymentsGroup, setPaymentsGroup] = useState<GroupRow | null>(null);
    const [paymentsMatrix, setPaymentsMatrix] = useState<GroupPaymentMatrix | null>(null);
    const [paymentsSort, setPaymentsSort] = useState<GroupPaymentSort>('date');
    const [loadingPayments, setLoadingPayments] = useState(false);
    const [fraisPage, setFraisPage] = useState(1);
    const [lifecycleTarget, setLifecycleTarget] = useState<{ group: GroupRow; action: LifecycleAction } | null>(null);
    const [lifecycleError, setLifecycleError] = useState<string | undefined>(undefined);
    const [lifecycleProcessing, setLifecycleProcessing] = useState(false);
    // « Frais du groupe » removal — the LIVE working copy of which fees the
    // group carries and which it has dropped, mirroring the Inscriptions edit
    // modal (removeEditingLine/restoreHiddenFee). They start from the server
    // row on openEdit() and are updated locally as soon as the JSON request
    // resolves, so the trash icon empties the row instantly instead of waiting
    // on a full Inertia page rebuild. `null` means "not editing".
    const [fraisActifs, setFraisActifs] = useState<number[] | null>(null);
    const [fraisRetiresIds, setFraisRetiresIds] = useState<number[]>([]);
    // Per-row spinner: the id of the fee whose remove/restore is in flight.
    const [fraisProcessingId, setFraisProcessingId] = useState<number | null>(null);
    // Edit mode: removing a fee cascades to every registration (hidden lines,
    // money released as avances) — confirmed first, then reported (F-002).
    const [fraisRemoveTarget, setFraisRemoveTarget] = useState<SelectOption | null>(null);
    const [fraisNotice, setFraisNotice] = useState<string | null>(null);
    const [fraisError, setFraisError] = useState<string | null>(null);

    const fraisCatalogOptions: SelectOption[] = fraisCatalog.map((f) => ({ value: f.id, label: f.nom }));
    /**
     * Fee rows the modal actually renders. Creating a group offers the WHOLE
     * active catalog (a new group starts with every fee assigned) minus the
     * rows the user trashed before saving — purely local state, posted to
     * store() as `fraisRetires`; editing one shows only the fees that group still carries,
     * so a fee removed through the trash icon disappears from the table
     * instead of silently reappearing on the next save.
     */
    const fraisRowOptions: SelectOption[] =
        editingGroup === null || fraisActifs === null
            ? fraisCatalogOptions.filter((f) => !fraisRetiresIds.includes(f.value as number))
            : fraisCatalogOptions.filter((f) => fraisActifs.includes(f.value as number));
    /** Removed fees, restorable from the « Frais retirés » list below the table. */
    const fraisRetires: SelectOption[] = fraisCatalogOptions.filter((f) =>
        fraisRetiresIds.includes(f.value as number),
    );
    const niveauOptions: SelectOption[] = niveaux.map((n) => ({ value: n, label: n }));
    const enseignantOptions: SelectOption[] = enseignants.map((e) => ({ value: e.id, label: e.nom }));
    // Create-mode status options omit "Fin de formation" — a group can only
    // reach it through the archive action (Group::archiverCommeTermine).
    const createStatutOptions: SelectOption[] = [
        { value: 'En inscription', label: 'En inscription' },
        { value: 'En formation', label: 'En formation' },
    ];
    const editStatutOptions: SelectOption[] = [
        { value: 'En inscription', label: 'En inscription' },
        { value: 'En formation', label: 'En formation' },
        { value: 'Fin de formation', label: 'Fin de formation' },
    ];

    const form = useForm<GroupFormState>(emptyForm(fraisCatalog));

    /**
     * Non-null while the edit modal holds a teacher DIFFERENT from the one the
     * group is saved with — the condition that turns a plain save into a real
     * changeover, and the trigger for the date + motif fields below. Creating a
     * group is never a changeover (it opens the first assignment period), so
     * this stays null there.
     */
    const enseignantChange =
        editingGroup !== null && form.data.enseignant_id !== (editingGroup.enseignantId ?? '')
            ? {
                  nouveau:
                      enseignants.find((e) => e.id === form.data.enseignant_id)?.nom ?? 'Aucun enseignant',
              }
            : null;

    function reload(nextFilters: Partial<typeof filters>) {
        router.get(
            '/backoffice/groups',
            { ...filters, ...nextFilters, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function setStatutTab(statut: string) {
        reload({ statutFilter: statut });
    }

    function openCreate() {
        setEditingGroup(null);
        setFraisActifs(null);
        setFraisRetiresIds([]);
        form.reset();
        form.clearErrors();
        form.setData(emptyForm(fraisCatalog));
        setFraisPage(1);
        setShowModal(true);
    }

    function openEdit(group: GroupRow) {
        setEditingGroup(group);
        form.clearErrors();

        // The list row already carries this group's own fee-line amounts
        // (GetGroupsList keys them by frais_id) — no second request needed.
        const lignes = emptyFraisLignes(fraisCatalog);
        Object.entries(group.fraisLignes).forEach(([fraisId, ligne]) => {
            lignes[Number(fraisId)] = {
                montant: ligne.montant,
                date_echeance: ligne.dateEcheance,
                classification: ligne.classification,
            };
        });

        form.setData({
            nom: group.nom,
            niveau: group.niveau,
            enseignant_id: group.enseignantId ?? '',
            enseignant_date_debut: todayIso(),
            enseignant_motif: '',
            statut: group.statut,
            date_debut_formation: group.dateDebutFormation ?? '',
            date_fin_formation: group.dateFinFormation ?? '',
            fraisLignes: lignes,
        });

        setFraisActifs(Object.keys(group.fraisLignes).map(Number));
        setFraisRetiresIds(group.fraisRetires.map((f) => f.id));
        setFraisPage(1);
        setShowModal(true);
    }

    function closeModal() {
        setShowModal(false);
        setEditingGroup(null);
        setFraisActifs(null);
        setFraisRetiresIds([]);
        form.reset();
        form.clearErrors();
        setFraisPage(1);
    }

    function confirmArchive(group: GroupRow) {
        setLifecycleTarget({ group, action: 'archive' });
        setLifecycleError(undefined);
    }

    function confirmAnnuler(group: GroupRow) {
        setLifecycleTarget({ group, action: 'annuler' });
        setLifecycleError(undefined);
    }

    function confirmReactiver(group: GroupRow) {
        setLifecycleTarget({ group, action: 'reactiver' });
        setLifecycleError(undefined);
    }

    function confirmActiver(group: GroupRow) {
        setLifecycleTarget({ group, action: 'activer' });
        setLifecycleError(undefined);
    }

    function confirmRetournerEnInscription(group: GroupRow) {
        setLifecycleTarget({ group, action: 'retourner-en-inscription' });
        setLifecycleError(undefined);
    }

    function handleLifecycleConfirm() {
        if (!lifecycleTarget) {
            return;
        }

        setLifecycleProcessing(true);
        router.post(
            `/backoffice/groups/${lifecycleTarget.group.id}/${lifecycleTarget.action}`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setLifecycleTarget(null);
                    setLifecycleError(undefined);
                },
                onError: (errors) => {
                    setLifecycleError(errors.statut ?? 'Action impossible.');
                },
                onFinish: () => setLifecycleProcessing(false),
            },
        );
    }

    async function openStudentsSegment(group: GroupRow, segment: StatsSegment) {
        setStudentsModal({ group, segment });
        setStudentsRows([]);
        setLoadingStudents(true);
        try {
            const response = await fetch(`/backoffice/groups/${group.id}/students-by-segment?segment=${segment}`);
            const data: { students: GroupStudentSegmentRow[] } = await response.json();
            setStudentsRows(data.students);
        } finally {
            setLoadingStudents(false);
        }
    }

    function closeStudentsModal() {
        setStudentsModal(null);
        setStudentsRows([]);
    }

    /**
     * "Détails paiement" — loads the group's students × frais matrix
     * (GetGroupPaymentMatrix). Re-fetched on every sort change rather than
     * re-ordered client-side, so the numbering (#1, #2…) and the column
     * totals always come from one authoritative computation.
     */
    async function loadPaymentMatrix(group: GroupRow, sort: GroupPaymentSort) {
        setLoadingPayments(true);
        try {
            const response = await fetch(`/backoffice/groups/${group.id}/payment-matrix?sort=${sort}`);
            const data: { matrix: GroupPaymentMatrix } = await response.json();
            setPaymentsMatrix(data.matrix);
        } finally {
            setLoadingPayments(false);
        }
    }

    function openPaymentsModal(group: GroupRow) {
        setPaymentsGroup(group);
        setPaymentsMatrix(null);
        // Date first, like the legacy « Statistique de groupe » screen.
        setPaymentsSort('date');
        void loadPaymentMatrix(group, 'date');
    }

    function changePaymentsSort(sort: GroupPaymentSort) {
        setPaymentsSort(sort);

        if (paymentsGroup) {
            void loadPaymentMatrix(paymentsGroup, sort);
        }
    }

    function closePaymentsModal() {
        setPaymentsGroup(null);
        setPaymentsMatrix(null);
    }

    /**
     * Re-derives the monthly due dates when the group's start date changes,
     * since the derived day comes from that date. Only rows still holding
     * the previously derived value are rewritten — once the user types a
     * date of their own it is never overwritten. Edit mode is left alone
     * entirely: those dates are the group's saved data, not defaults.
     */
    function setDateDebut(value: string) {
        if (editingGroup) {
            form.setData('date_debut_formation', value);

            return;
        }

        const previous = form.data.date_debut_formation;
        const lignes = { ...form.data.fraisLignes };

        fraisCatalog.forEach((f) => {
            const ligne = lignes[f.id];
            if (ligne === undefined || f.moisEcheance === null) {
                return;
            }

            if (ligne.date_echeance === '' || ligne.date_echeance === defaultEcheance(f.moisEcheance, previous)) {
                lignes[f.id] = { ...ligne, date_echeance: defaultEcheance(f.moisEcheance, value) };
            }
        });

        form.setData({ ...form.data, date_debut_formation: value, fraisLignes: lignes });
    }

    /**
     * Removes a fee from the group. This is NOT a form field — it fires
     * immediately against the server, because it cascades: every inscription
     * of the group has that fee line HIDDEN (never deleted), and the money
     * already collected on it is released back into re-applicable avances.
     *
     * fetch(), not router.delete(): an Inertia visit re-runs the index
     * controller and rebuilds the whole page payload (paginated list +
     * frais/enseignants catalogs) just to drop one row, which is what made the
     * trash icon feel slow. The endpoint returns plain JSON and the table is
     * updated from local state — same pattern as the Inscriptions edit modal
     * (removeEditingLine).
     */
    function removeFrais(frais: SelectOption) {
        const fraisId = frais.value as number;

        // Create mode: nothing exists on the server yet, so the removal is
        // local — the id is excluded from the table and posted as
        // `fraisRetires` on save.
        if (editingGroup === null) {
            setFraisRetiresIds((previous) => (previous.includes(fraisId) ? previous : [...previous, fraisId]));
            setFraisPage((page) => {
                const remaining = fraisRowOptions.length - 1;

                return Math.min(page, Math.max(1, Math.ceil(remaining / GROUP_FRAIS_PER_PAGE)));
            });

            return;
        }

        setFraisRemoveTarget(frais);
    }

    function executeRemoveFrais(frais: SelectOption) {
        const fraisId = frais.value as number;

        if (editingGroup === null) {
            return;
        }

        setFraisRemoveTarget(null);
        setFraisNotice(null);
        setFraisError(null);
        setFraisProcessingId(fraisId);
        postGroupFrais(`/backoffice/groups/${editingGroup.id}/frais/${fraisId}`, 'DELETE')
            .then((result) => {
                setFraisNotice(result.message ?? null);
                setFraisActifs((previous) => {
                    const next = (previous ?? []).filter((id) => id !== fraisId);

                    // Removing the last row of the current page would leave the
                    // client-side pager pointing past the end (a blank table).
                    setFraisPage((page) =>
                        Math.min(page, Math.max(1, Math.ceil(next.length / GROUP_FRAIS_PER_PAGE))),
                    );

                    return next;
                });
                setFraisRetiresIds((previous) =>
                    previous.includes(fraisId) ? previous : [...previous, fraisId],
                );
            })
            .catch(() => setFraisError('Le retrait du frais a échoué. Rechargez la page et réessayez.'))
            .finally(() => setFraisProcessingId(null));
    }

    /**
     * Reverse of removeFrais() — re-attaches the fee and un-hides its lines.
     * The response carries the restored row's shape, so it is spliced straight
     * back into the form state instead of re-fetching the page.
     */
    function restoreFrais(frais: SelectOption) {
        const fraisId = frais.value as number;

        if (editingGroup === null) {
            setFraisRetiresIds((previous) => previous.filter((id) => id !== fraisId));

            return;
        }

        setFraisProcessingId(fraisId);
        postGroupFrais(`/backoffice/groups/${editingGroup.id}/frais/${fraisId}/restore`, 'POST')
            .then((data) => {
                setFraisRetiresIds((previous) => previous.filter((id) => id !== fraisId));
                setFraisActifs((previous) =>
                    (previous ?? []).includes(fraisId) ? (previous ?? []) : [...(previous ?? []), fraisId],
                );

                if (data.ligne) {
                    form.setData('fraisLignes', {
                        ...form.data.fraisLignes,
                        [fraisId]: {
                            montant: data.ligne.montant,
                            date_echeance: data.ligne.date_echeance,
                            classification: data.ligne.classification,
                        },
                    });
                }
            })
            .finally(() => setFraisProcessingId(null));
    }

    function setLigne(fraisId: number, field: keyof GroupFraisLigne, value: string) {
        form.setData('fraisLignes', {
            ...form.data.fraisLignes,
            [fraisId]: { ...form.data.fraisLignes[fraisId], [field]: value },
        });
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => closeModal() };

        if (editingGroup) {
            form.transform((data) => data);
            form.put(`/backoffice/groups/${editingGroup.id}`, options);
        } else {
            form.transform((data) => ({ ...data, fraisRetires: fraisRetiresIds }));
            form.post('/backoffice/groups', options);
        }
    }

    return (
        <BackofficeLayout
            title="Groupes"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Groupes' }]}
            actions={
                <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                    <i className="ti ti-square-rounded-plus me-2" />
                    Ajouter un groupe
                </button>
            }
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

            <Card title="Groupes" bodyClassName="p-0 py-3">
                <ul className="nav nav-tabs nav-tabs-solid nav-tabs-rounded-fill mb-3 px-3" role="tablist">
                    {STATUT_TABS.map((tab) => (
                        <li className="me-2 mb-2" role="presentation" key={tab.key}>
                            <button
                                type="button"
                                className={`nav-link rounded${filters.statutFilter === tab.key ? ' active' : ''}`}
                                onClick={() => setStatutTab(tab.key)}
                            >
                                <i className={`ti ${tab.icon} me-1`} />
                                {tab.label}
                                <span className={`badge ${filters.statutFilter === tab.key ? 'bg-white text-dark' : 'badge-soft-secondary'} ms-1`}>
                                    {statutCounts[tab.key] ?? 0}
                                </span>
                            </button>
                        </li>
                    ))}
                </ul>

                {/* Filter row (reference CRM's Groupes filters, without a
                    Formation column) — Groupe/Enseignant/dates. */}
                <div className="px-3 pt-2">
                    <TableToolbar>
                        <div style={{ width: 220 }}>
                            <label className="form-label" htmlFor="grp-f-nom">
                                Groupe
                            </label>
                            <FilterTextInput
                                id="grp-f-nom"
                                value={filters.search}
                                onChange={(value) => reload({ search: value })}
                                placeholder="ex : Herr Driss 13h"
                            />
                        </div>
                        <div style={{ width: 220 }}>
                            <label className="form-label" htmlFor="grp-f-enseignant">
                                Enseignant
                            </label>
                            <SelectField
                                id="grp-f-enseignant"
                                options={enseignantOptions}
                                placeholder="Choisir un enseignant"
                                value={filters.enseignantFilter}
                                onChange={(event) => reload({ enseignantFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor="grp-f-du">
                                Date de début
                            </label>
                            <DateField
                                id="grp-f-du"
                                value={filters.dateFrom}
                                onChange={(event) => reload({ dateFrom: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor="grp-f-au">
                                Date de fin
                            </label>
                            <DateField
                                id="grp-f-au"
                                value={filters.dateTo}
                                onChange={(event) => reload({ dateTo: event.target.value })}
                            />
                        </div>
                    </TableToolbar>
                </div>


                {groups.data.length === 0 ? (
                    <EmptyState title="Aucun groupe avec ce statut" icon="ti ti-users-group" />
                ) : (
                    <>
                        <DataTable
                            loading={isLoading}
                            head={
                                <tr>
                                    <th>Nom</th>
                                    <th>Classification</th>
                                    <th>Enseignant</th>
                                    <th>Étudiants</th>
                                    <th>Statistique</th>
                                    <th>Statut</th>
                                    <th className="text-end">Action</th>
                                </tr>
                            }
                        >
                            {groups.data.map((group) => (
                                <tr key={group.id}>
                                    <td className="fw-medium">{group.nom}</td>
                                    <td>
                                        <span className="badge badge-soft-info">{group.niveau}</span>
                                    </td>
                                    <td>{group.enseignant ?? '—'}</td>
                                    <td>
                                        <button
                                            type="button"
                                            className="badge badge-soft-info border-0 d-inline-flex align-items-center gap-1"
                                            title={STATS_SEGMENT_LABELS.etudiants}
                                            onClick={() => openStudentsSegment(group, 'etudiants')}
                                        >
                                            {group.etudiantsDistinctsCount}
                                            <i className="ti ti-user" aria-hidden="true" />
                                        </button>
                                    </td>
                                    <td>
                                        <div className="d-flex flex-wrap gap-1">
                                            <button
                                                type="button"
                                                className="badge badge-soft-success border-0 d-inline-flex align-items-center gap-1"
                                                title={STATS_SEGMENT_LABELS.active}
                                                onClick={() => openStudentsSegment(group, 'active')}
                                            >
                                                {group.inscriptionsActivesCount}
                                                <i className="ti ti-user" aria-hidden="true" />
                                            </button>
                                            <button
                                                type="button"
                                                className="badge badge-soft-secondary border-0 d-inline-flex align-items-center gap-1"
                                                title={STATS_SEGMENT_LABELS.changement}
                                                onClick={() => openStudentsSegment(group, 'changement')}
                                            >
                                                {group.inscriptionsChangementCount}
                                                <i className="ti ti-user" aria-hidden="true" />
                                            </button>
                                            <button
                                                type="button"
                                                className="badge badge-soft-danger border-0 d-inline-flex align-items-center gap-1"
                                                title={STATS_SEGMENT_LABELS.annulee}
                                                onClick={() => openStudentsSegment(group, 'annulee')}
                                            >
                                                {group.inscriptionsAnnuleesCount}
                                                <i className="ti ti-user" aria-hidden="true" />
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <StatusBadge
                                            label={group.statut}
                                            variant={
                                                group.statut === 'En formation'
                                                    ? 'success'
                                                    : group.statut === 'Fin de formation'
                                                      ? 'secondary'
                                                      : group.statut === 'Annulée'
                                                        ? 'danger'
                                                        : 'warning'
                                            }
                                            dot
                                        />
                                    </td>
                                    <td className="text-end">
                                        <RowActions view={group.showUrl}>
                                            {group.statut !== 'Fin de formation' && group.statut !== 'Annulée' && (
                                                <RowActionItem icon="ti-edit" onClick={() => openEdit(group)}>
                                                    Modifier
                                                </RowActionItem>
                                            )}
                                            {canViewPayments && (
                                                <RowActionItem icon="ti-report-money" onClick={() => openPaymentsModal(group)}>
                                                    Détails paiement
                                                </RowActionItem>
                                            )}
                                            {group.statut === 'En formation' && (
                                                <>
                                                    <RowActionDivider />
                                                    <RowActionItem icon="ti-x" danger onClick={() => confirmAnnuler(group)}>
                                                        Annuler
                                                    </RowActionItem>
                                                    <RowActionItem
                                                        icon="ti-player-pause"
                                                        onClick={() => confirmRetournerEnInscription(group)}
                                                    >
                                                        En inscription
                                                    </RowActionItem>
                                                    <RowActionItem icon="ti-circle-check" onClick={() => confirmArchive(group)}>
                                                        Terminer
                                                    </RowActionItem>
                                                </>
                                            )}
                                            {group.statut === 'Annulée' && auth.isSuperAdmin && (
                                                <>
                                                    <RowActionDivider />
                                                    <RowActionItem icon="ti-refresh" onClick={() => confirmReactiver(group)}>
                                                        Réactiver
                                                    </RowActionItem>
                                                </>
                                            )}
                                            {group.statut === 'En inscription' && (
                                                <>
                                                    <RowActionDivider />
                                                    <RowActionItem icon="ti-circle-check" onClick={() => confirmActiver(group)}>
                                                        Activer
                                                    </RowActionItem>
                                                </>
                                            )}
                                        </RowActions>
                                    </td>
                                </tr>
                            ))}
                        </DataTable>
                        <Pagination paginator={groups} />
                    </>
                )}
            </Card>

            <Modal show={showModal} title={editingGroup ? 'Modifier le groupe' : 'Ajouter un groupe'} onClose={closeModal} processing={form.processing} size="xl">
                <form onSubmit={submit} onKeyDown={blockImplicitSubmit}>
                    <div className="row">
                        <div className="col-md-3">
                            <FormField
                                id="grp-nom"
                                label="Nom"
                                required
                                value={form.data.nom}
                                onChange={(event) => form.setData('nom', event.target.value)}
                                error={form.errors.nom}
                                placeholder="ex : Herr Driss 13h - Intensifs"
                            />
                        </div>
                        <div className="col-md-3">
                            <SelectField
                                id="grp-niveau"
                                label="Niveau"
                                required
                                options={niveauOptions}
                                placeholder="Choisir…"
                                value={form.data.niveau}
                                onChange={(event) => form.setData('niveau', event.target.value)}
                                error={form.errors.niveau}
                            />
                        </div>
                        <div className="col-md-3">
                            <SelectField
                                id="grp-ens"
                                label="Enseignant"
                                options={enseignantOptions}
                                placeholder={editingGroup ? 'Aucun enseignant' : 'Choisir…'}
                                value={form.data.enseignant_id}
                                onChange={(event) => form.setData('enseignant_id', event.target.value ? Number(event.target.value) : '')}
                                error={form.errors.enseignant_id}
                            />
                        </div>
                        <div className="col-md-3">
                            <SelectField
                                id="grp-statut"
                                label="Statut"
                                required
                                options={editingGroup ? editStatutOptions : createStatutOptions}
                                value={form.data.statut}
                                onChange={(event) => form.setData('statut', event.target.value)}
                                error={form.errors.statut}
                            />
                        </div>
                        <div className="col-md-3">
                            <DateField
                                id="grp-debut"
                                label="Date de début"
                                required
                                value={form.data.date_debut_formation}
                                onChange={(event) => setDateDebut(event.target.value)}
                                error={form.errors.date_debut_formation}
                            />
                        </div>
                        <div className="col-md-3">
                            <DateField
                                id="grp-fin"
                                label="Date de fin"
                                required
                                value={form.data.date_fin_formation}
                                onChange={(event) => form.setData('date_fin_formation', event.target.value)}
                                error={form.errors.date_fin_formation}
                            />
                        </div>
                    </div>

                    {enseignantChange && (
                        /* Same contract as « Changer d'enseignant » on the group detail
                           page: a teacher swap is a dated, motivated changeover that
                           archives the outgoing period and stops the emploi du temps —
                           never a silent overwrite of groups.enseignant_id. */
                        <div className="border-top pt-3 mb-3">
                            <div className="alert alert-info d-flex align-items-start gap-2" role="alert">
                                <i className="ti ti-info-circle fs-18 mt-1" aria-hidden="true" />
                                <span>
                                    <strong>{editingGroup?.enseignant ?? 'Aucun enseignant'}</strong> sera archivé à la
                                    date choisie et l'emploi du temps du groupe sera arrêté. Vous devrez créer un nouvel
                                    emploi du temps pour <strong>{enseignantChange.nouveau}</strong>. Les séances déjà
                                    effectuées ne sont pas modifiées.
                                </span>
                            </div>

                            <div className="row">
                                <div className="col-md-6">
                                    <DateField
                                        id="grp-ens-date"
                                        label="Date de prise en charge"
                                        required
                                        value={form.data.enseignant_date_debut}
                                        onChange={(event) => form.setData('enseignant_date_debut', event.target.value)}
                                        error={form.errors.enseignant_date_debut}
                                    />
                                </div>
                                <div className="col-md-6">
                                    <FormField
                                        id="grp-ens-motif"
                                        label="Motif"
                                        value={form.data.enseignant_motif}
                                        onChange={(event) => form.setData('enseignant_motif', event.target.value)}
                                        error={form.errors.enseignant_motif}
                                        placeholder="ex : indisponibilité de l'enseignant"
                                    />
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="border-top pt-3">
                        <h6 className="mb-1">Frais du groupe</h6>
                        <p className="text-muted fs-13 mb-3">
                            Les montants et les échéances sont pré-remplis depuis le catalogue des frais — modifiez-les si ce
                            groupe diffère du standard. Tous les frais sont reportés sur l'inscription lorsqu'un étudiant est
                            assigné à ce groupe.
                        </p>

                        {fraisRowOptions.length === 0 && fraisRetires.length === 0 ? (
                            <div className="alert alert-warning mb-0">
                                Aucun frais dans le catalogue. Ajoutez des frais dans Paramètres → Frais d'abord.
                            </div>
                        ) : (
                            <div className="table-responsive">
                                <table className="table table-bordered table-sm align-middle text-center mb-0">
                                    <thead className="table-light">
                                        <tr>
                                            <th className="text-center" style={{ width: '34%' }}>
                                                Frais
                                            </th>
                                            <th className="text-center" style={{ width: '18%' }}>
                                                Classification
                                            </th>
                                            <th className="text-center" style={{ width: '24%' }}>
                                                Échéance
                                            </th>
                                            <th className="text-center" style={{ width: editingGroup === null ? '24%' : '19%' }}>
                                                Montant
                                            </th>
                                            <th className="text-center" style={{ width: '5%' }}>
                                                &nbsp;
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {fraisRowOptions
                                            .slice((fraisPage - 1) * GROUP_FRAIS_PER_PAGE, fraisPage * GROUP_FRAIS_PER_PAGE)
                                            .map((fee) => {
                                            const ligne = form.data.fraisLignes[fee.value as number] ?? {
                                                montant: '0',
                                                date_echeance: '',
                                                classification: '',
                                            };
                                            const montantError = (form.errors as Record<string, string>)[`fraisLignes.${fee.value}.montant`];
                                            const classificationError = (form.errors as Record<string, string>)[
                                                `fraisLignes.${fee.value}.classification`
                                            ];

                                            return (
                                                <tr key={fee.value}>
                                                    <td>
                                                        <label className="form-label mb-0" htmlFor={`grp-fee-m-${fee.value}`}>
                                                            {fee.label}
                                                        </label>
                                                    </td>
                                                    <td>
                                                        <SelectField
                                                            id={`grp-fee-c-${fee.value}`}
                                                            options={niveauOptions}
                                                            placeholder="—"
                                                            value={ligne.classification}
                                                            onChange={(event) => setLigne(fee.value as number, 'classification', event.target.value)}
                                                            error={classificationError}
                                                        />
                                                    </td>
                                                    <td>
                                                        <DateField
                                                            id={`grp-fee-d-${fee.value}`}
                                                            value={ligne.date_echeance}
                                                            onChange={(event) => setLigne(fee.value as number, 'date_echeance', event.target.value)}
                                                            panelAlign="right"
                                                        />
                                                    </td>
                                                    <td>
                                                        <div className="input-group input-group-sm">
                                                            <input
                                                                id={`grp-fee-m-${fee.value}`}
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                className={`form-control text-center${montantError ? ' is-invalid' : ''}`}
                                                                placeholder="0"
                                                                value={ligne.montant}
                                                                onChange={(event) => setLigne(fee.value as number, 'montant', event.target.value)}
                                                            />
                                                            <span className="input-group-text">DH</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <button
                                                            type="button"
                                                            className="btn btn-sm btn-outline-danger border-0"
                                                            title={
                                                                editingGroup === null
                                                                    ? 'Retirer ce frais du groupe'
                                                                    : 'Retirer ce frais du groupe et de toutes ses inscriptions'
                                                            }
                                                            aria-label="Retirer ce frais"
                                                            disabled={fraisProcessingId === fee.value}
                                                            onClick={() => removeFrais(fee)}
                                                        >
                                                            <i
                                                                className={
                                                                    fraisProcessingId === fee.value
                                                                        ? 'ti ti-loader-2'
                                                                        : 'ti ti-trash'
                                                                }
                                                            />
                                                        </button>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                                {fraisNotice && <div className="alert alert-success py-2 small mb-2">{fraisNotice}</div>}
                                {fraisError && <div className="alert alert-danger py-2 small mb-2">{fraisError}</div>}
                                <GroupFraisPagination
                                    total={fraisRowOptions.length}
                                    perPage={GROUP_FRAIS_PER_PAGE}
                                    page={fraisPage}
                                    onPageChange={setFraisPage}
                                />
                            </div>
                        )}

                        {fraisRetires.length > 0 && (
                            <div className="mt-3">
                                <h6 className="fs-14 mb-1">Frais retirés</h6>
                                {editingGroup === null ? (
                                    <p className="text-muted fs-13 mb-2">
                                        Ces frais ne seront pas facturés par ce groupe. Restaurer un frais le remet dans le
                                        tableau ci-dessus avant l'enregistrement.
                                    </p>
                                ) : (
                                    <p className="text-muted fs-13 mb-2">
                                        Ces frais ne sont plus facturés par ce groupe et sont masqués sur ses inscriptions. Les
                                        montants déjà encaissés sur eux sont revenus en avance et peuvent être ré-appliqués à un
                                        autre frais. Restaurer un frais le réaffiche sur toutes les inscriptions du groupe — sans
                                        ré-appliquer les avances, ce qui reste une décision explicite.
                                    </p>
                                )}
                                <div className="d-flex flex-wrap gap-2">
                                    {fraisRetires.map((fee) => (
                                        <span key={fee.value} className="badge bg-light text-dark border d-inline-flex align-items-center gap-2 p-2">
                                            <span className="text-normal-case">{fee.label}</span>
                                            <button
                                                type="button"
                                                className="btn btn-sm btn-link p-0 text-primary"
                                                disabled={fraisProcessingId === fee.value}
                                                onClick={() => restoreFrais(fee)}
                                            >
                                                {fraisProcessingId === fee.value ? 'Restauration…' : 'Restaurer'}
                                            </button>
                                        </span>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="d-flex justify-content-end gap-2 mt-4">
                        <FormActions onCancel={closeModal} processing={form.processing} />
                    </div>
                </form>
            </Modal>

            <Modal
                show={studentsModal !== null}
                title="La liste d'étudiants"
                onClose={closeStudentsModal}
                size="xl"
            >
                {loadingStudents ? (
                    <p className="text-muted mb-0">Chargement…</p>
                ) : studentsRows.length === 0 ? (
                    <EmptyState title="Aucun étudiant" icon="ti ti-users-group" />
                ) : (
                    <div className="table-responsive">
                        <table className="table table-bordered align-middle mb-0">
                            <thead className="table-light">
                                <tr>
                                    <th>Référence</th>
                                    <th>Prénom</th>
                                    <th>Nom</th>
                                    <th>CIN</th>
                                    <th>Téléphone</th>
                                    <th>Date de naissance</th>
                                    <th>Niveau scolaire</th>
                                    <th>Date d'inscription</th>
                                </tr>
                            </thead>
                            <tbody>
                                {studentsRows.map((student) => (
                                    <tr key={student.reference}>
                                        <td>
                                            <code>{student.reference}</code>
                                        </td>
                                        <td>{student.prenom}</td>
                                        <td>{student.nom}</td>
                                        <td>{student.cin ?? '—'}</td>
                                        <td>
                                            {student.telephone ? (
                                                <a href={`tel:${student.telephone}`} className="d-inline-flex align-items-center">
                                                    <i className="ti ti-phone me-1" />
                                                    {student.telephone}
                                                </a>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td>{student.dateNaissance ?? '—'}</td>
                                        <td>{student.niveauScolaire ?? '—'}</td>
                                        <td>{student.dateInscription ?? '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Modal>

            <Modal
                show={paymentsGroup !== null}
                title={`Statistique de groupe — ${paymentsGroup?.nom ?? ''}`}
                onClose={closePaymentsModal}
                // A centred dialog, not modal-fullscreen: pinning the modal
                // edge-to-edge only removes the framing. `wide` keeps the
                // dialog while giving the grid ~80vw, so most of a year's fee
                // columns fit without scrolling sideways.
                size="wide"
            >
                <GroupPaymentMatrixTable
                    matrix={paymentsMatrix}
                    loading={loadingPayments}
                    sort={paymentsSort}
                    onSortChange={changePaymentsSort}
                />
            </Modal>

            <ConfirmDialog
                show={fraisRemoveTarget !== null}
                title="Retirer ce frais du groupe"
                recordLabel={fraisRemoveTarget?.label ?? ''}
                message="Le frais sera masqué sur toutes les inscriptions actives du groupe et l'argent déjà encaissé dessus sera converti en avances réutilisables. Continuer ?"
                processing={fraisProcessingId !== null}
                onConfirm={() => fraisRemoveTarget && executeRemoveFrais(fraisRemoveTarget)}
                onCancel={() => setFraisRemoveTarget(null)}
                confirmLabel="Retirer"
            />
            <ConfirmDialog
                show={lifecycleTarget !== null}
                title={LIFECYCLE_CONFIRM_COPY[lifecycleTarget?.action ?? 'archive'].title}
                recordLabel={lifecycleTarget?.group.nom ?? ''}
                message={LIFECYCLE_CONFIRM_COPY[lifecycleTarget?.action ?? 'archive'].message}
                icon={LIFECYCLE_CONFIRM_COPY[lifecycleTarget?.action ?? 'archive'].icon}
                variant={LIFECYCLE_CONFIRM_COPY[lifecycleTarget?.action ?? 'archive'].variant}
                confirmLabel={LIFECYCLE_CONFIRM_COPY[lifecycleTarget?.action ?? 'archive'].confirmLabel}
                processingLabel={LIFECYCLE_CONFIRM_COPY[lifecycleTarget?.action ?? 'archive'].processingLabel}
                error={lifecycleError}
                processing={lifecycleProcessing}
                onConfirm={handleLifecycleConfirm}
                onCancel={() => {
                    setLifecycleTarget(null);
                    setLifecycleError(undefined);
                }}
            />
        </BackofficeLayout>
    );
}

interface GroupFraisPaginationProps {
    total: number;
    perPage: number;
    page: number;
    onPageChange: (page: number) => void;
}

/**
 * Lightweight client-side pager for the "Frais du groupe" table — the fee
 * lines live entirely in form's in-memory state (no server round trip until
 * submit), so this can't reuse the server-driven <Pagination> component
 * (which navigates via router.get against a Laravel paginator). Same
 * Bootstrap `.pagination` markup/prev-next-jump styling.
 */
function GroupFraisPagination({ total, perPage, page, onPageChange }: GroupFraisPaginationProps) {
    const lastPage = Math.max(1, Math.ceil(total / perPage));

    if (lastPage <= 1) {
        return null;
    }

    const from = (page - 1) * perPage + 1;
    const to = Math.min(total, page * perPage);

    return (
        <div className="gls-pagination-footer d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2">
            <p className="text-muted mb-0">{total} total</p>
            <nav aria-label="Pagination des frais">
                <ul className="pagination pagination-sm mb-0 flex-nowrap">
                    <li className={`page-item${page === 1 ? ' disabled' : ''}`} aria-disabled={page === 1 ? true : undefined}>
                        <button type="button" className="page-link border-0" aria-label="Première page" onClick={() => onPageChange(1)}>
                            «
                        </button>
                    </li>
                    <li className={`page-item${page === 1 ? ' disabled' : ''}`} aria-disabled={page === 1 ? true : undefined}>
                        <button type="button" className="page-link border-0" aria-label="Page précédente" onClick={() => onPageChange(page - 1)}>
                            ‹
                        </button>
                    </li>
                    {pageWindow(page, lastPage, 1).map((n, i) =>
                        n === null ? (
                            <li className="page-item disabled" aria-disabled="true" key={`gap-${i}`}>
                                <span className="page-link">…</span>
                            </li>
                        ) : (
                            <li className={`page-item${n === page ? ' active' : ''}`} aria-current={n === page ? 'page' : undefined} key={n}>
                                <button type="button" className="page-link border-0" onClick={() => onPageChange(n)}>
                                    {n}
                                </button>
                            </li>
                        ),
                    )}
                    <li className={`page-item${page === lastPage ? ' disabled' : ''}`} aria-disabled={page === lastPage ? true : undefined}>
                        <button type="button" className="page-link border-0" aria-label="Page suivante" onClick={() => onPageChange(page + 1)}>
                            ›
                        </button>
                    </li>
                    <li className={`page-item${page === lastPage ? ' disabled' : ''}`} aria-disabled={page === lastPage ? true : undefined}>
                        <button type="button" className="page-link border-0" aria-label="Dernière page" onClick={() => onPageChange(lastPage)}>
                            »
                        </button>
                    </li>
                </ul>
            </nav>
            <p className="text-muted mb-0">
                {from}–{to} sur {total}
            </p>
        </div>
    );
}
