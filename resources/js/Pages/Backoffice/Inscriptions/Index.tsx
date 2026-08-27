import { router, useForm, usePage } from '@inertiajs/react';
import { pageWindow } from '@/Components/Tables/Pagination';
import { Fragment, useEffect, useRef, useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { blockImplicitSubmit } from '@/Lib/forms';
import TableToolbar from '@/Components/Tables/TableToolbar';
import FilterTextInput from '@/Components/Tables/FilterTextInput';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
import Pagination from '@/Components/Tables/Pagination';
import LocalPagination from '@/Components/Tables/LocalPagination';
import RowActions, { RowActionDivider, RowActionItem } from '@/Components/Tables/RowActions';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import DateField from '@/Components/Forms/DateField';
import FormField from '@/Components/Forms/FormField';
import SelectField from '@/Components/Forms/SelectField';
import MultiSelectField from '@/Components/Forms/MultiSelectField';
import TextareaField from '@/Components/Forms/TextareaField';
import PhoneField from '@/Components/Forms/PhoneField';
import FormActions from '@/Components/Forms/FormActions';
import StatusBadge from '@/Components/Details/StatusBadge';
import type {
    HiddenInscriptionFee,
    InscriptionFeeLine,
    InscriptionGroupFee,
    InscriptionGroupFeesResponse,
    InscriptionRow,
    InscriptionsPageProps,
    NouvelleInscription,
    PaymentLine,
    SelectOption,
    SharedProps,
    StudentChequeOption,
    UnpaidFee,
} from '@/Types';

/** How many fee rows the payment modal shows at once (mirrors the Encaissements create modal). */
const PAIEMENT_LINES_PER_PAGE = 4;
/** Encaissement::METHODE_ESPECES / METHODE_CHEQUE — the two the payment modal branches on. */
const METHODE_ESPECES = 'Espèces';
const METHODE_CHEQUE = 'Chèque';

/** The « Ajouter un paiement » modal's form — identical shape to the Encaissements create form. */
interface PaiementFormState {
    student_id: number | '';
    inscription_id: number | '';
    date_paiement: string;
    note: string;
    payment_lines: PaymentLine[];
}

/**
 * The just-created registration, shaped as an InscriptionRow so the payment
 * prompt and the payment modal can be driven by the same target state as a
 * row action. Only the fields those two read are meaningful — the rest carry
 * the empty values the list would show before its own reload.
 */
function rowFromNouvelleInscription(nouvelle: NouvelleInscription): InscriptionRow {
    return {
        id: nouvelle.id,
        reference: nouvelle.reference,
        studentId: nouvelle.studentId,
        groupId: null,
        student: nouvelle.studentLabel,
        studentShowUrl: null,
        groupe: null,
        date: null,
        dateDebut: null,
        dateFin: null,
        montantTotal: null,
        feesCount: 0,
        // A registration always starts Active (forced server-side in
        // InscriptionController@store), which is also what gates payment.
        statut: 'Active',
        showUrl: `/backoffice/inscriptions/${nouvelle.id}`,
    };
}

/** Frais whose échéance falls inside the current calendar month — the « Paiements en cours » filter. */
function echeanceCeMois(dateEcheance: string | null): boolean {
    if (!dateEcheance) {
        return false;
    }

    const now = new Date();
    const [annee, mois] = dateEcheance.split('-');

    return Number(annee) === now.getFullYear() && Number(mois) === now.getMonth() + 1;
}

interface InscriptionFormState {
    inscription_mode: 'new' | 'existing';
    student_id: number | '';
    group_id: number | '';
    statut: string;
    date_inscription: string;
    date_debut: string;
    date_fin: string;
    note: string;
    fee_lines: InscriptionFeeLine[];
    livre_ids: number[];
    // New-student inline fields.
    new_nom: string;
    new_prenom: string;
    new_sexe: string;
    new_date_naissance: string;
    new_cin: string;
    new_niveau: string;
    new_domaine: string;
    new_examen_type: string;
    new_email: string;
    new_telephone: string;
    new_whatsapp: string;
    new_adresse: string;
    new_parent_relation: string;
    new_parent_nom: string;
    new_parent_sexe: string;
    new_parent_cin: string;
    new_parent_telephone: string;
    new_parent_whatsapp: string;
    phone_pays: string;
}

function emptyForm(defaultCountry: string): InscriptionFormState {
    return {
        inscription_mode: 'new',
        student_id: '',
        group_id: '',
        statut: 'Active',
        date_inscription: new Date().toISOString().slice(0, 10),
        date_debut: '',
        date_fin: '',
        note: '',
        fee_lines: [],
        livre_ids: [],
        new_nom: '',
        new_prenom: '',
        new_sexe: '',
        new_date_naissance: '',
        new_cin: '',
        new_niveau: '',
        new_domaine: '',
        new_examen_type: '',
        new_email: '',
        new_telephone: '',
        new_whatsapp: '',
        new_adresse: '',
        new_parent_relation: '',
        new_parent_nom: '',
        new_parent_sexe: '',
        new_parent_cin: '',
        new_parent_telephone: '',
        new_parent_whatsapp: '',
        phone_pays: defaultCountry,
    };
}

/** Statut → badge color (Inscription::STATUTS: Active/Annulée/Changement/Expirée/Archivée). */
function statutVariant(statut: string): 'success' | 'danger' | 'warning' | 'secondary' {
    if (statut === 'Active') return 'success';
    if (statut === 'Annulée') return 'danger';
    if (statut === 'Changement') return 'warning';
    return 'secondary';
}

/**
 * Mirrors InscriptionFee::computeMontant() exactly — display-only preview;
 * the server independently recomputes this from the same inputs and that
 * server value is what actually persists (docs/phase-9-inscriptions-
 * mapping.md's Money handling section).
 */
function computeLineMontant(initial: number, remisePct: number | null, remiseMontant: number | null): number {
    if (remisePct !== null && remisePct > 0) {
        return Math.round(initial * (1 - Math.min(remisePct, 100) / 100) * 100) / 100;
    }

    return Math.round(Math.max(0, initial - (remiseMontant ?? 0)) * 100) / 100;
}


/** Rows per page of the "Frais disponibles" table — client-side only, no server round trip for this in-memory list. */
const AVAILABLE_FEES_PER_PAGE = 5;

/**
 * Replaces App\Livewire\Backoffice\Inscriptions\InscriptionsIndex — the most
 * business-critical module migrated so far. Preserves every calculation and
 * asymmetry exactly: fee lines only apply on create (never on edit); a new
 * registration always starts "Active" and its center/year/dates are always
 * derived from the selected group server-side; the discount two-way sync
 * and final-amount preview are display-only, never trusted as the source
 * of truth (the server recomputes independently on save).
 */
/**
 * Hides or restores one inscription fee line — a plain JSON POST rather than
 * an Inertia visit.
 *
 * These two actions fire from inside the OPEN edit modal, and the modal
 * updates its own table from local state afterwards. Routing them through
 * `router.post()` made Inertia re-run the index controller and rebuild the
 * whole page payload (the paginated list plus the students / groups / frais
 * option catalogs) for every single click, which is what made removing a fee
 * feel slow. Nothing behind the modal depends on the result.
 *
 * Throws on a non-2xx response so the caller's `.finally()` still clears its
 * spinner and the optimistic UI update is skipped.
 */
async function postFeeVisibility(url: string): Promise<{ fee?: InscriptionFeeLine }> {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const response = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
    });

    if (!response.ok) {
        throw new Error('fee visibility request failed');
    }

    return response.json();
}

export default function InscriptionsIndex({
    inscriptions,
    filters,
    perPageOptions,
    statuts,
    niveauxInteret,
    domaines,
    examenTypes,
    sexes,
    parentRelations,
    niveauxAvecDomaine,
    niveauStudium,
    defaultCountry,
    students,
    groups,
    frais,
    canManageFees,
    canChangeGroup,
    motifsAnnulation,
    canCreatePayment,
    methodesPaiement,
}: InscriptionsPageProps) {
    const isLoading = useInertiaLoading();
    const [showModal, setShowModal] = useState(false);
    const [editingInscription, setEditingInscription] = useState<InscriptionRow | null>(null);
    const [activeTab, setActiveTab] = useState<'affectation' | 'contact' | 'parent' | 'autre'>('affectation');
    const [loadingGroupFees, setLoadingGroupFees] = useState(false);
    const [loadingEditingFees, setLoadingEditingFees] = useState(false);
    // The inscription's group fee assignments (per-group montant + échéance),
    // so a fee re-added in the edit modal starts from the same amount the
    // create form would have proposed — never from an empty 0 DH line.
    const [editingGroupFees, setEditingGroupFees] = useState<InscriptionGroupFee[]>([]);
    // Books ("Livre" stock) available at the selected group's center — create form.
    const [availableLivres, setAvailableLivres] = useState<Array<{ id: number; nom: string; quantite: number }>>([]);
    // Edit modal's own book state: what's already assigned + what's pickable at this inscription's center.
    const [editingLivreIds, setEditingLivreIds] = useState<number[]>([]);
    const [editingAvailableLivres, setEditingAvailableLivres] = useState<Array<{ id: number; nom: string; quantite: number }>>([]);
    const [loadingEditingLivres, setLoadingEditingLivres] = useState(false);
    const [livresError, setLivresError] = useState<string | undefined>(undefined);
    const [livresProcessing, setLivresProcessing] = useState(false);
    const [hiddenFees, setHiddenFees] = useState<HiddenInscriptionFee[]>([]);
    // Hidden fees are the exception, not the norm — collapsed by default so
    // they stop pushing the "Livres" picker below the fold on inscriptions
    // that carry several of them.
    const [showHiddenFees, setShowHiddenFees] = useState(false);
    const [hideProcessingId, setHideProcessingId] = useState<number | null>(null);
    const [restoreProcessingId, setRestoreProcessingId] = useState<number | null>(null);
    const [availableFeesPage, setAvailableFeesPage] = useState(1);
    const [newFeeToAdd, setNewFeeToAdd] = useState<number | ''>('');
    const [deleteTarget, setDeleteTarget] = useState<InscriptionRow | null>(null);
    const [deleteError, setDeleteError] = useState<string | undefined>(undefined);
    const [deleteProcessing, setDeleteProcessing] = useState(false);
    const [changeGroupTarget, setChangeGroupTarget] = useState<InscriptionRow | null>(null);
    // « Suivi des paiements » (row menu): read-only view of the inscription's
    // fee lines with paid/remaining per line. Loads from the same
    // inscriptions.fees JSON endpoint the edit modal uses.
    const [suiviTarget, setSuiviTarget] = useState<InscriptionRow | null>(null);
    const [suiviFees, setSuiviFees] = useState<InscriptionFeeLine[] | null>(null);
    const [suiviEnCoursOnly, setSuiviEnCoursOnly] = useState(false);

    // ── « Ajouter un paiement » ──────────────────────────────────
    // A cashier who has just enrolled someone (or who is looking at the row)
    // can settle frais without leaving for Encaissements and re-searching the
    // student there. Same server contract as the Encaissements create modal —
    // it posts to encaissements.store with the same payment_lines shape, so
    // every money invariant (per-fee max:reste re-checked under lock, the
    // fee->inscription ownership guard, the CaisseResolver routing, the
    // active-context assertion) is enforced by exactly the same code path.
    const [paiementTarget, setPaiementTarget] = useState<InscriptionRow | null>(null);
    const [paiementFees, setPaiementFees] = useState<UnpaidFee[] | null>(null);
    // Default ON, matching the old CRM: only the frais whose échéance falls in
    // the current month are proposed, since that is what a walk-in payment
    // almost always settles. Unticking it widens the table to every unpaid
    // frais of the inscription (the endpoint never returns fully-paid ones).
    const [paiementEnCoursOnly, setPaiementEnCoursOnly] = useState(true);
    const [paiementCheques, setPaiementCheques] = useState<StudentChequeOption[]>([]);
    const [paiementLinesPage, setPaiementLinesPage] = useState(1);
    // The post-creation « Voulez-vous ajouter un paiement ? » step, driven off
    // the one-time `nouvelleInscription` flash rather than local form state:
    // the create modal submits an Inertia visit, so the page remounts with
    // fresh props before any local "just saved" flag could survive.
    const { flash } = usePage<SharedProps>().props;
    const [paiementPrompt, setPaiementPrompt] = useState<InscriptionRow | null>(null);

    const paiementForm = useForm<PaiementFormState>({
        student_id: '',
        inscription_id: '',
        date_paiement: new Date().toISOString().slice(0, 10),
        note: '',
        payment_lines: [],
    });

    const methodePaiementOptions: SelectOption[] = (methodesPaiement ?? []).map((m) => ({ value: m, label: m }));

    function openPaiement(inscription: InscriptionRow): void {
        setPaiementPrompt(null);
        setPaiementTarget(inscription);
        setPaiementFees(null);
        setPaiementCheques([]);
        setPaiementEnCoursOnly(true);
        setPaiementLinesPage(1);
        paiementForm.clearErrors();
        paiementForm.setData({
            student_id: inscription.studentId ?? '',
            inscription_id: inscription.id,
            date_paiement: new Date().toISOString().slice(0, 10),
            note: '',
            payment_lines: [],
        });

        fetch(`/backoffice/inscriptions/${inscription.id}/unpaid-fees`, { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((data: { fees: UnpaidFee[] }) => {
                setPaiementFees(data.fees);
                const today = new Date().toISOString().slice(0, 10);
                paiementForm.setData(
                    'payment_lines',
                    data.fees.map((fee) => ({
                        feeId: fee.id,
                        nom: fee.nom,
                        montantInitial: fee.montantInitial,
                        reste: fee.reste,
                        dateEcheance: fee.dateEcheance,
                        montant: '',
                        methode: METHODE_ESPECES,
                        datePaiement: today,
                        chequeId: '',
                    })),
                );
            })
            .catch(() => setPaiementFees([]));

        // Tracked chèques of the student — the only source a Chèque-method row
        // may draw on (no manual numéro/banque entry anywhere in the app).
        if (inscription.studentId) {
            fetch(`/backoffice/students/${inscription.studentId}/cheques`, { headers: { Accept: 'application/json' } })
                .then((response) => (response.ok ? response.json() : { cheques: [] }))
                .then((data: { cheques: StudentChequeOption[] }) => setPaiementCheques(data.cheques))
                .catch(() => setPaiementCheques([]));
        }
    }

    function closePaiement(): void {
        setPaiementTarget(null);
        setPaiementFees(null);
        setPaiementCheques([]);
        setPaiementLinesPage(1);
        paiementForm.clearErrors();
        paiementForm.reset();
    }

    function setPaiementLine(index: number, patch: Partial<PaymentLine>): void {
        paiementForm.setData((previous) => ({
            ...previous,
            payment_lines: previous.payment_lines.map((line, i) => (i === index ? { ...line, ...patch } : line)),
        }));
    }

    function submitPaiement(event: FormEvent): void {
        event.preventDefault();

        // camelCase client state -> the snake_case shape
        // StoreEncaissementRequest validates. Rows left at montant '' are
        // ignored server-side, so the whole table is submitted at once.
        paiementForm.transform((data) => ({
            ...data,
            payment_lines: data.payment_lines.map((line) => ({
                fee_id: line.feeId,
                montant: line.montant,
                methode: line.methode,
                date_paiement: line.datePaiement,
                cheque_id: line.methode === METHODE_CHEQUE ? line.chequeId : null,
            })),
        }));

        paiementForm.post('/backoffice/encaissements', {
            preserveScroll: true,
            onSuccess: () => closePaiement(),
            // A refused row can be paginated out of view — jump to the page
            // holding the first payment_lines.<i>.* error so the save never
            // fails silently off-screen.
            onError: (errors) => {
                const firstErrorKey = Object.keys(errors as Record<string, string>).find((key) =>
                    key.startsWith('payment_lines.'),
                );
                if (!firstErrorKey) {
                    return;
                }
                const lineIndex = Number(firstErrorKey.split('.')[1]);
                if (Number.isNaN(lineIndex)) {
                    return;
                }
                setPaiementLinesPage(Math.floor(lineIndex / PAIEMENT_LINES_PER_PAGE) + 1);
            },
            // Reset the transform so a later submit on this form instance does
            // not carry the payment_lines mapping over.
            onFinish: () => paiementForm.transform((data) => data),
        });
    }

    // The create modal's Inertia visit lands back here carrying the one-time
    // `nouvelleInscription` flash: ask whether to record a payment right away.
    // Keyed on the id so the prompt opens once per creation and never again on
    // a later filter/pagination reload (the flash is pulled server-side).
    const nouvelleInscription = flash.nouvelleInscription ?? null;
    const nouvelleInscriptionId = nouvelleInscription?.id ?? null;

    useEffect(() => {
        if (nouvelleInscription === null || !canCreatePayment) {
            return;
        }

        setPaiementPrompt(rowFromNouvelleInscription(nouvelleInscription));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [nouvelleInscriptionId]);

    function openSuivi(inscription: InscriptionRow): void {
        setSuiviTarget(inscription);
        setSuiviFees(null);
        setSuiviEnCoursOnly(false);
        fetch(`/backoffice/inscriptions/${inscription.id}/fees`, { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((data: { fees: InscriptionFeeLine[] }) => setSuiviFees(data.fees))
            .catch(() => setSuiviFees([]));
    }

    function lineFinal(line: InscriptionFeeLine): number {
        const initial = parseFloat(line.montantInitial || '0');
        const pct = line.remisePct !== '' ? parseFloat(line.remisePct) : null;
        const dh = line.remiseMontant !== '' ? parseFloat(line.remiseMontant) : null;

        return computeLineMontant(initial, pct, dh);
    }

    function formatFr(date: string): string {
        const [y, m, d] = date.split('-');

        return y && m && d ? `${d}/${m}/${y}` : date;
    }

    function fmtDh(value: number): string {
        return Number.isInteger(value) ? String(value) : value.toFixed(2);
    }
    const [showFeesDetails, setShowFeesDetails] = useState(false);
    // Reactivation only (Annulée/Changement -> Active). Cancelling has its own
    // form modal below — it needs a reason, an end date and a fee decision.
    const [statutTarget, setStatutTarget] = useState<{ inscription: InscriptionRow; statut: 'Active' } | null>(null);
    const [cancelTarget, setCancelTarget] = useState<InscriptionRow | null>(null);
    const [statutError, setStatutError] = useState<string | undefined>(undefined);
    const [statutProcessing, setStatutProcessing] = useState(false);

    const form = useForm<InscriptionFormState>(emptyForm(defaultCountry));
    // "Annuler l'inscription". The fee scopes are the same two the group-change
    // form offers and mean the same thing server-side (AnnulerInscription
    // reuses ChangerGroupeInscription::SCOPES).
    const cancelForm = useForm<{
        motif_annulation: string;
        date_fin: string;
        unpaid_fees_scope: '' | 'overdue_only' | 'all';
        note: string;
    }>({
        motif_annulation: '',
        date_fin: new Date().toISOString().slice(0, 10),
        unpaid_fees_scope: '',
        note: '',
    });
    const changeGroupForm = useForm<{
        new_group_id: number | '';
        date_fin: string;
        date_debut: string;
        unpaid_fees_scope: '' | 'overdue_only' | 'all';
        note: string;
        transfer_fee_ids: number[];
    }>({
        new_group_id: '',
        date_fin: new Date().toISOString().slice(0, 10),
        date_debut: new Date().toISOString().slice(0, 10),
        unpaid_fees_scope: '',
        note: '',
        transfer_fee_ids: [],
    });
    // "Transférer des frais" — the current inscription's paid / partially
    // paid lines the user may carry over (with their payments) to the new
    // inscription instead of leaving them in the archived one.
    const [showTransferFees, setShowTransferFees] = useState(false);
    const [transferableFees, setTransferableFees] = useState<InscriptionFeeLine[]>([]);
    const [loadingTransferableFees, setLoadingTransferableFees] = useState(false);
    // Separate form for the edit modal's fee table — its own endpoint
    // (PUT .../fees), independent processing/errors from the base-fields form.
    const feesForm = useForm<{ fee_lines: InscriptionFeeLine[] }>({ fee_lines: [] });
    const feesSaveTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    const studentOptions: SelectOption[] = students.map((s) => ({ value: s.id, label: s.label }));
    const groupOptions: SelectOption[] = groups.map((g) => ({ value: g.id, label: g.label }));
    // Catalog fees already fully "Payé" on this inscription are excluded —
    // nothing to gain from re-adding a settled fee; unpaid/partial/not-yet-
    // added ones (incl. re-adding the same name as a second charge) stay
    // selectable.
    const paidFeeNames = new Set(
        feesForm.data.fee_lines.filter((line) => line.statut === 'Payé').map((line) => line.nom),
    );
    const fraisOptions: SelectOption[] = frais
        .filter((f) => !paidFeeNames.has(f.label))
        .map((f) => ({ value: f.id, label: f.label }));
    const niveauOptions: SelectOption[] = niveauxInteret.map((n) => ({ value: n, label: n }));
    const domaineOptions: SelectOption[] = domaines.map((d) => ({ value: d, label: d }));
    const examenOptions: SelectOption[] = examenTypes.map((e) => ({ value: e, label: e }));
    const parentRelationOptions: SelectOption[] = parentRelations.map((r) => ({ value: r, label: r }));
    const statutOptions: SelectOption[] = statuts.map((s) => ({ value: s, label: s }));
    const statutFilterOptions: SelectOption[] = statuts.map((s) => ({ value: s, label: s }));

    const showsDomaine = niveauxAvecDomaine.includes(form.data.new_niveau);
    const showsExamen = form.data.new_niveau === niveauStudium;

    function reload(nextFilters: Partial<typeof filters>) {
        router.get(
            '/backoffice/inscriptions',
            { ...filters, ...nextFilters, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function openCreate() {
        setEditingInscription(null);
        setActiveTab('affectation');
        form.reset();
        form.clearErrors();
        form.setData(emptyForm(defaultCountry));
        setAvailableFeesPage(1);
        setShowModal(true);
    }

    function openEdit(inscription: InscriptionRow) {
        // The base-fields form only ever changes 6 columns, matching the
        // Livewire form's own `@unless ($editingId)` scoping. Fee lines are
        // loaded/saved separately below (feesForm, its own PUT endpoint).
        // The row exposes the raw student/group ids so the selects
        // preselect by id (label-prefix matching used to pick the wrong
        // record when two students shared a name — Phase 12 fix).
        setEditingInscription(inscription);
        setActiveTab('affectation');
        form.clearErrors();
        form.setData({
            ...emptyForm(defaultCountry),
            inscription_mode: 'existing',
            student_id: inscription.studentId ?? '',
            group_id: inscription.groupId ?? '',
            statut: inscription.statut,
        });
        setShowModal(true);

        // Fetched separately since InscriptionRow (the list payload) carries
        // only feesCount, not the fee lines themselves.
        feesForm.setData('fee_lines', []);
        feesForm.clearErrors();
        setHiddenFees([]);
        setShowHiddenFees(false);
        setNewFeeToAdd('');
        setLoadingEditingFees(true);
        fetch(`/backoffice/inscriptions/${inscription.id}/fees`, { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((data: { fees: InscriptionFeeLine[]; hiddenFees: HiddenInscriptionFee[] }) => {
                feesForm.setData('fee_lines', data.fees);
                setHiddenFees(data.hiddenFees);
            })
            .finally(() => setLoadingEditingFees(false));

        setEditingGroupFees([]);
        if (inscription.groupId) {
            fetch(`/backoffice/groups/${inscription.groupId}/inscription-fees`, { headers: { Accept: 'application/json' } })
                .then((response) => response.json())
                .then((data: InscriptionGroupFeesResponse) => setEditingGroupFees(data.fees))
                .catch(() => setEditingGroupFees([]));
        }

        setEditingLivreIds([]);
        setEditingAvailableLivres([]);
        setLivresError(undefined);
        setLoadingEditingLivres(true);
        fetch(`/backoffice/inscriptions/${inscription.id}/livres`, { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((data: { assignedIds: number[]; livres: Array<{ id: number; nom: string; quantite: number }> }) => {
                setEditingLivreIds(data.assignedIds);
                setEditingAvailableLivres(data.livres);
            })
            .finally(() => setLoadingEditingLivres(false));
    }

    /**
     * « Modification des frais d'inscription » — the same edit modal, opened
     * straight on its fee table (the frais live inside it, saved by their own
     * PUT .../fees endpoint). A separate row action because editing an amount
     * or a remise is a distinct, far more frequent job than correcting the
     * student/group fields at the top of the modal.
     */
    function openEditFrais(inscription: InscriptionRow) {
        openEdit(inscription);
        // After the modal has painted — the section only exists once
        // editingInscription is set and the modal body is mounted.
        window.setTimeout(() => {
            document.getElementById('ins-frais-section')?.scrollIntoView({ block: 'start', behavior: 'smooth' });
        }, 150);
    }

    function closeModal() {
        setShowModal(false);
        setEditingInscription(null);
        feesForm.setData('fee_lines', []);
        feesForm.clearErrors();
        setHiddenFees([]);
        setShowHiddenFees(false);
        setNewFeeToAdd('');
        setAvailableLivres([]);
        setEditingLivreIds([]);
        setEditingAvailableLivres([]);
        setLivresError(undefined);
        form.reset();
        form.clearErrors();
    }

    function handleNiveauChange(value: string) {
        form.setData('new_niveau', value);
        if (!niveauxAvecDomaine.includes(value)) {
            form.setData('new_domaine', '');
        }
        if (value !== niveauStudium) {
            form.setData('new_examen_type', '');
        }
    }

    function handleModeChange(mode: 'new' | 'existing') {
        form.setData({
            ...form.data,
            inscription_mode: mode,
            student_id: '',
            new_nom: '',
            new_prenom: '',
            new_sexe: '',
            new_date_naissance: '',
            new_cin: '',
            new_niveau: '',
            new_domaine: '',
            new_examen_type: '',
            new_email: '',
            new_telephone: '',
            new_whatsapp: '',
            new_adresse: '',
            new_parent_relation: '',
            new_parent_nom: '',
            new_parent_sexe: '',
            new_parent_cin: '',
            new_parent_telephone: '',
            new_parent_whatsapp: '',
        });
        form.clearErrors();
    }

    function handleGroupChange(groupId: number | '') {
        form.setData((data) => ({ ...data, group_id: groupId, fee_lines: [], livre_ids: [], date_debut: '', date_fin: '' }));
        setAvailableFeesPage(1);
        setAvailableLivres([]);

        if (groupId === '' || editingInscription) {
            return;
        }

        setLoadingGroupFees(true);
        fetch(`/backoffice/groups/${groupId}/inscription-fees`, {
            headers: { Accept: 'application/json' },
        })
            .then((response) => response.json())
            .then((data: InscriptionGroupFeesResponse) => {
                form.setData((prev) => ({
                    ...prev,
                    date_debut: data.dateDebut ?? '',
                    date_fin: data.dateFin ?? '',
                    fee_lines: data.fees.map((fee) => ({
                        fraisId: fee.fraisId,
                        nom: fee.nom,
                        montantInitial: fee.montantInitial,
                        remisePct: '',
                        remiseMontant: '',
                        note: '',
                        dateEcheance: fee.dateEcheance,
                    })),
                }));
            })
            .finally(() => setLoadingGroupFees(false));

        fetch(`/backoffice/groups/${groupId}/inscription-livres`, { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((data: { livres: Array<{ id: number; nom: string; quantite: number }> }) => {
                setAvailableLivres(data.livres);
            });
    }

    /**
     * Shared by the create form's "Frais disponibles" table and the edit
     * modal's "Frais de cette inscription" table — takes the lines array +
     * its own setter so each table stays on its own useForm instance.
     */
    function updateLine(
        lines: InscriptionFeeLine[],
        setLines: (next: InscriptionFeeLine[]) => void,
        index: number,
        field: keyof InscriptionFeeLine,
        value: string,
    ) {
        const next = [...lines];
        const line = { ...next[index], [field]: value };

        // Pct and DH are mutually exclusive, not two-way-synced: whichever
        // one the user just typed into is the sole source of truth (matches
        // the server's "pct wins when both are present" rule — the previous
        // back-fill-the-other-field approach violated that rule the moment
        // BOTH ended up non-empty, e.g. typing "100" DH on a 1300 fee
        // derived a rounded 7.69% that then computed 1200.03 instead of the
        // exact 1200 the user asked for). Editing the initial amount keeps
        // whichever discount mode was already active, recomputing its paired
        // display value for the still-open field only.
        if (field === 'remisePct') {
            line.remiseMontant = '';
        } else if (field === 'remiseMontant') {
            line.remisePct = '';
        } else if (field === 'montantInitial' && line.remisePct !== '') {
            const initial = parseFloat(value || '0');
            const pct = Math.min(100, Math.max(0, parseFloat(line.remisePct || '0')));
            line.remiseMontant = String(Math.round((initial * pct) / 100 * 100) / 100);
        }

        next[index] = line;
        setLines(next);
    }

    function linesTotal(lines: InscriptionFeeLine[]): number {
        return lines.reduce((sum, line) => {
            const initial = parseFloat(line.montantInitial || '0');
            const pct = line.remisePct !== '' ? parseFloat(line.remisePct) : null;
            const dh = line.remiseMontant !== '' ? parseFloat(line.remiseMontant) : null;

            return sum + computeLineMontant(initial, pct, dh);
        }, 0);
    }

    /**
     * Sum of what is still owed across the edit table's lines. Mirrors the
     * per-row calculation (live montant minus the server-computed `paye`,
     * clamped at 0 so an overpaid line can't offset an underpaid one).
     */
    function linesResteTotal(lines: InscriptionFeeLine[]): number {
        return lines.reduce((sum, line) => {
            const initial = parseFloat(line.montantInitial || '0');
            const pct = line.remisePct !== '' ? parseFloat(line.remisePct) : null;
            const dh = line.remiseMontant !== '' ? parseFloat(line.remiseMontant) : null;
            const paye = parseFloat(line.paye ?? '0') || 0;

            return sum + Math.max(0, computeLineMontant(initial, pct, dh) - paye);
        }, 0);
    }

    function setLine(index: number, field: keyof InscriptionFeeLine, value: string) {
        updateLine(form.data.fee_lines, (next) => form.setData('fee_lines', next), index, field, value);
    }

    function lineTotal(): number {
        return linesTotal(form.data.fee_lines);
    }

    function persistFees(lines: InscriptionFeeLine[]) {
        if (!editingInscription) {
            return;
        }

        // Same camelCase → snake_case mapping as the create form's submit()
        // — the server validates fee_lines.*.montant_initial/remise_pct/…
        feesForm.transform(() => ({
            fee_lines: lines.map((line) => ({
                id: line.id,
                frais_id: line.fraisId,
                nom: line.nom,
                montant_initial: line.montantInitial,
                remise_pct: line.remisePct,
                remise_montant: line.remiseMontant,
                note: line.note,
                date_echeance: line.dateEcheance,
            })),
        }));

        feesForm.put(`/backoffice/inscriptions/${editingInscription.id}/fees`, {
            preserveScroll: true,
            onFinish: () => feesForm.transform((data) => data),
        });
    }

    function scheduleFeesSave(lines: InscriptionFeeLine[]) {
        if (feesSaveTimeout.current) {
            clearTimeout(feesSaveTimeout.current);
        }
        feesSaveTimeout.current = setTimeout(() => persistFees(lines), 600);
    }

    function setEditingLine(index: number, field: keyof InscriptionFeeLine, value: string) {
        updateLine(feesForm.data.fee_lines, (next) => {
            feesForm.setData('fee_lines', next);
            scheduleFeesSave(next);
        }, index, field, value);
    }

    /**
     * Adds a new fee line to an existing inscription, from the active
     * catalog (`frais` prop) — the line has no `id` yet, so
     * MettreAJourFraisInscription creates it on the next scheduleFeesSave
     * (same "no id = create" rule the create form already relies on).
     */
    function addEditingLine() {
        if (newFeeToAdd === '') {
            return;
        }

        const catalogFee = frais.find((f) => f.id === newFeeToAdd);

        if (!catalogFee) {
            return;
        }

        const groupFee = editingGroupFees.find((fee) => fee.fraisId === catalogFee.id);

        const next = [
            ...feesForm.data.fee_lines,
            {
                fraisId: catalogFee.id,
                nom: catalogFee.label,
                montantInitial: groupFee?.montantInitial ?? catalogFee.montantDefaut,
                remisePct: '',
                remiseMontant: '',
                note: '',
                dateEcheance: groupFee?.dateEcheance ?? '',
            },
        ];

        feesForm.setData('fee_lines', next);
        setNewFeeToAdd('');
        scheduleFeesSave(next);
    }

    /**
     * Hides the fee instead of deleting it (BasculerVisibiliteFraisInscription)
     * — the row and its payment history stay intact and it moves to "Frais
     * masqués", restorable from there. A brand-new line with no `id` yet
     * (added client-side, never saved) is simply dropped from the form
     * instead, since there is nothing to hide server-side.
     */
    function removeEditingLine(index: number) {
        const line = feesForm.data.fee_lines[index];

        if (!line.id || !editingInscription) {
            feesForm.setData('fee_lines', feesForm.data.fee_lines.filter((_, i) => i !== index));
            return;
        }

        if (feesSaveTimeout.current) {
            clearTimeout(feesSaveTimeout.current);
        }

        // fetch(), not router.post(): an Inertia visit re-runs the index
        // controller and rebuilds the entire page payload (paginated list +
        // students/groups/frais catalogs) just to hide one row, which is what
        // made this feel slow. The endpoint returns plain JSON and the table
        // below is updated from local state.
        setHideProcessingId(line.id);
        postFeeVisibility(`/backoffice/inscriptions/${editingInscription.id}/fees/${line.id}/hide`)
            .then(() => {
                feesForm.setData('fee_lines', feesForm.data.fee_lines.filter((_, i) => i !== index));
                setHiddenFees((previous) => [
                    ...previous,
                    { id: line.id as number, nom: line.nom, montant: line.montantInitial, dateEcheance: line.dateEcheance },
                ]);
            })
            .finally(() => setHideProcessingId(null));
    }

    function restoreHiddenFee(fee: HiddenInscriptionFee) {
        if (!editingInscription) {
            return;
        }

        // fetch() — see removeEditingLine(). The response carries the restored
        // line's full shape, so it is spliced straight back into the table
        // instead of re-fetching the whole fee list afterwards (the old path
        // cost an Inertia reload PLUS a second request).
        setRestoreProcessingId(fee.id);
        postFeeVisibility(`/backoffice/inscriptions/${editingInscription.id}/fees/${fee.id}/restore`)
            .then((data) => {
                setHiddenFees((previous) => previous.filter((f) => f.id !== fee.id));

                if (data.fee) {
                    feesForm.setData('fee_lines', [...feesForm.data.fee_lines, data.fee]);
                }
            })
            .finally(() => setRestoreProcessingId(null));
    }

    /**
     * Saves the edit modal's book selection — its own PUT endpoint
     * (AssignerLivresInscription), independent of the base-fields form. A
     * book already assigned stays untouched even if resubmitted; only newly
     * checked/unchecked books move stock (add = -1, remove = +1), which is
     * how a later level change can add a further book without re-charging
     * stock for ones already given.
     */
    function saveEditingLivres(onDone?: () => void) {
        if (!editingInscription) {
            onDone?.();

            return;
        }

        setLivresProcessing(true);
        setLivresError(undefined);
        router.put(
            `/backoffice/inscriptions/${editingInscription.id}/livres`,
            { livre_ids: editingLivreIds },
            {
                preserveScroll: true,
                preserveState: true,
                // `onDone` continues the caller's flow (the modal's own
                // "Modifier" submit). It runs on success only — if the book
                // sync fails (out of stock), the base-fields PUT is skipped
                // so the modal stays open showing the error instead of
                // closing as though everything saved.
                onSuccess: () => {
                    if (onDone) {
                        onDone();

                        return;
                    }

                    // Standalone save: re-read the assignment + stock counts
                    // the action just changed, so the "(N en stock)" labels
                    // show the decremented quantities, not the pre-save ones.
                    setLoadingEditingLivres(true);
                    fetch(`/backoffice/inscriptions/${editingInscription.id}/livres`, {
                        headers: { Accept: 'application/json' },
                    })
                        .then((response) => response.json())
                        .then((data: { assignedIds: number[]; livres: Array<{ id: number; nom: string; quantite: number }> }) => {
                            setEditingLivreIds(data.assignedIds);
                            setEditingAvailableLivres(data.livres);
                        })
                        .finally(() => setLoadingEditingLivres(false));
                },
                onError: (errors) => setLivresError(errors.livre_ids ?? 'Action impossible.'),
                onFinish: () => setLivresProcessing(false),
            },
        );
    }

    function submit(event: FormEvent) {
        event.preventDefault();

        // Fee lines auto-save on a 600ms debounce. Submitting inside that
        // window would close the modal with the timer still pending, so the
        // last edit to an amount/discount would be thrown away — flush it
        // now, before anything else runs.
        if (feesSaveTimeout.current) {
            clearTimeout(feesSaveTimeout.current);
            feesSaveTimeout.current = null;
            persistFees(feesForm.data.fee_lines);
        }

        const options = {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            // Reset the transform so a later submit on this shared form
            // instance doesn't carry the fee_lines mapping over.
            onFinish: () => form.transform((data) => data),
        };

        // fee_lines lives in camelCase client state (InscriptionFeeLine) but
        // the server validates snake_case (fee_lines.*.montant_initial /
        // .remise_pct / .remise_montant / .frais_id / .date_echeance) —
        // without this mapping every submit silently validated as "no
        // discount, zero amount" instead of failing loudly (all rules are
        // nullable), so fee lines saved with no montant_initial/frais_id.
        form.transform((data) => ({
            ...data,
            fee_lines: data.fee_lines.map((line) => ({
                frais_id: line.fraisId,
                nom: line.nom,
                montant_initial: line.montantInitial,
                remise_pct: line.remisePct,
                remise_montant: line.remiseMontant,
                note: line.note,
                date_echeance: line.dateEcheance,
            })),
        }));

        if (editingInscription) {
            // The book selection lives outside this form (its own endpoint,
            // AssignerLivresInscription, because assigning a book moves
            // stock). It used to be saved ONLY by a separate "Enregistrer les
            // livres" button, so the normal "Modifier" flow silently dropped
            // it — books picked in the modal were never persisted and the
            // stock was never decremented. Chain it here so one click saves
            // the whole modal, and only run the base-fields PUT once the
            // book sync has succeeded.
            saveEditingLivres(() => form.put(`/backoffice/inscriptions/${editingInscription.id}`, options));
        } else {
            form.post('/backoffice/inscriptions', options);
        }
    }

    function confirmDelete(inscription: InscriptionRow) {
        setDeleteTarget(inscription);
        setDeleteError(undefined);
    }

    function handleDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleteProcessing(true);
        router.delete(`/backoffice/inscriptions/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteTarget(null);
                setDeleteError(undefined);
            },
            onError: (errors) => {
                setDeleteError(errors.delete ?? 'Suppression impossible.');
            },
            onFinish: () => setDeleteProcessing(false),
        });
    }

    function confirmStatutChange(inscription: InscriptionRow, statut: 'Active') {
        setStatutTarget({ inscription, statut });
        setStatutError(undefined);
    }

    function openCancel(inscription: InscriptionRow) {
        setCancelTarget(inscription);
        cancelForm.clearErrors();
        cancelForm.setData({
            motif_annulation: '',
            // Defaults to today: the enrollment stops when the cancellation is
            // recorded unless the user says otherwise.
            date_fin: new Date().toISOString().slice(0, 10),
            unpaid_fees_scope: '',
            note: '',
        });
    }

    function closeCancel() {
        setCancelTarget(null);
        cancelForm.clearErrors();
    }

    function submitCancel(event: FormEvent) {
        event.preventDefault();

        if (!cancelTarget) {
            return;
        }

        cancelForm.post(`/backoffice/inscriptions/${cancelTarget.id}/annuler`, {
            preserveScroll: true,
            onSuccess: () => closeCancel(),
        });
    }

    function handleStatutChange() {
        if (!statutTarget) {
            return;
        }

        setStatutProcessing(true);
        router.patch(
            `/backoffice/inscriptions/${statutTarget.inscription.id}/statut`,
            { statut: statutTarget.statut },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setStatutTarget(null);
                    setStatutError(undefined);
                },
                onError: (errors) => {
                    setStatutError(errors.statut ?? 'Action impossible.');
                },
                onFinish: () => setStatutProcessing(false),
            },
        );
    }

    function openChangeGroup(inscription: InscriptionRow) {
        setChangeGroupTarget(inscription);
        setShowFeesDetails(false);
        changeGroupForm.clearErrors();
        changeGroupForm.setData({
            new_group_id: '',
            date_fin: new Date().toISOString().slice(0, 10),
            date_debut: new Date().toISOString().slice(0, 10),
            unpaid_fees_scope: '',
            note: '',
            transfer_fee_ids: [],
        });
        setShowTransferFees(false);
        setTransferableFees([]);
        setLoadingTransferableFees(true);
        fetch(`/backoffice/inscriptions/${inscription.id}/fees`, { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((data: { fees: InscriptionFeeLine[] }) =>
                setTransferableFees(data.fees.filter((fee) => (parseFloat(fee.paye ?? '0') || 0) > 0)),
            )
            .catch(() => setTransferableFees([]))
            .finally(() => setLoadingTransferableFees(false));
    }

    function closeChangeGroup() {
        setChangeGroupTarget(null);
        changeGroupForm.clearErrors();
    }

    function toggleTransferFee(id: number) {
        const current = changeGroupForm.data.transfer_fee_ids;
        changeGroupForm.setData(
            'transfer_fee_ids',
            current.includes(id) ? current.filter((feeId) => feeId !== id) : [...current, id],
        );
    }

    function toggleShowTransferFees() {
        const next = !showTransferFees;
        setShowTransferFees(next);
        if (!next) {
            changeGroupForm.setData('transfer_fee_ids', []);
        }
    }

    function submitChangeGroup(event: FormEvent) {
        event.preventDefault();
        if (!changeGroupTarget) {
            return;
        }

        changeGroupForm.post(`/backoffice/inscriptions/${changeGroupTarget.id}/change-group`, {
            preserveScroll: true,
            onSuccess: () => closeChangeGroup(),
        });
    }

    return (
        <BackofficeLayout
            title="Inscriptions"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Inscriptions' }]}
            actions={
                <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                    <i className="ti ti-square-rounded-plus me-2" />
                    Ajouter une inscription
                </button>
            }
        >
            <Card title="Inscriptions" bodyClassName="p-0 py-3">
                {/* Per-column filter row (reference CRM's Inscriptions filters,
                    without Offres) — replaces the dropdown + single search box. */}
                <div className="px-3 pt-2">
                    <TableToolbar>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor="ins-f-reference">
                                Référence
                            </label>
                            <FilterTextInput
                                id="ins-f-reference"
                                value={filters.referenceFilter}
                                onChange={(value) => reload({ referenceFilter: value })}
                                placeholder="ex : I19"
                            />
                        </div>
                        <div style={{ width: 240 }}>
                            <label className="form-label" htmlFor="ins-f-student">
                                Étudiant
                            </label>
                            <SelectField
                                id="ins-f-student"
                                options={studentOptions}
                                placeholder="Choisir un étudiant"
                                value={filters.studentFilter}
                                onChange={(event) => reload({ studentFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 190 }}>
                            <label className="form-label" htmlFor="ins-f-statut">
                                Statut
                            </label>
                            <SelectField
                                id="ins-f-statut"
                                options={statutFilterOptions}
                                placeholder="Tous les statuts"
                                value={filters.statutFilter}
                                onChange={(event) => reload({ statutFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 240 }}>
                            <label className="form-label" htmlFor="ins-f-groupe">
                                Groupe
                            </label>
                            <SelectField
                                id="ins-f-groupe"
                                options={groupOptions}
                                placeholder="Choisir une formation"
                                value={filters.groupFilter}
                                onChange={(event) => reload({ groupFilter: event.target.value })}
                            />
                        </div>
                    </TableToolbar>
                </div>

                <TableLengthRow
                    perPage={filters.perPage}
                    perPageOptions={perPageOptions}
                    onPerPageChange={(perPage) => reload({ perPage })}
                />

                {inscriptions.data.length === 0 ? (
                    <EmptyState title="Aucune inscription pour le moment" icon="ti ti-clipboard-list" />
                ) : (
                    <>
                        <DataTable
                            loading={isLoading}
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th className="text-wrap-cell">Étudiant</th>
                                    <th className="text-wrap-cell">Groupe</th>
                                    <th>Date d'inscription</th>
                                    <th>Date de début</th>
                                    <th>Date de fin</th>
                                    <th>Statut</th>
                                    <th className="text-end">Action</th>
                                </tr>
                            }
                        >
                            {inscriptions.data.map((inscription) => (
                                <tr key={inscription.id}>
                                    <td>
                                        <code>{inscription.reference}</code>
                                    </td>
                                    <td className="fw-medium text-wrap-cell">{inscription.student ?? '—'}</td>
                                    <td className="text-wrap-cell">{inscription.groupe ?? '—'}</td>
                                    <td>{inscription.date ?? '—'}</td>
                                    <td>{inscription.dateDebut ?? '—'}</td>
                                    <td>{inscription.dateFin ?? '—'}</td>
                                    <td>
                                        <StatusBadge label={inscription.statut} variant={statutVariant(inscription.statut)} dot />
                                    </td>
                                    <td className="text-end">
                                        {/* Grouped into four blocks separated by
                                            dividers: edit-the-record, change its
                                            lifecycle, move it to another group,
                                            then money — so the destructive and the
                                            financial actions are never adjacent to
                                            a routine "Modifier" click. */}
                                        <RowActions view={inscription.showUrl}>
                                            <RowActionItem icon="ti-edit" onClick={() => openEdit(inscription)}>
                                                Modifier
                                            </RowActionItem>

                                            {canManageFees && (
                                                <RowActionItem icon="ti-edit" onClick={() => openEditFrais(inscription)}>
                                                    Modification des frais d&apos;inscription
                                                </RowActionItem>
                                            )}

                                            <RowActionDivider />

                                            {inscription.statut === 'Active' ? (
                                                <RowActionItem icon="ti-x" danger onClick={() => openCancel(inscription)}>
                                                    Annuler
                                                </RowActionItem>
                                            ) : (
                                                <RowActionItem icon="ti-refresh" onClick={() => confirmStatutChange(inscription, 'Active')}>
                                                    Réactiver
                                                </RowActionItem>
                                            )}

                                            {canChangeGroup && inscription.statut === 'Active' && (
                                                <>
                                                    <RowActionDivider />
                                                    <RowActionItem icon="ti-replace" onClick={() => openChangeGroup(inscription)}>
                                                        Changement de groupe
                                                    </RowActionItem>
                                                </>
                                            )}

                                            <RowActionDivider />

                                            <RowActionItem icon="ti-credit-card" onClick={() => openSuivi(inscription)}>
                                                Suivi des paiements
                                            </RowActionItem>

                                            {/* Active only: settling frais from here on a
                                                cancelled/archived registration would book money
                                                against one the student no longer holds. (The
                                                Encaissements page's own dropdown is wider — it
                                                lists every inscription of the active year — so a
                                                deliberate correction on a closed registration
                                                stays possible there, where the cashier picks it
                                                explicitly.) */}
                                            {canCreatePayment && inscription.statut === 'Active' && (
                                                <RowActionItem icon="ti-credit-card" onClick={() => openPaiement(inscription)}>
                                                    Ajouter un paiement
                                                </RowActionItem>
                                            )}

                                            <RowActionDivider />

                                            <RowActionItem icon="ti-trash" danger onClick={() => confirmDelete(inscription)}>
                                                Supprimer
                                            </RowActionItem>
                                        </RowActions>
                                    </td>
                                </tr>
                            ))}
                        </DataTable>
                        <Pagination paginator={inscriptions} />
                    </>
                )}
            </Card>

            <Modal
                show={showModal}
                title={editingInscription ? "Modifier l'inscription" : 'Ajouter une inscription'}
                onClose={closeModal}
                processing={form.processing}
                size="xl"
            >
                <form onSubmit={submit} onKeyDown={blockImplicitSubmit}>
                    <div className="row">
                        {!editingInscription && (
                            <div className="col-md-4">
                                <SelectField
                                    id="ins-mode"
                                    label="Inscrire pour"
                                    required
                                    options={[
                                        { value: 'new', label: 'Nouvel étudiant' },
                                        { value: 'existing', label: 'Étudiant existant' },
                                    ]}
                                    value={form.data.inscription_mode}
                                    onChange={(event) => handleModeChange(event.target.value as 'new' | 'existing')}
                                />
                            </div>
                        )}

                        {(editingInscription || form.data.inscription_mode === 'existing') && (
                            <div className="col-md-8">
                                <SelectField
                                    id="ins-student"
                                    label="Étudiant"
                                    required
                                    options={studentOptions}
                                    placeholder="Choisir…"
                                    value={form.data.student_id}
                                    onChange={(event) => form.setData('student_id', event.target.value ? Number(event.target.value) : '')}
                                    error={form.errors.student_id}
                                />
                            </div>
                        )}

                        {!editingInscription && form.data.inscription_mode === 'new' && (
                            <>
                                <div className="col-md-4">
                                    <FormField
                                        id="ins-new-nom"
                                        label="Nom"
                                        required
                                        value={form.data.new_nom}
                                        onChange={(event) => form.setData('new_nom', event.target.value)}
                                        error={form.errors.new_nom}
                                        placeholder="ex : Rafik"
                                    />
                                </div>
                                <div className="col-md-4">
                                    <FormField
                                        id="ins-new-prenom"
                                        label="Prénom"
                                        required
                                        value={form.data.new_prenom}
                                        onChange={(event) => form.setData('new_prenom', event.target.value)}
                                        error={form.errors.new_prenom}
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
                                                        name="ins-new-sexe"
                                                        id={`ins-new-sexe-${sexe}`}
                                                        value={sexe}
                                                        checked={form.data.new_sexe === sexe}
                                                        onChange={() => form.setData('new_sexe', sexe)}
                                                        autoComplete="off"
                                                    />
                                                    <label
                                                        className="btn btn-outline-primary d-inline-flex align-items-center justify-content-center px-4"
                                                        htmlFor={`ins-new-sexe-${sexe}`}
                                                    >
                                                        <i className={`ti ${sexe === 'Homme' ? 'ti-man' : 'ti-woman'} me-1`} />
                                                        {sexe === 'Homme' ? 'Masculin' : 'Féminin'}
                                                    </label>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                                <div className="col-md-4">
                                    <DateField
                                        id="ins-new-naiss"
                                        label="Date de naissance"
                                        value={form.data.new_date_naissance}
                                        onChange={(event) => form.setData('new_date_naissance', event.target.value)}
                                        error={form.errors.new_date_naissance}
                                    />
                                </div>
                                <div className="col-md-4">
                                    <FormField
                                        id="ins-new-cin"
                                        label="Carte d'identité"
                                        value={form.data.new_cin}
                                        onChange={(event) => form.setData('new_cin', event.target.value)}
                                        error={form.errors.new_cin}
                                        placeholder="ex : AB123456"
                                    />
                                </div>
                                <div className="col-md-4">
                                    <SelectField
                                        id="ins-new-niveau"
                                        label="Intéressé par"
                                        options={niveauOptions}
                                        placeholder="Choisir…"
                                        value={form.data.new_niveau}
                                        onChange={(event) => handleNiveauChange(event.target.value)}
                                        error={form.errors.new_niveau}
                                    />
                                </div>
                                {showsDomaine && (
                                    <div className="col-md-4">
                                        <SelectField
                                            id="ins-new-domaine"
                                            label="Domaine"
                                            required
                                            options={domaineOptions}
                                            placeholder="Choisir…"
                                            value={form.data.new_domaine}
                                            onChange={(event) => form.setData('new_domaine', event.target.value)}
                                            error={form.errors.new_domaine}
                                        />
                                    </div>
                                )}
                                {showsExamen && (
                                    <div className="col-md-4">
                                        <SelectField
                                            id="ins-new-examen"
                                            label="Examen d'entrée"
                                            required
                                            options={examenOptions}
                                            placeholder="Choisir…"
                                            value={form.data.new_examen_type}
                                            onChange={(event) => form.setData('new_examen_type', event.target.value)}
                                            error={form.errors.new_examen_type}
                                        />
                                    </div>
                                )}
                                <div className="col-md-4">
                                    <DateField
                                        id="ins-new-date"
                                        label="Date d'inscription"
                                        required
                                        value={form.data.date_inscription}
                                        onChange={(event) => form.setData('date_inscription', event.target.value)}
                                        error={form.errors.date_inscription}
                                    />
                                </div>
                            </>
                        )}

                        {editingInscription && (
                            <div className="col-md-4">
                                <DateField
                                    id="ins-edit-date"
                                    label="Date d'inscription"
                                    required
                                    value={form.data.date_inscription}
                                    onChange={(event) => form.setData('date_inscription', event.target.value)}
                                    error={form.errors.date_inscription}
                                />
                            </div>
                        )}
                    </div>

                    <ul className="nav nav-tabs p-0 border-bottom rounded-0 mt-2 mb-4" role="tablist">
                        <li className="nav-item">
                            <button
                                type="button"
                                className={`nav-link d-inline-flex align-items-center${activeTab === 'affectation' ? ' active' : ''}`}
                                onClick={() => setActiveTab('affectation')}
                            >
                                <i className="ti ti-calendar-event me-1" />
                                Affectation
                            </button>
                        </li>
                        {!editingInscription && form.data.inscription_mode === 'new' && (
                            <>
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
                            </>
                        )}
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

                    {activeTab === 'affectation' && (
                        <div>
                            <div className="row">
                                <div className="col-md-4">
                                    {editingInscription ? (
                                        // Group is read-only here — changing group has its own
                                        // dedicated "Changement de groupe" flow (fee migration +
                                        // archival snapshot, registrations.change-group gate);
                                        // this plain edit form must never silently move the
                                        // student to another group.
                                        <FormField
                                            id="ins-group"
                                            label="Groupe"
                                            value={editingInscription.groupe ?? '—'}
                                            readOnly
                                            disabled
                                        />
                                    ) : (
                                        <SelectField
                                            id="ins-group"
                                            label="Groupe"
                                            required
                                            options={groupOptions}
                                            placeholder="Choisir une formation"
                                            value={form.data.group_id}
                                            onChange={(event) => handleGroupChange(event.target.value ? Number(event.target.value) : '')}
                                            error={form.errors.group_id}
                                        />
                                    )}
                                </div>
                                {editingInscription && (
                                    <div className="col-md-4">
                                        <SelectField
                                            id="ins-statut"
                                            label="Statut"
                                            required
                                            options={statutOptions}
                                            value={form.data.statut}
                                            onChange={(event) => form.setData('statut', event.target.value)}
                                            error={form.errors.statut}
                                        />
                                    </div>
                                )}
                                {form.data.group_id !== '' && (
                                    <>
                                        <div className="col-md-4">
                                            <DateField
                                                id="ins-debut"
                                                label="Date de début"
                                                value={form.data.date_debut}
                                                disabled
                                            />
                                            <div className="form-text">Provient du groupe</div>
                                        </div>
                                        <div className="col-md-4">
                                            <DateField id="ins-fin" label="Date de fin" value={form.data.date_fin} disabled />
                                            <div className="form-text">Provient du groupe</div>
                                        </div>
                                    </>
                                )}
                            </div>

                            {!editingInscription && (
                                <div className="border-top pt-3">
                                    <h6 className="mb-1">Frais disponibles</h6>
                                    {form.data.group_id === '' ? (
                                        <p className="text-muted fs-13">Sélectionnez un groupe pour voir ses frais disponibles.</p>
                                    ) : loadingGroupFees ? (
                                        <p className="text-muted fs-13">Chargement…</p>
                                    ) : form.data.fee_lines.length === 0 ? (
                                        <div className="alert alert-warning mb-0">
                                            Ce groupe n'a aucun frais assigné. Assignez des frais sur le groupe d'abord.
                                        </div>
                                    ) : (
                                        <div className="table-responsive">
                                            <table className="table table-sm align-middle mb-1">
                                                <thead className="thead-light">
                                                    <tr>
                                                        <th>Frais</th>
                                                        <th>Initial (DH)</th>
                                                        <th>Remise</th>
                                                        <th>Note</th>
                                                        <th className="text-end">Montant</th>
                                                        <th className="text-end">Reste</th>
                                                        <th>Échéance</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {form.data.fee_lines
                                                        .slice(
                                                            (availableFeesPage - 1) * AVAILABLE_FEES_PER_PAGE,
                                                            availableFeesPage * AVAILABLE_FEES_PER_PAGE,
                                                        )
                                                        .map((line, pageIndex) => {
                                                        const index = (availableFeesPage - 1) * AVAILABLE_FEES_PER_PAGE + pageIndex;
                                                        const initial = parseFloat(line.montantInitial || '0');
                                                        const pct = line.remisePct !== '' ? parseFloat(line.remisePct) : null;
                                                        const dh = line.remiseMontant !== '' ? parseFloat(line.remiseMontant) : null;
                                                        const final = computeLineMontant(initial, pct, dh);
                                                        const montantError = (form.errors as Record<string, string>)[`fee_lines.${index}.montant_initial`];

                                                        return (
                                                            <tr key={line.fraisId ?? index}>
                                                                <td className="fw-medium text-wrap-cell" style={{ minWidth: 220 }}>{line.nom}</td>
                                                                <td style={{ width: 110 }}>
                                                                    <input
                                                                        type="number"
                                                                        step="0.01"
                                                                        min="0"
                                                                        className={`form-control form-control-sm${montantError ? ' is-invalid' : ''}`}
                                                                        value={line.montantInitial}
                                                                        onChange={(event) => setLine(index, 'montantInitial', event.target.value)}
                                                                    />
                                                                </td>
                                                                <td style={{ width: 210, minWidth: 210 }}>
                                                                    <div className="input-group input-group-sm flex-nowrap fee-remise-group">
                                                                        <input
                                                                            type="number"
                                                                            step="0.01"
                                                                            min="0"
                                                                            max="100"
                                                                            className="form-control"
                                                                            placeholder="%"
                                                                            value={line.remisePct}
                                                                            onChange={(event) => setLine(index, 'remisePct', event.target.value)}
                                                                        />
                                                                        <span className="input-group-text">%</span>
                                                                        <input
                                                                            type="number"
                                                                            step="0.01"
                                                                            min="0"
                                                                            className="form-control"
                                                                            placeholder="DH"
                                                                            value={line.remiseMontant}
                                                                            onChange={(event) => setLine(index, 'remiseMontant', event.target.value)}
                                                                        />
                                                                        <span className="input-group-text">DH</span>
                                                                    </div>
                                                                </td>
                                                                <td style={{ width: 150 }}>
                                                                    <input
                                                                        type="text"
                                                                        className="form-control form-control-sm"
                                                                        value={line.note}
                                                                        onChange={(event) => setLine(index, 'note', event.target.value)}
                                                                    />
                                                                </td>
                                                                <td className="text-end fw-semibold" style={{ width: 110 }}>
                                                                    {final.toFixed(2)} DH
                                                                </td>
                                                                {/*
                                                                 * On a new inscription nothing is paid yet, so reste always
                                                                 * equals montant. Shown anyway so this table's columns line up
                                                                 * with the edit modal's — the same fee reads the same way
                                                                 * before and after saving.
                                                                 */}
                                                                <td className="text-end fw-semibold text-danger" style={{ width: 110 }}>
                                                                    {final.toFixed(2)} DH
                                                                </td>
                                                                <td style={{ width: 150 }}>
                                                                    <DateField
                                                                        id={`ins-fee-date-${index}`}
                                                                        value={line.dateEcheance}
                                                                        onChange={(event) => setLine(index, 'dateEcheance', event.target.value)}
                                                                        panelAlign="right"
                                                                    />
                                                                </td>
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colSpan={4} className="text-end fw-semibold">
                                                            Total à payer
                                                        </td>
                                                        <td className="text-end fw-bold">{lineTotal().toFixed(2)} DH</td>
                                                        <td className="text-end fw-bold">{lineTotal().toFixed(2)} DH</td>
                                                        <td />
                                                    </tr>
                                                </tfoot>
                                            </table>
                                            <ClientFeesPagination
                                                total={form.data.fee_lines.length}
                                                perPage={AVAILABLE_FEES_PER_PAGE}
                                                page={availableFeesPage}
                                                onPageChange={setAvailableFeesPage}
                                            />
                                        </div>
                                    )}
                                </div>
                            )}

                            {!editingInscription && (
                                <div className="border-top pt-3">
                                    <h6 className="mb-1">Livres</h6>
                                    {form.data.group_id === '' ? (
                                        <p className="text-muted fs-13">Sélectionnez un groupe pour voir les livres disponibles.</p>
                                    ) : availableLivres.length === 0 ? (
                                        <p className="text-muted fs-13">Aucun livre en stock pour ce centre.</p>
                                    ) : (
                                        <MultiSelectField
                                            id="ins-livres"
                                            options={availableLivres.map((l) => ({
                                                value: l.id,
                                                label: `${l.nom} (${l.quantite} en stock)`,
                                            }))}
                                            placeholder="Sélectionner un ou plusieurs livres…"
                                            values={form.data.livre_ids.map(String)}
                                            onChange={(values) => form.setData('livre_ids', values.map(Number))}
                                            error={(form.errors as Record<string, string>).livre_ids}
                                        />
                                    )}
                                </div>
                            )}

                            {editingInscription && (
                                // id: the « Modification des frais d'inscription »
                                // row action opens this same modal and scrolls
                                // straight here, so the fee table is reachable in
                                // one click instead of « Modifier » + scroll.
                                <div className="border-top pt-3" id="ins-frais-section">
                                    <div className="d-flex align-items-center justify-content-between mb-1">
                                        <h6 className="mb-0">Frais de cette inscription</h6>
                                    </div>
                                    {!canManageFees && (
                                        <p className="text-muted fs-13">
                                            Lecture seule — vous n'avez pas la permission de modifier les frais.
                                        </p>
                                    )}
                                    {canManageFees && !loadingEditingFees && (
                                        <div className="d-flex align-items-center gap-2 mb-3" style={{ maxWidth: 420 }}>
                                            <div className="flex-grow-1">
                                                <SelectField
                                                    id="ins-add-frais"
                                                    options={fraisOptions}
                                                    placeholder="Ajouter un frais du catalogue…"
                                                    value={newFeeToAdd}
                                                    onChange={(event) =>
                                                        setNewFeeToAdd(event.target.value ? Number(event.target.value) : '')
                                                    }
                                                />
                                            </div>
                                            <button
                                                type="button"
                                                className="btn btn-primary btn-sm flex-shrink-0"
                                                disabled={newFeeToAdd === ''}
                                                onClick={addEditingLine}
                                            >
                                                <i className="ti ti-plus" />
                                            </button>
                                        </div>
                                    )}
                                    {loadingEditingFees ? (
                                        <p className="text-muted fs-13">Chargement…</p>
                                    ) : feesForm.data.fee_lines.length === 0 ? (
                                        <p className="text-muted fs-13">Aucun frais enregistré pour cette inscription.</p>
                                    ) : (
                                        <div className="table-responsive">
                                            <table className="table table-sm align-middle mb-1">
                                                <thead className="thead-light">
                                                    <tr>
                                                        <th>Frais</th>
                                                        <th>Initial (DH)</th>
                                                        <th>Remise</th>
                                                        <th>Note</th>
                                                        <th className="text-end">Montant</th>
                                                        <th className="text-end">Reste</th>
                                                        <th>Échéance</th>
                                                        <th>Statut</th>
                                                        {canManageFees && <th />}
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {feesForm.data.fee_lines.map((line, index) => {
                                                        const initial = parseFloat(line.montantInitial || '0');
                                                        const pct = line.remisePct !== '' ? parseFloat(line.remisePct) : null;
                                                        const dh = line.remiseMontant !== '' ? parseFloat(line.remiseMontant) : null;
                                                        const final = computeLineMontant(initial, pct, dh);
                                                        // `paye` is server-computed (sum of the line's encaissements) and
                                                        // never submitted back. `final` is the LIVE edited montant, so while
                                                        // the user changes initial/remise the reste previews against the
                                                        // already-paid total — same unsaved-preview behaviour the Montant
                                                        // column already has. Clamped at 0: an overpaid line reads 0, not
                                                        // a negative.
                                                        const paye = parseFloat(line.paye ?? '0') || 0;
                                                        const reste = Math.max(0, final - paye);
                                                        const rowErrors = feesForm.errors as Record<string, string>;
                                                        const montantError = rowErrors[`fee_lines.${index}.montant_initial`];

                                                        return (
                                                            <tr key={line.id ?? `new-${index}`}>
                                                                <td style={{ width: 240, minWidth: 240 }}>
                                                                    {canManageFees ? (
                                                                        <input
                                                                            type="text"
                                                                            className="form-control form-control-sm"
                                                                            value={line.nom}
                                                                            onChange={(event) => setEditingLine(index, 'nom', event.target.value)}
                                                                        />
                                                                    ) : (
                                                                        <span className="fw-medium">{line.nom}</span>
                                                                    )}
                                                                </td>
                                                                <td style={{ width: 110 }}>
                                                                    <input
                                                                        type="number"
                                                                        step="0.01"
                                                                        min="0"
                                                                        disabled={!canManageFees}
                                                                        className={`form-control form-control-sm${montantError ? ' is-invalid' : ''}`}
                                                                        value={line.montantInitial}
                                                                        onChange={(event) => setEditingLine(index, 'montantInitial', event.target.value)}
                                                                    />
                                                                </td>
                                                                <td style={{ width: 210, minWidth: 210 }}>
                                                                    <div className="input-group input-group-sm flex-nowrap fee-remise-group">
                                                                        <input
                                                                            type="number"
                                                                            step="0.01"
                                                                            min="0"
                                                                            max="100"
                                                                            disabled={!canManageFees}
                                                                            className="form-control"
                                                                            placeholder="%"
                                                                            value={line.remisePct}
                                                                            onChange={(event) => setEditingLine(index, 'remisePct', event.target.value)}
                                                                        />
                                                                        <span className="input-group-text">%</span>
                                                                        <input
                                                                            type="number"
                                                                            step="0.01"
                                                                            min="0"
                                                                            disabled={!canManageFees}
                                                                            className="form-control"
                                                                            placeholder="DH"
                                                                            value={line.remiseMontant}
                                                                            onChange={(event) => setEditingLine(index, 'remiseMontant', event.target.value)}
                                                                        />
                                                                        <span className="input-group-text">DH</span>
                                                                    </div>
                                                                </td>
                                                                <td style={{ width: 150 }}>
                                                                    <input
                                                                        type="text"
                                                                        disabled={!canManageFees}
                                                                        className="form-control form-control-sm"
                                                                        value={line.note}
                                                                        onChange={(event) => setEditingLine(index, 'note', event.target.value)}
                                                                    />
                                                                </td>
                                                                <td className="text-end fw-semibold" style={{ width: 110 }}>
                                                                    {final.toFixed(2)} DH
                                                                </td>
                                                                <td
                                                                    className={`text-end fw-semibold${reste > 0 ? ' text-danger' : ' text-success'}`}
                                                                    style={{ width: 110 }}
                                                                    title={`Payé : ${paye.toFixed(2)} DH`}
                                                                >
                                                                    {reste.toFixed(2)} DH
                                                                </td>
                                                                <td style={{ width: 150 }}>
                                                                    <DateField
                                                                        id={`ins-edit-fee-date-${index}`}
                                                                        value={line.dateEcheance}
                                                                        disabled={!canManageFees}
                                                                        onChange={(event) => setEditingLine(index, 'dateEcheance', event.target.value)}
                                                                        panelAlign="right"
                                                                    />
                                                                </td>
                                                                <td style={{ width: 110 }}>
                                                                    {line.statut && (
                                                                        <span
                                                                            className={`badge badge-soft-${line.statut === 'Payé' ? 'success' : line.statut === 'Payé partiellement' ? 'warning' : 'secondary'}`}
                                                                        >
                                                                            {line.statut}
                                                                        </span>
                                                                    )}
                                                                </td>
                                                                {canManageFees && (
                                                                    <td className="text-end" style={{ width: 40 }}>
                                                                        <button
                                                                            type="button"
                                                                            className="btn btn-icon btn-sm text-danger"
                                                                            aria-label="Masquer ce frais"
                                                                            title="Masquer ce frais"
                                                                            disabled={hideProcessingId === line.id}
                                                                            onClick={() => removeEditingLine(index)}
                                                                        >
                                                                            <i className={hideProcessingId === line.id ? 'ti ti-loader-2' : 'ti ti-trash'} />
                                                                        </button>
                                                                    </td>
                                                                )}
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colSpan={4} className="text-end fw-semibold">
                                                            Total à payer
                                                        </td>
                                                        <td className="text-end fw-bold">{linesTotal(feesForm.data.fee_lines).toFixed(2)} DH</td>
                                                        <td className="text-end fw-bold">
                                                            {linesResteTotal(feesForm.data.fee_lines).toFixed(2)} DH
                                                        </td>
                                                        <td colSpan={canManageFees ? 3 : 2} />
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    )}
                                    {feesForm.errors.fee_lines && (
                                        <div className="text-danger small mb-2">{feesForm.errors.fee_lines}</div>
                                    )}
                                    {canManageFees && feesForm.processing && (
                                        <div className="text-muted small mb-2">Enregistrement…</div>
                                    )}

                                    {hiddenFees.length > 0 && (
                                        <div className="mt-3">
                                            <button
                                                type="button"
                                                className="btn btn-link p-0 d-flex align-items-center gap-2 text-decoration-none mb-1"
                                                onClick={() => setShowHiddenFees((previous) => !previous)}
                                                aria-expanded={showHiddenFees}
                                                aria-controls="ins-hidden-fees"
                                            >
                                                <i
                                                    className={`ti ${showHiddenFees ? 'ti-chevron-down' : 'ti-chevron-right'}`}
                                                    aria-hidden="true"
                                                />
                                                <h6 className="mb-0">Frais masqués</h6>
                                                <span className="badge badge-soft-secondary">{hiddenFees.length}</span>
                                            </button>
                                            <div
                                                id="ins-hidden-fees"
                                                className="table-responsive"
                                                hidden={!showHiddenFees}
                                            >
                                                <table className="table table-sm align-middle mb-0">
                                                    <thead className="thead-light">
                                                        <tr>
                                                            <th>Frais</th>
                                                            <th className="text-end">Montant</th>
                                                            <th>Échéance</th>
                                                            {canManageFees && <th />}
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {hiddenFees.map((fee) => (
                                                            <tr key={fee.id}>
                                                                <td className="text-muted">{fee.nom}</td>
                                                                <td className="text-end text-muted">{Number(fee.montant).toFixed(2)} DH</td>
                                                                <td className="text-muted">{fee.dateEcheance || '—'}</td>
                                                                {canManageFees && (
                                                                    <td className="text-end" style={{ width: 120 }}>
                                                                        <button
                                                                            type="button"
                                                                            className="btn btn-outline-primary btn-sm"
                                                                            disabled={restoreProcessingId === fee.id}
                                                                            onClick={() => restoreHiddenFee(fee)}
                                                                        >
                                                                            {restoreProcessingId === fee.id ? 'Restauration…' : 'Restaurer'}
                                                                        </button>
                                                                    </td>
                                                                )}
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {editingInscription && (
                                <div className="border-top pt-3">
                                    <h6 className="mb-1">Livres</h6>
                                    {loadingEditingLivres ? (
                                        <p className="text-muted fs-13">Chargement…</p>
                                    ) : editingAvailableLivres.length === 0 && editingLivreIds.length === 0 ? (
                                        <p className="text-muted fs-13">Aucun livre en stock pour ce centre.</p>
                                    ) : (
                                        <>
                                            <MultiSelectField
                                                id="ins-edit-livres"
                                                options={editingAvailableLivres.map((l) => ({
                                                    value: l.id,
                                                    label: `${l.nom} (${l.quantite} en stock)`,
                                                }))}
                                                placeholder="Sélectionner un ou plusieurs livres…"
                                                disabled={!canManageFees}
                                                values={editingLivreIds.map(String)}
                                                onChange={(values) => setEditingLivreIds(values.map(Number))}
                                                error={livresError}
                                            />
                                            {canManageFees && (
                                                <div className="d-flex justify-content-end align-items-center gap-2 mt-1">
                                                    <span className="text-muted fs-13">
                                                        Enregistré aussi avec « Modifier ».
                                                    </span>
                                                    <button
                                                        type="button"
                                                        className="btn btn-outline-primary btn-sm"
                                                        disabled={livresProcessing}
                                                        onClick={() => saveEditingLivres()}
                                                    >
                                                        {livresProcessing ? 'Enregistrement…' : 'Enregistrer les livres'}
                                                    </button>
                                                </div>
                                            )}
                                        </>
                                    )}
                                </div>
                            )}
                        </div>
                    )}

                    {activeTab === 'contact' && !editingInscription && form.data.inscription_mode === 'new' && (
                        <div className="row">
                            <div className="col-md-4">
                                <FormField
                                    id="ins-email"
                                    label="Email"
                                    type="email"
                                    value={form.data.new_email}
                                    onChange={(event) => form.setData('new_email', event.target.value)}
                                    error={form.errors.new_email}
                                    placeholder="ex : nom@domaine.com"
                                />
                            </div>
                            <div className="col-md-4">
                                <PhoneField
                                    id="ins-tel"
                                    label="Téléphone"
                                    countryIso={form.data.phone_pays}
                                    national={form.data.new_telephone}
                                    onCountryChange={(iso) => form.setData('phone_pays', iso)}
                                    onNationalChange={(value) => form.setData('new_telephone', value)}
                                    error={form.errors.new_telephone}
                                />
                            </div>
                            <div className="col-md-4">
                                <PhoneField
                                    id="ins-wa"
                                    label="WhatsApp"
                                    countryIso={form.data.phone_pays}
                                    national={form.data.new_whatsapp}
                                    onCountryChange={(iso) => form.setData('phone_pays', iso)}
                                    onNationalChange={(value) => form.setData('new_whatsapp', value)}
                                    error={form.errors.new_whatsapp}
                                />
                            </div>
                            <div className="col-md-4">
                                <FormField
                                    id="ins-adresse"
                                    label="Adresse"
                                    value={form.data.new_adresse}
                                    onChange={(event) => form.setData('new_adresse', event.target.value)}
                                    error={form.errors.new_adresse}
                                    placeholder="ex : 7 rue des fleurs"
                                />
                            </div>
                        </div>
                    )}

                    {activeTab === 'parent' && !editingInscription && form.data.inscription_mode === 'new' && (
                        <div className="row">
                            <div className="col-md-4">
                                <SelectField
                                    id="ins-prelation"
                                    label="Catégorie"
                                    options={parentRelationOptions}
                                    placeholder="Choisir la relation"
                                    value={form.data.new_parent_relation}
                                    onChange={(event) => form.setData('new_parent_relation', event.target.value)}
                                    error={form.errors.new_parent_relation}
                                />
                            </div>
                            <div className="col-md-4">
                                <FormField
                                    id="ins-pnom"
                                    label="Nom du parent"
                                    value={form.data.new_parent_nom}
                                    onChange={(event) => form.setData('new_parent_nom', event.target.value)}
                                    error={form.errors.new_parent_nom}
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
                                                    name="ins-psexe"
                                                    id={`ins-psexe-${sexe}`}
                                                    value={sexe}
                                                    checked={form.data.new_parent_sexe === sexe}
                                                    onChange={() => form.setData('new_parent_sexe', sexe)}
                                                    autoComplete="off"
                                                />
                                                <label
                                                    className="btn btn-outline-primary d-inline-flex align-items-center justify-content-center px-4"
                                                    htmlFor={`ins-psexe-${sexe}`}
                                                >
                                                    <i className={`ti ${sexe === 'Homme' ? 'ti-man' : 'ti-woman'} me-1`} />
                                                    {sexe === 'Homme' ? 'Masculin' : 'Féminin'}
                                                </label>
                                            </div>
                                        ))}
                                    </div>
                                    {form.errors.new_parent_sexe && <div className="text-danger fs-12 mt-1">{form.errors.new_parent_sexe}</div>}
                                </div>
                            </div>
                            <div className="col-md-4">
                                <FormField
                                    id="ins-pcin"
                                    label="CIN"
                                    value={form.data.new_parent_cin}
                                    onChange={(event) => form.setData('new_parent_cin', event.target.value)}
                                    error={form.errors.new_parent_cin}
                                    placeholder="ex : AB123456"
                                />
                            </div>
                            <div className="col-md-4">
                                <PhoneField
                                    id="ins-ptel"
                                    label="Téléphone du parent"
                                    countryIso={form.data.phone_pays}
                                    national={form.data.new_parent_telephone}
                                    onCountryChange={(iso) => form.setData('phone_pays', iso)}
                                    onNationalChange={(value) => form.setData('new_parent_telephone', value)}
                                    error={form.errors.new_parent_telephone}
                                />
                            </div>
                            <div className="col-md-4">
                                <PhoneField
                                    id="ins-pwa"
                                    label="WhatsApp du parent"
                                    countryIso={form.data.phone_pays}
                                    national={form.data.new_parent_whatsapp}
                                    onCountryChange={(iso) => form.setData('phone_pays', iso)}
                                    onNationalChange={(value) => form.setData('new_parent_whatsapp', value)}
                                    error={form.errors.new_parent_whatsapp}
                                />
                            </div>
                        </div>
                    )}

                    {activeTab === 'autre' && (
                        <TextareaField
                            id="ins-note"
                            label="Note"
                            rows={3}
                            value={form.data.note}
                            onChange={(event) => form.setData('note', event.target.value)}
                            error={form.errors.note}
                            placeholder="Notes complémentaires sur cette inscription"
                        />
                    )}

                    <div className="d-flex justify-content-end gap-2 mt-4">
                        <FormActions onCancel={closeModal} processing={form.processing} />
                    </div>
                </form>
            </Modal>

            <Modal
                show={suiviTarget !== null}
                title={`Suivi des paiements : ${suiviTarget?.reference ?? ''}`}
                size="xl"
                onClose={() => setSuiviTarget(null)}
            >
                {(() => {
                    const all = suiviFees ?? [];
                    const visible = suiviEnCoursOnly
                        ? all.filter((line) => lineFinal(line) - (parseFloat(line.paye ?? '0') || 0) > 0)
                        : all;
                    const total = visible.reduce((sum, line) => sum + lineFinal(line), 0);
                    const paye = visible.reduce((sum, line) => sum + (parseFloat(line.paye ?? '0') || 0), 0);
                    const reste = Math.max(0, total - paye);

                    return (
                        <>
                            <div className="form-check mb-3">
                                <input
                                    type="checkbox"
                                    className="form-check-input"
                                    id="suivi-en-cours"
                                    checked={suiviEnCoursOnly}
                                    onChange={(event) => setSuiviEnCoursOnly(event.target.checked)}
                                />
                                <label className="form-check-label fw-semibold" htmlFor="suivi-en-cours">
                                    Paiements en cours
                                </label>
                            </div>

                            {suiviFees === null ? (
                                <div className="text-center py-4">
                                    <div className="spinner-border spinner-border-sm text-primary" role="status" />
                                </div>
                            ) : visible.length === 0 ? (
                                <div className="alert alert-info d-flex align-items-center" role="alert">
                                    <i className="ti ti-alert-circle me-2 fs-18" />
                                    Aucun élément n'a été trouvé.
                                </div>
                            ) : (
                                <>
                                    <div className="row g-3 mb-3">
                                        <div className="col-md-4">
                                            <label className="form-label fw-semibold">Montant total</label>
                                            <input type="text" className="form-control" value={fmtDh(total)} readOnly />
                                        </div>
                                        <div className="col-md-4">
                                            <label className="form-label fw-semibold">Montant total de paiement</label>
                                            <input type="text" className="form-control" value={fmtDh(paye)} readOnly />
                                        </div>
                                        <div className="col-md-4">
                                            <label className="form-label fw-semibold">Reste à payer Total</label>
                                            <input type="text" className="form-control" value={fmtDh(reste)} readOnly />
                                        </div>
                                    </div>

                                    <div className="table-responsive">
                                        <table className="table table-hover mb-0">
                                            <thead className="thead-light">
                                                <tr>
                                                    <th>Frais</th>
                                                    <th>Date d'échéance</th>
                                                    <th className="text-end">Montant total</th>
                                                    <th className="text-end">Montant de paiement</th>
                                                    <th className="text-end">Reste à payer</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {visible.map((line, index) => {
                                                    const final = lineFinal(line);
                                                    const linePaye = parseFloat(line.paye ?? '0') || 0;
                                                    const lineReste = Math.max(0, final - linePaye);

                                                    return (
                                                        <tr key={line.id ?? index}>
                                                            <td className="text-wrap-cell">{line.nom}</td>
                                                            <td>{line.dateEcheance ? formatFr(line.dateEcheance) : '—'}</td>
                                                            <td className="text-end">{fmtDh(final)} DH</td>
                                                            <td className="text-end">{fmtDh(linePaye)} DH</td>
                                                            <td className="text-end">
                                                                {lineReste <= 0 ? (
                                                                    <StatusBadge label="Payé" variant="success" />
                                                                ) : (
                                                                    <span className="fw-semibold text-danger">{fmtDh(lineReste)} DH</span>
                                                                )}
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>
                                    <p className="text-muted mt-2 mb-0">{visible.length} total</p>
                                </>
                            )}

                            <div className="d-flex justify-content-end mt-4">
                                <button type="button" className="btn btn-outline-secondary" onClick={() => setSuiviTarget(null)}>
                                    Fermer
                                </button>
                            </div>
                        </>
                    );
                })()}
            </Modal>

            {/*
              * Post-creation step: the inscription is already saved (this is a
              * plain yes/no, not a second half of the create form) — declining
              * simply dismisses it. Saying yes opens the payment modal below,
              * already scoped to the new registration, so the cashier never has
              * to walk to Encaissements and search the student again.
              */}
            <Modal
                show={paiementPrompt !== null}
                title="Ajouter un paiement ?"
                onClose={() => setPaiementPrompt(null)}
                footer={
                    <div className="d-flex justify-content-end gap-2">
                        <button type="button" className="btn btn-outline-secondary" onClick={() => setPaiementPrompt(null)}>
                            Non
                        </button>
                        <button
                            type="button"
                            className="btn btn-primary"
                            onClick={() => paiementPrompt && openPaiement(paiementPrompt)}
                        >
                            Oui, ajouter un paiement
                        </button>
                    </div>
                }
            >
                <p className="mb-0">
                    L&apos;inscription <span className="fw-semibold">{paiementPrompt?.reference}</span> a été créée.
                    Voulez-vous enregistrer un paiement maintenant ?
                </p>
            </Modal>

            {/*
              * « Ajouter un paiement » — the Encaissements create modal without
              * its student/inscription cascade (both are already known here).
              * It posts to the SAME encaissements.store endpoint with the same
              * payment_lines payload, so nothing about the money path is
              * duplicated: the till routing, the per-fee remaining-balance
              * re-check under lock and the active-context guard all run there.
              */}
            <Modal
                show={paiementTarget !== null}
                title={`Ajouter un paiement : ${paiementTarget?.reference ?? ''}`}
                size="xl"
                onClose={closePaiement}
                processing={paiementForm.processing}
                footer={
                    <FormActions
                        form="inscription-paiement-form"
                        onCancel={closePaiement}
                        processing={paiementForm.processing}
                        submitLabel="Valider"
                        cancelLabel="Fermer"
                    />
                }
            >
                <form id="inscription-paiement-form" onSubmit={submitPaiement}>
                    <div className="mb-3">
                        <label className="form-label fw-semibold" htmlFor="ins-paiement-student">
                            Étudiant <span className="text-danger">*</span>
                        </label>
                        <input
                            id="ins-paiement-student"
                            type="text"
                            className="form-control"
                            value={paiementTarget?.student ?? '—'}
                            disabled
                        />
                    </div>

                    <div className="form-check mb-3">
                        <input
                            type="checkbox"
                            className="form-check-input"
                            id="ins-paiement-en-cours"
                            checked={paiementEnCoursOnly}
                            onChange={(event) => {
                                setPaiementEnCoursOnly(event.target.checked);
                                setPaiementLinesPage(1);
                            }}
                        />
                        <label className="form-check-label fw-semibold" htmlFor="ins-paiement-en-cours">
                            Paiements en cours
                        </label>
                        <div className="form-text">
                            Coché : uniquement les frais dont l&apos;échéance tombe ce mois-ci. Décoché : tous les frais non soldés.
                        </div>
                    </div>

                    {paiementFees === null ? (
                        <div className="text-center py-4">
                            <div className="spinner-border spinner-border-sm text-primary" role="status" />
                        </div>
                    ) : (() => {
                        // Filtering hides rows, it never rebuilds payment_lines:
                        // each row keeps its real index in the submitted array,
                        // so an amount typed before ticking the box is still sent
                        // with the right fee and the server's per-row errors
                        // (payment_lines.<i>.*) still land on the right line.
                        const rows = paiementForm.data.payment_lines
                            .map((line, index) => ({ line, index }))
                            .filter(({ line }) => !paiementEnCoursOnly || echeanceCeMois(line.dateEcheance));
                        const pageRows = rows.slice(
                            (paiementLinesPage - 1) * PAIEMENT_LINES_PER_PAGE,
                            paiementLinesPage * PAIEMENT_LINES_PER_PAGE,
                        );

                        if (rows.length === 0) {
                            return (
                                <div className="alert alert-success d-flex align-items-center" role="alert">
                                    <i className="ti ti-check me-2 fs-18" />
                                    {paiementEnCoursOnly
                                        ? "Aucun frais à échéance ce mois-ci. Décochez « Paiements en cours » pour voir tous les frais non soldés."
                                        : 'Pas de factures à payer.'}
                                </div>
                            );
                        }

                        return (
                            <>
                                <div className="table-responsive mb-3">
                                    <table className="table table-bordered align-middle mb-0">
                                        <thead className="table-light">
                                            <tr>
                                                <th>Frais</th>
                                                <th>Date d&apos;échéance</th>
                                                <th className="text-end">Montant</th>
                                                <th className="text-end">Reste à payer</th>
                                                <th style={{ minWidth: 140 }}>Montant de paiement</th>
                                                <th style={{ minWidth: 150 }}>Méthode</th>
                                                <th style={{ minWidth: 160 }}>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {pageRows.map(({ line, index }) => {
                                                const rowErrors = paiementForm.errors as Record<string, string | undefined>;
                                                const montantError = rowErrors[`payment_lines.${index}.montant`];
                                                const dateError = rowErrors[`payment_lines.${index}.date_paiement`];
                                                const chequeError = rowErrors[`payment_lines.${index}.cheque_id`];
                                                const isCheque = line.methode === METHODE_CHEQUE;

                                                return (
                                                    <Fragment key={line.feeId}>
                                                        <tr>
                                                            <td className="fw-medium text-wrap-cell">{line.nom}</td>
                                                            <td>{line.dateEcheance ? formatFr(line.dateEcheance) : '—'}</td>
                                                            <td className="text-end">{Number(line.montantInitial).toFixed(2)} DH</td>
                                                            <td className="text-end">{Number(line.reste).toFixed(2)} DH</td>
                                                            <td>
                                                                <div className="input-group input-group-sm">
                                                                    <input
                                                                        id={`ins-pl-montant-${index}`}
                                                                        type="number"
                                                                        step="0.01"
                                                                        min="0"
                                                                        max={line.reste}
                                                                        placeholder="0"
                                                                        className={`form-control${montantError ? ' is-invalid' : ''}`}
                                                                        value={line.montant}
                                                                        onChange={(event) =>
                                                                            setPaiementLine(index, { montant: event.target.value })
                                                                        }
                                                                        aria-invalid={montantError ? true : undefined}
                                                                    />
                                                                    <span className="input-group-text">DH</span>
                                                                </div>
                                                                {montantError && <div className="text-danger fs-12 mt-1">{montantError}</div>}
                                                            </td>
                                                            <td>
                                                                <SelectField
                                                                    id={`ins-pl-methode-${index}`}
                                                                    options={methodePaiementOptions}
                                                                    value={line.methode}
                                                                    onChange={(event) =>
                                                                        setPaiementLine(index, {
                                                                            methode: event.target.value,
                                                                            chequeId: event.target.value === METHODE_CHEQUE ? line.chequeId : '',
                                                                        })
                                                                    }
                                                                />
                                                            </td>
                                                            <td>
                                                                <DateField
                                                                    id={`ins-pl-date-${index}`}
                                                                    value={line.datePaiement}
                                                                    onChange={(event) =>
                                                                        setPaiementLine(index, { datePaiement: event.target.value })
                                                                    }
                                                                    error={dateError}
                                                                    panelAlign="right"
                                                                />
                                                            </td>
                                                        </tr>
                                                        {isCheque && (
                                                            <tr>
                                                                <td colSpan={7} className="bg-light-subtle">
                                                                    <SelectField
                                                                        id={`ins-pl-cheque-${index}`}
                                                                        label="Chèque enregistré"
                                                                        required
                                                                        options={paiementCheques.map((cheque) => ({
                                                                            value: cheque.id,
                                                                            label: `${cheque.numeroCheque}${cheque.banque ? ` — ${cheque.banque}` : ''} (reste ${Number(cheque.reste).toFixed(2)} DH)`,
                                                                        }))}
                                                                        placeholder={
                                                                            paiementCheques.length === 0
                                                                                ? "Aucun chèque enregistré pour cet étudiant — ajoutez-en un dans Chèques"
                                                                                : 'Choisir un chèque'
                                                                        }
                                                                        disabled={paiementCheques.length === 0}
                                                                        value={line.chequeId}
                                                                        onChange={(event) =>
                                                                            setPaiementLine(index, {
                                                                                chequeId: event.target.value ? Number(event.target.value) : '',
                                                                            })
                                                                        }
                                                                        error={chequeError}
                                                                    />
                                                                </td>
                                                            </tr>
                                                        )}
                                                    </Fragment>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>

                                <LocalPagination
                                    page={paiementLinesPage}
                                    pageCount={Math.ceil(rows.length / PAIEMENT_LINES_PER_PAGE)}
                                    onPageChange={setPaiementLinesPage}
                                    total={rows.length}
                                    perPage={PAIEMENT_LINES_PER_PAGE}
                                />

                                {paiementForm.errors.payment_lines && (
                                    <div className="text-danger small mb-3">{paiementForm.errors.payment_lines}</div>
                                )}
                            </>
                        );
                    })()}

                    {/*
                     * No Caisse field, exactly like the Encaissements modal: the
                     * account is derived server-side from each row's méthode
                     * (CaisseResolver) — never chosen client-side, for any role.
                     */}
                    <TextareaField
                        id="ins-paiement-note"
                        label="Note"
                        value={paiementForm.data.note}
                        onChange={(event) => paiementForm.setData('note', event.target.value)}
                        error={paiementForm.errors.note}
                    />
                </form>
            </Modal>

            <ConfirmDialog
                show={deleteTarget !== null}
                title="Supprimer cette inscription"
                recordLabel={deleteTarget?.reference ?? ''}
                message="Voulez-vous vraiment supprimer cette inscription ?"
                error={deleteError}
                processing={deleteProcessing}
                onConfirm={handleDelete}
                onCancel={() => {
                    setDeleteTarget(null);
                    setDeleteError(undefined);
                }}
            />

            <ConfirmDialog
                show={statutTarget !== null}
                title="Réactiver l'inscription"
                recordLabel={statutTarget?.inscription.reference ?? ''}
                message="Voulez-vous vraiment réactiver cette inscription ?"
                icon="ti-refresh"
                variant="primary"
                confirmLabel="Oui, réactiver"
                processingLabel="Réactivation…"
                error={statutError}
                processing={statutProcessing}
                onConfirm={handleStatutChange}
                onCancel={() => {
                    setStatutTarget(null);
                    setStatutError(undefined);
                }}
            />

            <Modal
                show={cancelTarget !== null}
                title="Annuler l&rsquo;inscription"
                onClose={closeCancel}
                processing={cancelForm.processing}
                size="lg"
            >
                {cancelTarget && (
                    <form onSubmit={submitCancel} onKeyDown={blockImplicitSubmit}>
                        <div className="alert alert-info d-flex align-items-start gap-2">
                            <i className="ti ti-alert-circle fs-20" />
                            <span>
                                Si vous confirmez l&rsquo;opération, les types de frais non payés sélectionnés
                                ci-dessous seront automatiquement supprimés. Les paiements déjà encaissés ne
                                sont jamais supprimés : ils restent disponibles comme avance pour l&rsquo;étudiant.
                            </span>
                        </div>

                        <div className="row g-3">
                            <div className="col-md-6">
                                <label className="form-label">Référence du registre</label>
                                <input type="text" className="form-control" value={cancelTarget.reference} disabled readOnly />
                            </div>
                            <div className="col-md-6">
                                <label className="form-label">Étudiant</label>
                                <input type="text" className="form-control" value={cancelTarget.student ?? ''} disabled readOnly />
                            </div>

                            <div className="col-md-6">
                                <label className="form-label">Statut actuel</label>
                                <input type="text" className="form-control" value={cancelTarget.statut} disabled readOnly />
                            </div>
                            <div className="col-md-6">
                                <SelectField
                                    id="cancel-motif"
                                    label="Raison d&rsquo;annulation"
                                    required
                                    value={cancelForm.data.motif_annulation}
                                    onChange={(event) => cancelForm.setData('motif_annulation', event.target.value)}
                                    error={cancelForm.errors.motif_annulation}
                                    placeholder="Choisir un élément"
                                    options={motifsAnnulation.map((nom) => ({ value: nom, label: nom }))}
                                />
                            </div>

                            <div className="col-md-6">
                                <DateField
                                    id="cancel-date-fin"
                                    label="Date de fin"
                                    required
                                    value={cancelForm.data.date_fin}
                                    onChange={(event) => cancelForm.setData('date_fin', event.target.value)}
                                    error={cancelForm.errors.date_fin}
                                />
                            </div>
                            <div className="col-md-6">
                                <label className="form-label">Note</label>
                                <textarea
                                    className="form-control"
                                    rows={3}
                                    value={cancelForm.data.note}
                                    onChange={(event) => cancelForm.setData('note', event.target.value)}
                                />
                            </div>

                            <div className="col-12">
                                <div className="form-check">
                                    <input
                                        type="radio"
                                        className="form-check-input"
                                        id="cancel-scope-all"
                                        name="cancel-scope"
                                        checked={cancelForm.data.unpaid_fees_scope === 'all'}
                                        onChange={() => cancelForm.setData('unpaid_fees_scope', 'all')}
                                    />
                                    <label className="form-check-label" htmlFor="cancel-scope-all">
                                        Supprimer tous les types de frais Non payés
                                    </label>
                                </div>
                                <div className="form-check">
                                    <input
                                        type="radio"
                                        className="form-check-input"
                                        id="cancel-scope-overdue"
                                        name="cancel-scope"
                                        checked={cancelForm.data.unpaid_fees_scope === 'overdue_only'}
                                        onChange={() => cancelForm.setData('unpaid_fees_scope', 'overdue_only')}
                                    />
                                    <label className="form-check-label" htmlFor="cancel-scope-overdue">
                                        Supprimer tous les types de frais Non payés ayant une date supérieure à
                                        la date de fin
                                        {cancelForm.data.date_fin ? ' : ' + formatFr(cancelForm.data.date_fin) : ''}
                                    </label>
                                </div>
                                <div className="form-check">
                                    <input
                                        type="radio"
                                        className="form-check-input"
                                        id="cancel-scope-none"
                                        name="cancel-scope"
                                        checked={cancelForm.data.unpaid_fees_scope === ''}
                                        onChange={() => cancelForm.setData('unpaid_fees_scope', '')}
                                    />
                                    <label className="form-check-label" htmlFor="cancel-scope-none">
                                        Ne rien supprimer
                                    </label>
                                </div>
                                {cancelForm.errors.unpaid_fees_scope && (
                                    <div className="text-danger fs-13 mt-1">{cancelForm.errors.unpaid_fees_scope}</div>
                                )}
                            </div>
                        </div>

                        <div className="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" className="btn btn-outline-secondary" onClick={closeCancel}>
                                Fermer
                            </button>
                            <button type="submit" className="btn btn-danger" disabled={cancelForm.processing}>
                                {cancelForm.processing ? 'Annulation…' : 'Confirmer l’annulation'}
                            </button>
                        </div>
                    </form>
                )}
            </Modal>

            <Modal
                show={changeGroupTarget !== null}
                title={`Changement de groupe : ${changeGroupTarget?.student ?? ''}`}
                onClose={closeChangeGroup}
                processing={changeGroupForm.processing}
                size="lg"
            >
                {changeGroupTarget && (
                    <form onSubmit={submitChangeGroup} onKeyDown={blockImplicitSubmit}>
                        <div className="alert alert-warning">
                            Cette action archivera l'inscription actuelle et créera une nouvelle inscription dans le
                            nouveau groupe sélectionné.
                        </div>

                        <h6 className="mb-2">Groupe actuel</h6>
                        <div className="row mb-3">
                            <div className="col-md-6">
                                <FormField id="cg-groupe-actuel" label="Groupe" value={changeGroupTarget.groupe ?? '—'} readOnly disabled />
                            </div>
                            <div className="col-md-6">
                                <FormField
                                    id="cg-montant-total"
                                    label="Montant total"
                                    value={
                                        changeGroupTarget.montantTotal !== null
                                            ? `${Number(changeGroupTarget.montantTotal).toFixed(2)} MAD`
                                            : '—'
                                    }
                                    readOnly
                                    disabled
                                />
                            </div>
                            <div className="col-md-6">
                                <DateField
                                    id="cg-date-fin"
                                    label="Date de fin"
                                    required
                                    value={changeGroupForm.data.date_fin}
                                    onChange={(event) => changeGroupForm.setData('date_fin', event.target.value)}
                                    error={changeGroupForm.errors.date_fin}
                                />
                            </div>
                            <div className="col-12">
                                <TextareaField
                                    id="cg-note"
                                    label="Note"
                                    value={changeGroupForm.data.note}
                                    onChange={(event) => changeGroupForm.setData('note', event.target.value)}
                                    error={changeGroupForm.errors.note}
                                />
                            </div>
                            <div className="col-12">
                                <button
                                    type="button"
                                    className="btn btn-link p-0"
                                    onClick={() => setShowFeesDetails((value) => !value)}
                                >
                                    {showFeesDetails ? 'Moins de détails' : 'Plus de détails'}
                                </button>
                                {showFeesDetails && (
                                    <div className="mt-2">
                                        <div className="form-check">
                                            <input
                                                type="radio"
                                                className="form-check-input"
                                                id="cg-scope-overdue"
                                                name="cg-scope"
                                                checked={changeGroupForm.data.unpaid_fees_scope === 'overdue_only'}
                                                onChange={() => changeGroupForm.setData('unpaid_fees_scope', 'overdue_only')}
                                            />
                                            <label className="form-check-label" htmlFor="cg-scope-overdue">
                                                Supprimer tous les frais Non payés ayant une date supérieure à la date de fin
                                            </label>
                                        </div>
                                        <div className="form-check">
                                            <input
                                                type="radio"
                                                className="form-check-input"
                                                id="cg-scope-all"
                                                name="cg-scope"
                                                checked={changeGroupForm.data.unpaid_fees_scope === 'all'}
                                                onChange={() => changeGroupForm.setData('unpaid_fees_scope', 'all')}
                                            />
                                            <label className="form-check-label" htmlFor="cg-scope-all">
                                                Supprimer tous les frais Non payés
                                            </label>
                                        </div>
                                        <div className="form-check">
                                            <input
                                                type="radio"
                                                className="form-check-input"
                                                id="cg-scope-none"
                                                name="cg-scope"
                                                checked={changeGroupForm.data.unpaid_fees_scope === ''}
                                                onChange={() => changeGroupForm.setData('unpaid_fees_scope', '')}
                                            />
                                            <label className="form-check-label" htmlFor="cg-scope-none">
                                                Ne rien supprimer
                                            </label>
                                        </div>
                                    </div>
                                )}
                            </div>
                            <div className="col-12 mt-3">
                                <div className="form-check">
                                    <input
                                        type="checkbox"
                                        className="form-check-input"
                                        id="cg-transfer-fees"
                                        checked={showTransferFees}
                                        onChange={toggleShowTransferFees}
                                    />
                                    <label className="form-check-label" htmlFor="cg-transfer-fees">
                                        Transférer des frais payés vers la nouvelle inscription
                                    </label>
                                </div>
                                {showTransferFees && (
                                    <div className="mt-2 ms-4">
                                        {loadingTransferableFees ? (
                                            <p className="text-muted fs-13 mb-0">Chargement…</p>
                                        ) : transferableFees.length === 0 ? (
                                            <p className="text-muted fs-13 mb-0">Aucun frais payé sur cette inscription.</p>
                                        ) : (
                                            <>
                                                <p className="text-muted fs-13 mb-2">
                                                    Les frais cochés (et leurs paiements) seront déplacés vers la nouvelle inscription
                                                    et retirés de l'ancienne. Le frais équivalent du nouveau groupe ne sera pas facturé
                                                    une seconde fois.
                                                </p>
                                                {transferableFees.map((fee) => (
                                                    <div className="form-check" key={fee.id}>
                                                        <input
                                                            type="checkbox"
                                                            className="form-check-input"
                                                            id={`cg-transfer-fee-${fee.id}`}
                                                            checked={changeGroupForm.data.transfer_fee_ids.includes(fee.id as number)}
                                                            onChange={() => toggleTransferFee(fee.id as number)}
                                                        />
                                                        <label className="form-check-label" htmlFor={`cg-transfer-fee-${fee.id}`}>
                                                            {fee.nom} — payé {parseFloat(fee.paye ?? '0').toFixed(2)} DH
                                                            {fee.statut !== 'Payé' && (
                                                                <span className="badge badge-soft-warning ms-2">{fee.statut}</span>
                                                            )}
                                                        </label>
                                                    </div>
                                                ))}
                                            </>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>

                        <h6 className="mb-2">Le nouveau groupe</h6>
                        <div className="row mb-3">
                            <div className="col-md-6">
                                <DateField
                                    id="cg-date-debut"
                                    label="Date de début"
                                    required
                                    value={changeGroupForm.data.date_debut}
                                    onChange={(event) => changeGroupForm.setData('date_debut', event.target.value)}
                                    error={changeGroupForm.errors.date_debut}
                                />
                            </div>
                            <div className="col-md-6">
                                <SelectField
                                    id="cg-groupe"
                                    label="Groupe"
                                    required
                                    options={groupOptions.filter((g) => g.value !== changeGroupTarget.groupId)}
                                    placeholder="Choisir une formation"
                                    value={changeGroupForm.data.new_group_id}
                                    onChange={(event) =>
                                        changeGroupForm.setData('new_group_id', event.target.value ? Number(event.target.value) : '')
                                    }
                                    error={changeGroupForm.errors.new_group_id}
                                />
                            </div>
                        </div>

                        <div className="d-flex justify-content-end gap-2 mt-4">
                            <FormActions onCancel={closeChangeGroup} processing={changeGroupForm.processing} submitLabel="Confirmer" />
                        </div>
                    </form>
                )}
            </Modal>
        </BackofficeLayout>
    );
}

interface ClientFeesPaginationProps {
    total: number;
    perPage: number;
    page: number;
    onPageChange: (page: number) => void;
}

/**
 * Lightweight client-side pager for the "Frais disponibles" table — the
 * lines live entirely in form's in-memory state (no server round trip until
 * submit), so this can't reuse the server-driven <Pagination> component
 * (which navigates via router.get against a Laravel paginator). Same
 * Bootstrap `.pagination` markup/prev-next-jump styling.
 */
function ClientFeesPagination({ total, perPage, page, onPageChange }: ClientFeesPaginationProps) {
    const lastPage = Math.max(1, Math.ceil(total / perPage));

    if (lastPage <= 1) {
        return null;
    }

    const from = (page - 1) * perPage + 1;
    const to = Math.min(total, page * perPage);

    return (
        <div className="gls-pagination-footer d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
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
