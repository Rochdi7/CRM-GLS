import { router, useForm } from '@inertiajs/react';
import { Fragment, useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import FilterTextInput from '@/Components/Tables/FilterTextInput';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
import SearchInput from '@/Components/Tables/SearchInput';
import Pagination from '@/Components/Tables/Pagination';
import LocalPagination from '@/Components/Tables/LocalPagination';
import RowActions, { RowActionDivider, RowActionItem } from '@/Components/Tables/RowActions';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import SelectField from '@/Components/Forms/SelectField';
import DateField from '@/Components/Forms/DateField';
import FormField from '@/Components/Forms/FormField';
import TextareaField from '@/Components/Forms/TextareaField';
import FormActions from '@/Components/Forms/FormActions';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { t } from '@/Lib/i18n';
import type { EncaissementRow, EncaissementsPageProps, InscriptionPaymentRow, PaymentLine, SelectOption, StudentChequeOption, UnpaidFee } from '@/Types';

interface CreateFormState {
    student_id: number | '';
    inscription_id: number | '';
    date_paiement: string;
    note: string;
    payment_lines: PaymentLine[];
}

interface EditFormState {
    methode: string;
    date_paiement: string;
    numero_cheque: string;
    banque: string;
    date_echeance_cheque: string;
    note: string;
}

/**
 * "Convertir en avance" — no montant input: the amounts come from existing
 * payments of the selected inscription; ticking rows detaches them from
 * their frais server-side (avances.convert).
 */
interface AvanceFormState {
    student_id: number | '';
    inscription_id: number | '';
    encaissement_ids: number[];
}

interface ApplyAvanceFormState {
    inscription_id: number | '';
    fee_id: number | '';
    montant: string;
}

/** Local list — the controller sends no perPageOptions prop (default perPage is 15, unclamped server-side). */
const PER_PAGE_OPTIONS = [15, 25, 50, 100];

function emptyCreateForm(): CreateFormState {
    return {
        student_id: '',
        inscription_id: '',
        date_paiement: new Date().toISOString().slice(0, 10),
        note: '',
        payment_lines: [],
    };
}

const SOLDE_OPTIONS: SelectOption[] = [
    { value: 'restant', label: 'Avec reste à utiliser' },
    { value: 'epuise', label: 'Épuisées (entièrement utilisées)' },
    { value: 'tous', label: 'Toutes' },
];

function emptyAvanceForm(): AvanceFormState {
    return {
        student_id: '',
        inscription_id: '',
        encaissement_ids: [],
    };
}

/**
 * Replaces App\Livewire\Backoffice\Encaissements\EncaissementsIndex — same
 * cascading student→inscription→fee-lines create form (one row per unpaid
 * fee, no till picker at all since the acting employee's own till is always
 * used server-side), same edit-mode freeze on montant/caisse/student/fee
 * (only méthode/date/chèque/note are live inputs on edit). Every discount/
 * remaining-balance figure shown here is display-only — the server
 * independently recomputes and re-validates (including the per-row
 * max:reste cap and the fee->inscription_id ownership check) on save.
 *
 * A Chèque-method row on CREATE has no manual numéro/banque/échéance entry
 * — it must reference one of the student's tracked chèques (Chèques
 * module, /backoffice/cheques), picked from a dropdown that expands
 * directly under that row; numéro/banque/échéance are always read off that
 * Cheque record server-side (EncaissementController@store).
 */
export default function EncaissementsIndex({ encaissements, montantTotal, caisses, students, groups, methodes, banques, filters, can }: EncaissementsPageProps) {
    const isLoading = useInertiaLoading();
    const [deleteTarget, setDeleteTarget] = useState<EncaissementRow | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string | undefined>(undefined);
    const [showModal, setShowModal] = useState(false);
    const [editingRow, setEditingRow] = useState<EncaissementRow | null>(null);
    const [inscriptionOptions, setInscriptionOptions] = useState<SelectOption[]>([]);
    const [loadingInscriptions, setLoadingInscriptions] = useState(false);
    const [loadingFees, setLoadingFees] = useState(false);
    const [feeLinesPage, setFeeLinesPage] = useState(1);
    const FEE_LINES_PER_PAGE = 4;
    const [showAvanceModal, setShowAvanceModal] = useState(false);
    const [avanceInscriptionOptions, setAvanceInscriptionOptions] = useState<SelectOption[]>([]);
    const [avancePayments, setAvancePayments] = useState<InscriptionPaymentRow[]>([]);
    const [loadingAvanceInscriptions, setLoadingAvanceInscriptions] = useState(false);
    const [loadingAvancePayments, setLoadingAvancePayments] = useState(false);
    const [applyTarget, setApplyTarget] = useState<EncaissementRow | null>(null);
    const [applyInscriptionOptions, setApplyInscriptionOptions] = useState<SelectOption[]>([]);
    const [applyFeeOptions, setApplyFeeOptions] = useState<Array<SelectOption & { reste: string }>>([]);
    const [loadingApplyInscriptions, setLoadingApplyInscriptions] = useState(false);
    const [loadingApplyFees, setLoadingApplyFees] = useState(false);
    const [studentCheques, setStudentCheques] = useState<StudentChequeOption[]>([]);
    const [emailTarget, setEmailTarget] = useState<EncaissementRow | null>(null);

    const caisseOptions: SelectOption[] = caisses.map((c) => ({ value: c.id, label: c.nom }));
    const studentOptions: SelectOption[] = students.map((s) => ({ value: s.id, label: s.nom }));
    const groupOptions: SelectOption[] = groups.map((g) => ({ value: g.id, label: g.nom }));
    const methodeOptions: SelectOption[] = methodes.map((m) => ({ value: m, label: m }));

    const createForm = useForm<CreateFormState>(emptyCreateForm());
    const editForm = useForm<EditFormState>({
        methode: '',
        date_paiement: '',
        numero_cheque: '',
        banque: '',
        date_echeance_cheque: '',
        note: '',
    });
    const avanceForm = useForm<AvanceFormState>(emptyAvanceForm());
    const applyForm = useForm<ApplyAvanceFormState>({ inscription_id: '', fee_id: '', montant: '' });
    const emailForm = useForm<{ email: string }>({ email: '' });

    // Partial reload: only the paginated rows, the echoed filters and the
    // total they add up to are recomputed; the option lists
    // (caisses/students/banques — closures server-side) keep their
    // first-visit values.
    //
    // ⚠ `montantTotal` MUST stay in this list: a prop left out of `only` is
    // not re-sent, so the figure collapsed to 0.00 MAD the moment any filter
    // or page changed, while the rows below it were correct (26/08/2026).
    const RELOAD_ONLY = ['encaissements', 'montantTotal', 'filters'];

    function reload(nextFilters: Partial<typeof filters>) {
        const next = { ...filters, ...nextFilters, page: undefined };

        // A cleared date must reach the server as an EXPLICIT empty value.
        // Inertia omits empty strings from the query string, so clearing both
        // dates while no other filter was set produced a bare URL — which the
        // controller reads as a first visit and answers by redirecting with
        // today's date re-injected. Sending a marker keeps "cleared" distinct
        // from "never set" (27/08/2026).
        const withExplicitDates = {
            ...next,
            dateFrom: next.dateFrom === '' ? '-' : next.dateFrom,
            dateTo: next.dateTo === '' ? '-' : next.dateTo,
        };

        router.get('/backoffice/encaissements', withExplicitDates, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: RELOAD_ONLY,
        });
    }

    function openCreate() {
        setEditingRow(null);
        setInscriptionOptions([]);
        setStudentCheques([]);
        setFeeLinesPage(1);
        createForm.clearErrors();
        createForm.setData(emptyCreateForm());
        setShowModal(true);
    }

    function openEdit(row: EncaissementRow) {
        setEditingRow(row);
        editForm.clearErrors();
        editForm.setData({
            methode: row.methode,
            date_paiement: row.datePaiement ?? '',
            numero_cheque: row.numeroCheque ?? '',
            banque: row.banque ?? '',
            date_echeance_cheque: row.dateEcheanceCheque ?? '',
            note: row.note ?? '',
        });
        setShowModal(true);
    }

    function closeModal() {
        setShowModal(false);
        setEditingRow(null);
    }

    /**
     * Hard-deletes a payment (super-admin, or whoever they granted
     * payments.delete). The server reverses caisses.solde in the same
     * transaction and refuses entangled rows, surfacing that as a validation
     * error shown inside this dialog.
     */
    function confirmDeleteEncaissement() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        setDeleteError(undefined);

        router.delete(`/backoffice/encaissements/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteTarget(null);
                setDeleting(false);
            },
            onError: (errors: Record<string, string>) => {
                setDeleteError(errors.encaissement ?? 'Suppression impossible.');
                setDeleting(false);
            },
        });
    }

    // One click sends the receipt straight to the student's email when one
    // is on file (the server queues the mail, so this returns instantly);
    // the address prompt only appears for students with no email.
    function openEmailRecu(row: EncaissementRow) {
        emailForm.clearErrors();
        const studentEmail = row.studentEmail?.trim() ?? '';

        if (studentEmail !== '') {
            // router.post with an explicit payload: useForm's setData is
            // async state, so setData + post in the same tick posts stale data.
            router.post(
                row.recuEmailUrl,
                { email: studentEmail },
                {
                    preserveScroll: true,
                    // A server-side rejection (e.g. malformed stored email)
                    // falls back to the prompt so the cashier can correct it.
                    onError: (errors) => {
                        emailForm.setData('email', studentEmail);
                        emailForm.setError('email', errors.email ?? '');
                        setEmailTarget(row);
                    },
                },
            );
            return;
        }

        emailForm.setData('email', '');
        setEmailTarget(row);
    }

    function closeEmailModal() {
        setEmailTarget(null);
    }

    function submitEmailRecu(event: FormEvent) {
        event.preventDefault();
        if (!emailTarget) {
            return;
        }
        emailForm.post(emailTarget.recuEmailUrl, {
            preserveScroll: true,
            onSuccess: () => closeEmailModal(),
        });
    }

    function openCreateAvance() {
        avanceForm.clearErrors();
        avanceForm.setData(emptyAvanceForm());
        setAvanceInscriptionOptions([]);
        setAvancePayments([]);
        setShowAvanceModal(true);
    }

    function closeAvanceModal() {
        setShowAvanceModal(false);
    }

    async function onAvanceStudentChange(studentId: number | '') {
        avanceForm.setData({ student_id: studentId, inscription_id: '', encaissement_ids: [] });
        setAvanceInscriptionOptions([]);
        setAvancePayments([]);

        if (studentId === '') {
            return;
        }

        setLoadingAvanceInscriptions(true);
        try {
            const response = await fetch(`/backoffice/students/${studentId}/inscriptions-for-payment`);
            const data: { inscriptions: Array<{ id: number; label: string }> } = await response.json();
            setAvanceInscriptionOptions(data.inscriptions.map((i) => ({ value: i.id, label: i.label })));
        } finally {
            setLoadingAvanceInscriptions(false);
        }
    }

    async function onAvanceInscriptionChange(inscriptionId: number | '') {
        avanceForm.setData((previous) => ({ ...previous, inscription_id: inscriptionId, encaissement_ids: [] }));
        setAvancePayments([]);

        if (inscriptionId === '') {
            return;
        }

        setLoadingAvancePayments(true);
        try {
            const response = await fetch(`/backoffice/inscriptions/${inscriptionId}/payments`);
            const data: { payments: InscriptionPaymentRow[] } = await response.json();
            setAvancePayments(data.payments);
        } finally {
            setLoadingAvancePayments(false);
        }
    }

    function toggleAvancePayment(id: number) {
        avanceForm.setData((previous) => ({
            ...previous,
            encaissement_ids: previous.encaissement_ids.includes(id)
                ? previous.encaissement_ids.filter((selected) => selected !== id)
                : [...previous.encaissement_ids, id],
        }));
    }

    const convertiblePayments = avancePayments.filter((p) => !p.rembourse);
    const allAvanceSelected = convertiblePayments.length > 0 && convertiblePayments.every((p) => avanceForm.data.encaissement_ids.includes(p.id));
    const avanceSelectedTotal = avancePayments
        .filter((p) => avanceForm.data.encaissement_ids.includes(p.id))
        .reduce((sum, p) => sum + Number(p.montant), 0);

    function toggleAllAvancePayments() {
        avanceForm.setData((previous) => ({
            ...previous,
            encaissement_ids: allAvanceSelected ? [] : convertiblePayments.map((p) => p.id),
        }));
    }

    function submitAvance(event: FormEvent) {
        event.preventDefault();
        avanceForm.post('/backoffice/avances/convert', {
            preserveScroll: true,
            onSuccess: () => closeAvanceModal(),
        });
    }

    async function openApplyAvance(row: EncaissementRow) {
        setApplyTarget(row);
        applyForm.clearErrors();
        applyForm.setData({
            inscription_id: '',
            fee_id: '',
            montant: '',
        });
        setApplyInscriptionOptions([]);
        setApplyFeeOptions([]);

        if (row.studentId === null) {
            return;
        }

        setLoadingApplyInscriptions(true);
        try {
            const response = await fetch(`/backoffice/students/${row.studentId}/inscriptions-for-payment`);
            const data: { inscriptions: Array<{ id: number; label: string }> } = await response.json();
            setApplyInscriptionOptions(data.inscriptions.map((i) => ({ value: i.id, label: i.label })));
        } finally {
            setLoadingApplyInscriptions(false);
        }
    }

    function closeApplyModal() {
        setApplyTarget(null);
        setApplyInscriptionOptions([]);
        setApplyFeeOptions([]);
    }

    async function onApplyInscriptionChange(inscriptionId: number | '') {
        applyForm.setData((previous) => ({ ...previous, inscription_id: inscriptionId, fee_id: '', montant: '' }));
        setApplyFeeOptions([]);

        if (inscriptionId === '') {
            return;
        }

        setLoadingApplyFees(true);
        try {
            const response = await fetch(`/backoffice/inscriptions/${inscriptionId}/unpaid-fees`);
            const data: { fees: UnpaidFee[] } = await response.json();
            setApplyFeeOptions(data.fees.map((fee) => ({ value: fee.id, label: fee.nom, reste: fee.reste })));
        } finally {
            setLoadingApplyFees(false);
        }
    }

    const selectedApplyFee = applyFeeOptions.find((f) => String(f.value) === String(applyForm.data.fee_id));
    const avanceRestant = applyTarget ? Number(applyTarget.montantRestant ?? applyTarget.montant) : 0;
    const applyMaxMontant = selectedApplyFee ? Math.min(Number(selectedApplyFee.reste), avanceRestant) : avanceRestant;

    function submitApplyAvance(event: FormEvent) {
        event.preventDefault();
        if (!applyTarget) return;
        applyForm.post(`/backoffice/avances/${applyTarget.id}/apply`, {
            preserveScroll: true,
            onSuccess: () => closeApplyModal(),
        });
    }

    async function onStudentChange(studentId: number | '') {
        createForm.setData((previous) => ({
            ...previous,
            student_id: studentId,
            inscription_id: '',
            payment_lines: [],
            cheque_id: '',
        }));
        setInscriptionOptions([]);
        setStudentCheques([]);
        setFeeLinesPage(1);

        if (studentId === '') {
            return;
        }

        setLoadingInscriptions(true);
        try {
            const response = await fetch(`/backoffice/students/${studentId}/inscriptions-for-payment`);
            const data: { inscriptions: Array<{ id: number; label: string }> } = await response.json();
            setInscriptionOptions(data.inscriptions.map((i) => ({ value: i.id, label: i.label })));
        } finally {
            setLoadingInscriptions(false);
        }

        // Tracked chèques of this student still holding value (Chèques
        // module) — the ONLY source for a Chèque-method row's numéro/
        // banque/échéance; there is no manual entry fallback (Ajouter un
        // chèque lives on its own page, /backoffice/cheques).
        try {
            const chequesResponse = await fetch(`/backoffice/students/${studentId}/cheques`, { headers: { Accept: 'application/json' } });
            if (chequesResponse.ok) {
                const chequesData: { cheques: StudentChequeOption[] } = await chequesResponse.json();
                setStudentCheques(chequesData.cheques);
            }
        } catch {
            // Network hiccup — the row's cheque dropdown just stays empty;
            // the user can retry by reselecting the student.
        }
    }

    async function onInscriptionChange(inscriptionId: number | '') {
        createForm.setData((previous) => ({ ...previous, inscription_id: inscriptionId, payment_lines: [] }));
        setFeeLinesPage(1);

        if (inscriptionId === '') {
            return;
        }

        setLoadingFees(true);
        try {
            const response = await fetch(`/backoffice/inscriptions/${inscriptionId}/unpaid-fees`);
            const data: { fees: UnpaidFee[] } = await response.json();
            createForm.setData((previous) => ({
                ...previous,
                payment_lines: data.fees.map((fee) => ({
                    feeId: fee.id,
                    nom: fee.nom,
                    montantInitial: fee.montantInitial,
                    reste: fee.reste,
                    dateEcheance: fee.dateEcheance,
                    montant: '',
                    methode: 'Espèces',
                    datePaiement: previous.date_paiement,
                    chequeId: '',
                })),
            }));
        } finally {
            setLoadingFees(false);
        }
    }

    function setLine(index: number, patch: Partial<PaymentLine>) {
        createForm.setData((previous) => ({
            ...previous,
            payment_lines: previous.payment_lines.map((line, i) => (i === index ? { ...line, ...patch } : line)),
        }));
    }

    function submitCreate(event: FormEvent) {
        event.preventDefault();
        // The rows live in camelCase client state (PaymentLine) but the
        // server validates snake_case (payment_lines.*.fee_id /
        // .date_paiement) — without this mapping every submit failed
        // validation invisibly. Same transform on every create submit, so
        // no cross-submission transform leakage.
        createForm.transform((data) => ({
            ...data,
            payment_lines: data.payment_lines.map((line) => ({
                fee_id: line.feeId,
                montant: line.montant,
                methode: line.methode,
                date_paiement: line.datePaiement,
                cheque_id: line.methode === 'Chèque' ? line.chequeId : null,
            })),
        }));
        createForm.post('/backoffice/encaissements', {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            // A rejected row could be paginated out of view — jump to
            // whichever page holds the first "payment_lines.<i>.*" error so
            // the user isn't left staring at an unrelated page.
            onError: (errors) => {
                const firstErrorKey = Object.keys(errors).find((key) => key.startsWith('payment_lines.'));
                if (!firstErrorKey) return;
                const lineIndex = Number(firstErrorKey.split('.')[1]);
                if (Number.isNaN(lineIndex)) return;
                setFeeLinesPage(Math.floor(lineIndex / FEE_LINES_PER_PAGE) + 1);
            },
        });
    }

    function submitEdit(event: FormEvent) {
        event.preventDefault();
        if (!editingRow) return;
        editForm.put(`/backoffice/encaissements/${editingRow.id}`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }

    return (
        <BackofficeLayout
            title={filters.view === 'avance' ? 'Avances' : 'Encaissements'}
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: filters.view === 'avance' ? 'Avances' : 'Encaissements' },
            ]}
            actions={
                filters.view === 'avance' ? (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreateAvance}>
                        <i className="ti ti-transfer me-2" />
                        Convertir en avance
                    </button>
                ) : (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                        <i className="ti ti-square-rounded-plus me-2" />
                        Enregistrer un paiement
                    </button>
                )
            }
        >
            {/* wimschool-style view tabs — server-side read-only filters on the same list, PLUS one real page link
                (Chèques → the standalone Chèques module) sharing the same tab bar, same cross-link convention as
                GROUPS_TABS (Groupes ↔ Historique). The old "Paiements par chèque" tab button was removed
                (redundant now that the real Chèques module exists) — the view=cheque filter/columns it drove
                are still reachable by URL (?view=cheque), just no longer exposed as a tab. */}
            <ul className="nav nav-tabs p-0 border-bottom rounded-0 mb-4" role="tablist">
                {[
                    { view: '', label: 'Encaissements', icon: 'ti ti-cash-banknote' },
                    { view: 'avance', label: 'Avances', icon: 'ti ti-clock-dollar' },
                ].map((tab) => (
                    <li className="nav-item" key={tab.view} role="presentation">
                        <button
                            type="button"
                            className={`nav-link d-inline-flex align-items-center${filters.view === tab.view ? ' active' : ''}`}
                            aria-current={filters.view === tab.view ? 'page' : undefined}
                            onClick={() =>
                                reload({
                                    view: tab.view,
                                    // Avances carry no date window (the fields are not even
                                    // drawn there), so a value left over from the
                                    // Encaissements tab must be dropped on the way in —
                                    // otherwise it keeps filtering invisibly and hides real
                                    // unspent money.
                                    dateFrom: tab.view === 'avance' ? '' : filters.dateFrom,
                                    dateTo: tab.view === 'avance' ? '' : filters.dateTo,
                                })
                            }
                        >
                            <i className={`${tab.icon} me-2`} aria-hidden="true" />
                            {tab.label}
                        </button>
                    </li>
                ))}
                <li className="nav-item" role="presentation">
                    <a href="/backoffice/cheques" className="nav-link d-inline-flex align-items-center">
                        <i className="ti ti-building-bank me-2" aria-hidden="true" />
                        Chèques
                    </a>
                </li>
            </ul>

            <Card
                title={filters.view === 'avance' ? 'Avances' : 'Encaissements'}
                bodyClassName="p-0 py-3"
            >
                <div className="px-3 pt-2">
                    <div className="row g-3 mb-3">
                        {filters.view === 'cheque' ? (
                            <div className="col-6 col-md-4 col-lg-2">
                                <label className="form-label" htmlFor="enc-f-numero-cheque">
                                    Num Chèque
                                </label>
                                <FilterTextInput
                                    id="enc-f-numero-cheque"
                                    value={filters.numeroChequeFilter}
                                    onChange={(value) => reload({ numeroChequeFilter: value })}
                                    placeholder="ex : P19"
                                />
                            </div>
                        ) : (
                            <div className="col-6 col-md-4 col-lg-2">
                                <label className="form-label" htmlFor="enc-f-reference">
                                    Référence
                                </label>
                                <FilterTextInput
                                    id="enc-f-reference"
                                    value={filters.referenceFilter}
                                    onChange={(value) => reload({ referenceFilter: value })}
                                    placeholder="ex : P19"
                                />
                            </div>
                        )}
                        <div className="col-6 col-md-4 col-lg-2">
                            <label className="form-label" htmlFor="enc-f-student">
                                Étudiant
                            </label>
                            <SelectField
                                id="enc-f-student"
                                options={studentOptions}
                                placeholder="Choisir un étudiant"
                                value={filters.studentFilter}
                                onChange={(event) => reload({ studentFilter: event.target.value })}
                            />
                        </div>
                        <div className="col-6 col-md-4 col-lg-2">
                            <label className="form-label" htmlFor="enc-f-group">
                                Groupe
                            </label>
                            <SelectField
                                id="enc-f-group"
                                options={groupOptions}
                                placeholder="Tous les groupes"
                                value={filters.groupFilter}
                                onChange={(event) => reload({ groupFilter: event.target.value })}
                            />
                        </div>
                        {filters.view === 'cheque' ? (
                            <div className="col-6 col-md-4 col-lg-2">
                                <label className="form-label" htmlFor="enc-f-banque">
                                    Banque
                                </label>
                                <FilterTextInput
                                    id="enc-f-banque"
                                    value={filters.banqueFilter}
                                    onChange={(value) => reload({ banqueFilter: value })}
                                    placeholder="ex : Attijariwafa"
                                />
                            </div>
                        ) : (
                            <>
                                <div className="col-6 col-md-4 col-lg-2">
                                    <label className="form-label" htmlFor="enc-f-caisse">
                                        Caisse
                                    </label>
                                    <SelectField
                                        id="enc-f-caisse"
                                        options={caisseOptions}
                                        placeholder="Toutes les caisses"
                                        value={filters.caisseFilter}
                                        onChange={(event) => reload({ caisseFilter: event.target.value })}
                                    />
                                </div>
                                <div className="col-6 col-md-4 col-lg-2">
                                    <label className="form-label" htmlFor="enc-f-methode">
                                        Méthode
                                    </label>
                                    <SelectField
                                        id="enc-f-methode"
                                        options={methodeOptions}
                                        placeholder="Toutes les méthodes"
                                        value={filters.methodeFilter}
                                        onChange={(event) => reload({ methodeFilter: event.target.value })}
                                    />
                                </div>
                                {/* Avances only: by default the tab lists what still has
                                    money to allocate; « Épuisées » shows the history of
                                    fully used ones. */}
                                {filters.view === 'avance' && (
                                    <div className="col-6 col-md-4 col-lg-2">
                                        <label className="form-label" htmlFor="enc-f-solde">
                                            {t('Balance')}
                                        </label>
                                        <SelectField
                                            id="enc-f-solde"
                                            options={SOLDE_OPTIONS}
                                            value={filters.soldeFilter}
                                            onChange={(event) => reload({ soldeFilter: event.target.value })}
                                        />
                                    </div>
                                )}
                            </>
                        )}
                        {/* No date window on the Avances tab: an avance is money received
                            and not yet allocated, so it stays outstanding until someone
                            applies it — the backend lists the tab in full for exactly that
                            reason (GetEncaissementsList, CLAUDE.md §11 "Deliberate
                            exceptions"). Offering date fields there was misleading: they
                            still filtered, so a leftover value silently hid real unspent
                            money. */}
                        {filters.view !== 'avance' && (
                            <>
                                <div className="col-6 col-md-4 col-lg-2">
                                    <label className="form-label" htmlFor="enc-f-du">
                                        Date de début
                                    </label>
                                    <DateField
                                        id="enc-f-du"
                                        value={filters.dateFrom}
                                        onChange={(event) => reload({ dateFrom: event.target.value })}
                                    />
                                </div>
                                <div className="col-6 col-md-4 col-lg-2">
                                    <label className="form-label" htmlFor="enc-f-au">
                                        Date de fin
                                    </label>
                                    <DateField
                                        id="enc-f-au"
                                        value={filters.dateTo}
                                        onChange={(event) => reload({ dateTo: event.target.value })}
                                    />
                                </div>
                            </>
                        )}
                    </div>
                </div>

                <TableLengthRow
                    perPage={filters.perPage}
                    perPageOptions={PER_PAGE_OPTIONS}
                    onPerPageChange={(perPage) => reload({ perPage })}
                    search={
                        <SearchInput
                            value={filters.search}
                            onSearch={(value) => reload({ search: value })}
                            placeholder="Rechercher"
                        />
                    }
                />

                <p className="fw-medium px-3 mb-3">
                    {/* Avances total what is still AVAILABLE (montant − applied − refunded),
                        so the label states that rather than claiming a plain sum. */}
                    {filters.view === 'avance' ? t('Remaining total') : t('Total amount')} :{' '}
                    {Number(montantTotal ?? 0).toFixed(2)} MAD
                </p>

                {encaissements.data.length === 0 ? (
                    <EmptyState
                        title={filters.view === 'avance' ? 'Aucune avance' : 'Aucun paiement'}
                        icon="ti ti-cash-banknote"
                    />
                ) : (
                    <>
                        {filters.view === 'avance' ? (
                            <DataTable
                                loading={isLoading}
                                head={
                                    <tr>
                                        <th>Référence</th>
                                        <th>Étudiant</th>
                                        <th className="text-end">Montant</th>
                                        <th>Caisse</th>
                                        <th>Date</th>
                                        <th className="text-end">Montant utilisé</th>
                                        <th className="text-end">Montant restant</th>
                                        <th className="text-end">Action</th>
                                    </tr>
                                }
                            >
                                {encaissements.data.map((row) => (
                                    <tr key={row.id}>
                                        <td>
                                            <code>{row.reference}</code>
                                        </td>
                                        <td>{row.student ?? '—'}</td>
                                        <td className="text-end fw-medium">{Number(row.montant).toFixed(2)} MAD</td>
                                        <td>{row.caisse ?? '—'}</td>
                                        <td>{row.datePaiement ?? '—'}</td>
                                        <td className="text-end">{Number(row.montantUtilise ?? 0).toFixed(2)} MAD</td>
                                        <td className="text-end fw-medium">
                                            {Number(row.montantRestant ?? row.montant).toFixed(2)} MAD
                                        </td>
                                        <td className="text-end">
                                            <RowActions view={row.showUrl}>
                                                {Number(row.montantRestant ?? row.montant) > 0 && (
                                                    <RowActionItem icon="ti-arrow-forward" onClick={() => openApplyAvance(row)}>
                                                        Appliquer à un frais
                                                    </RowActionItem>
                                                )}
                                            </RowActions>
                                        </td>
                                    </tr>
                                ))}
                            </DataTable>
                        ) : (
                            <DataTable
                                loading={isLoading}
                                head={
                                    <tr>
                                        <th>Référence</th>
                                        <th>Étudiant</th>
                                        {filters.view === 'cheque' && (
                                            <>
                                                <th>Num Chèque</th>
                                                <th>Banque</th>
                                                <th>Échéance</th>
                                            </>
                                        )}
                                        <th>Frais</th>
                                        <th>Caisse</th>
                                        <th className="text-end">Montant</th>
                                        <th>Méthode</th>
                                        <th>Date</th>
                                        <th className="text-end">Action</th>
                                    </tr>
                                }
                            >
                                {encaissements.data.map((row) => (
                                    <tr key={row.id}>
                                        <td>
                                            <code>{row.reference}</code>
                                        </td>
                                        <td>{row.student ?? '—'}</td>
                                        {filters.view === 'cheque' && (
                                            <>
                                                <td>{row.numeroCheque ?? '—'}</td>
                                                <td>{row.banque ?? '—'}</td>
                                                <td>{row.dateEcheanceCheque ?? '—'}</td>
                                            </>
                                        )}
                                        <td>{row.feeNom ?? '—'}</td>
                                        <td>{row.caisse ?? '—'}</td>
                                        <td className="text-end fw-medium">
                                            {Number(row.montant).toFixed(2)} MAD
                                            {Number(row.montantRembourse) > 0 && (
                                                <div className="text-danger fs-12">Remboursé : {Number(row.montantRembourse).toFixed(2)} MAD</div>
                                            )}
                                        </td>
                                        <td>
                                            <span className="badge badge-soft-info">{row.methode}</span>
                                        </td>
                                        <td>{row.datePaiement ?? '—'}</td>
                                        <td>
                                            <RowActions view={row.showUrl}>
                                                <RowActionItem icon="ti-edit" onClick={() => openEdit(row)}>
                                                    Modifier
                                                </RowActionItem>
                                                <RowActionDivider />
                                                <RowActionItem
                                                    icon="ti-file-text"
                                                    onClick={() => window.open(`${row.recuUrl}?format=a6`, '_blank')}
                                                >
                                                    Générer le reçu pour une imprimante ticket (format A6)
                                                </RowActionItem>
                                                <RowActionItem
                                                    icon="ti-file-text"
                                                    onClick={() => window.open(`${row.recuUrl}?format=a5`, '_blank')}
                                                >
                                                    Générer le reçu format A5
                                                </RowActionItem>
                                                <RowActionItem
                                                    icon="ti-file-text"
                                                    onClick={() => window.open(`${row.recuUrl}?format=a5x2`, '_blank')}
                                                >
                                                    Générer deux copies du reçu (Paysage A5)
                                                </RowActionItem>
                                                <RowActionDivider />
                                                <RowActionItem icon="ti-mail" onClick={() => openEmailRecu(row)}>
                                                    Envoyer le reçu par email
                                                </RowActionItem>
                                                {can?.delete && (
                                                    <>
                                                        <RowActionDivider />
                                                        <RowActionItem
                                                            icon="ti-trash"
                                                            danger
                                                            onClick={() => {
                                                                setDeleteError(undefined);
                                                                setDeleteTarget(row);
                                                            }}
                                                        >
                                                            Supprimer
                                                        </RowActionItem>
                                                    </>
                                                )}
                                            </RowActions>
                                        </td>
                                    </tr>
                                ))}
                            </DataTable>
                        )}
                        <Pagination paginator={encaissements} only={RELOAD_ONLY} />
                    </>
                )}
            </Card>

            {/* Create modal — cascading student → inscription → fee-lines form. */}
            <Modal
                show={showModal && editingRow === null}
                title="Enregistrer un paiement"
                onClose={closeModal}
                processing={createForm.processing}
                size="xl"
                footer={
                    <FormActions form="encaissement-create-form" onCancel={closeModal} processing={createForm.processing} submitLabel="Enregistrer" />
                }
            >
                <form id="encaissement-create-form" onSubmit={submitCreate}>
                    <div className="row">
                        <div className="col-md-6">
                            <SelectField
                                id="e-student"
                                label="Étudiant"
                                options={studentOptions}
                                placeholder="Sélectionner un étudiant"
                                required
                                value={createForm.data.student_id}
                                onChange={(e) => onStudentChange(e.target.value === '' ? '' : Number(e.target.value))}
                                error={createForm.errors.student_id}
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="e-inscription"
                                label="Inscription"
                                options={inscriptionOptions}
                                placeholder={loadingInscriptions ? 'Chargement…' : 'Sélectionner une inscription'}
                                required
                                disabled={createForm.data.student_id === '' || loadingInscriptions}
                                value={createForm.data.inscription_id}
                                onChange={(e) => onInscriptionChange(e.target.value === '' ? '' : Number(e.target.value))}
                                error={createForm.errors.inscription_id}
                            />
                        </div>
                    </div>

                    {loadingFees && <p className="text-muted">Chargement des frais…</p>}

                    {createForm.data.payment_lines.length > 0 && (
                        <>
                            {/* wimschool-style compact fee table: Frais / Date
                                d'échéance / Montant / Reste à payer / Montant
                                de paiement / Méthode / Date — one row per
                                unpaid fee, replacing the old stacked cards. */}
                            <div className="table-responsive mb-3">
                                <table className="table table-bordered align-middle mb-0">
                                    <thead className="table-light">
                                        <tr>
                                            <th>Frais</th>
                                            <th>Date d'échéance</th>
                                            <th className="text-end">Montant</th>
                                            <th className="text-end">Reste à payer</th>
                                            <th style={{ minWidth: 140 }}>Montant de paiement</th>
                                            <th style={{ minWidth: 150 }}>Méthode</th>
                                            <th style={{ minWidth: 160 }}>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {createForm.data.payment_lines
                                            // Pair each line with its real array index BEFORE
                                            // slicing — pagination only changes what's on
                                            // screen, never which row a montant/date belongs
                                            // to (still keyed by the original index).
                                            .map((line, index) => ({ line, index }))
                                            .slice((feeLinesPage - 1) * FEE_LINES_PER_PAGE, feeLinesPage * FEE_LINES_PER_PAGE)
                                            .map(({ line, index }) => {
                                                // Server errors arrive keyed per submitted row
                                                // (payment_lines.<i>.montant/…) — surface them on
                                                // the row so a refused save is never silent.
                                                const rowErrors = createForm.errors as Record<string, string | undefined>;
                                                const montantError = rowErrors[`payment_lines.${index}.montant`];
                                                const dateError = rowErrors[`payment_lines.${index}.date_paiement`];
                                                const chequeError = rowErrors[`payment_lines.${index}.cheque_id`];
                                                const isCheque = line.methode === 'Chèque';

                                                return (
                                                    <Fragment key={line.feeId}>
                                                        <tr>
                                                            <td className="fw-medium">{line.nom}</td>
                                                            <td>{line.dateEcheance ?? '—'}</td>
                                                            <td className="text-end">{Number(line.montantInitial).toFixed(2)} DH</td>
                                                            <td className="text-end">{Number(line.reste).toFixed(2)} DH</td>
                                                            <td>
                                                                <div className="input-group input-group-sm">
                                                                    <input
                                                                        id={`pl-montant-${index}`}
                                                                        type="number"
                                                                        step="0.01"
                                                                        min="0"
                                                                        max={line.reste}
                                                                        placeholder="0"
                                                                        className={`form-control${montantError ? ' is-invalid' : ''}`}
                                                                        value={line.montant}
                                                                        onChange={(e) => setLine(index, { montant: e.target.value })}
                                                                        aria-invalid={montantError ? true : undefined}
                                                                    />
                                                                    <span className="input-group-text">DH</span>
                                                                </div>
                                                                {montantError && <div className="text-danger fs-12 mt-1">{montantError}</div>}
                                                            </td>
                                                            <td>
                                                                <SelectField
                                                                    id={`pl-methode-${index}`}
                                                                    options={methodeOptions}
                                                                    value={line.methode}
                                                                    onChange={(e) =>
                                                                        setLine(index, {
                                                                            methode: e.target.value,
                                                                            chequeId: e.target.value === 'Chèque' ? line.chequeId : '',
                                                                        })
                                                                    }
                                                                />
                                                            </td>
                                                            <td>
                                                                <DateField
                                                                    id={`pl-date-${index}`}
                                                                    value={line.datePaiement}
                                                                    onChange={(e) => setLine(index, { datePaiement: e.target.value })}
                                                                    error={dateError}
                                                                    panelAlign="right"
                                                                />
                                                            </td>
                                                        </tr>
                                                        {isCheque && (
                                                            <tr>
                                                                <td colSpan={7} className="bg-light-subtle">
                                                                    <SelectField
                                                                        id={`pl-cheque-${index}`}
                                                                        label="Chèque enregistré"
                                                                        required
                                                                        options={studentCheques.map((c) => ({
                                                                            value: c.id,
                                                                            label: `${c.numeroCheque}${c.banque ? ` — ${c.banque}` : ''} (reste ${Number(c.reste).toFixed(2)} DH)`,
                                                                        }))}
                                                                        placeholder={
                                                                            studentCheques.length === 0
                                                                                ? "Aucun chèque enregistré pour cet étudiant — ajoutez-en un dans Chèques"
                                                                                : 'Choisir un chèque'
                                                                        }
                                                                        disabled={studentCheques.length === 0}
                                                                        value={line.chequeId}
                                                                        onChange={(e) =>
                                                                            setLine(index, {
                                                                                chequeId: e.target.value ? Number(e.target.value) : '',
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
                                page={feeLinesPage}
                                pageCount={Math.ceil(createForm.data.payment_lines.length / FEE_LINES_PER_PAGE)}
                                onPageChange={setFeeLinesPage}
                                total={createForm.data.payment_lines.length}
                                perPage={FEE_LINES_PER_PAGE}
                            />
                            {createForm.errors.payment_lines && (
                                <div className="text-danger small mb-3">{createForm.errors.payment_lines}</div>
                            )}
                        </>
                    )}

                    {/*
                     * No Caisse field: the server derives the till from the
                     * signed-in employee's own caisse (EncaissementController
                     * @store + CaisseProvisioner self-heal) — never chosen
                     * client-side, for any role.
                     */}
                    <TextareaField
                        id="e-note"
                        label="Note"
                        value={createForm.data.note}
                        onChange={(e) => createForm.setData('note', e.target.value)}
                        error={createForm.errors.note}
                    />
                </form>
            </Modal>

            {/* Edit modal — wimschool-style layout: read-only context fields
                (étudiant / frais / montant total / reste à payer / montant de
                paiement — money records are append-only, CLAUDE.md §11) with
                only méthode/date/chèque/note as live inputs. */}
            <Modal
                show={showModal && editingRow !== null}
                title={editingRow ? `Modifier paiement : ${editingRow.reference}` : ''}
                onClose={closeModal}
                processing={editForm.processing}
                size="lg"
                footer={
                    <FormActions
                        form="encaissement-edit-form"
                        onCancel={closeModal}
                        processing={editForm.processing}
                        submitLabel="Modifier"
                        cancelLabel="Fermer"
                    />
                }
            >
                {editingRow && (
                    <form id="encaissement-edit-form" onSubmit={submitEdit}>
                        <div className="row">
                            <div className="col-md-6 mb-3">
                                <label className="form-label" htmlFor="e-edit-student">
                                    Étudiant
                                </label>
                                <input
                                    id="e-edit-student"
                                    type="text"
                                    className="form-control"
                                    value={[editingRow.studentRef, editingRow.student].filter(Boolean).join(' | ') || '—'}
                                    disabled
                                />
                            </div>
                            <div className="col-md-6 mb-3">
                                <label className="form-label" htmlFor="e-edit-frais">
                                    Frais
                                </label>
                                <input
                                    id="e-edit-frais"
                                    type="text"
                                    className="form-control"
                                    value={editingRow.feeNom ?? 'Avance'}
                                    disabled
                                />
                            </div>
                            <div className="col-md-6 mb-3">
                                <label className="form-label" htmlFor="e-edit-montant-total">
                                    Montant total
                                </label>
                                <div className="input-group">
                                    <input
                                        id="e-edit-montant-total"
                                        type="text"
                                        className="form-control"
                                        value={editingRow.feeMontantTotal !== null ? Number(editingRow.feeMontantTotal).toFixed(2) : '—'}
                                        disabled
                                    />
                                    <span className="input-group-text">DH</span>
                                </div>
                            </div>
                            <div className="col-md-6 mb-3">
                                <label className="form-label" htmlFor="e-edit-reste">
                                    Reste à payer
                                </label>
                                <div className="input-group">
                                    <input
                                        id="e-edit-reste"
                                        type="text"
                                        className="form-control"
                                        value={editingRow.feeReste !== null ? Number(editingRow.feeReste).toFixed(2) : '—'}
                                        disabled
                                    />
                                    <span className="input-group-text">DH</span>
                                </div>
                            </div>
                            {/* Montant read-only by design: money records are
                                append-only (corrections = remboursement + new
                                paiement), and caisse.solde would silently
                                desync otherwise. */}
                            <div className="col-md-6 mb-3">
                                <label className="form-label" htmlFor="e-edit-montant">
                                    Montant de paiement
                                </label>
                                <div className="input-group">
                                    <input
                                        id="e-edit-montant"
                                        type="text"
                                        className="form-control"
                                        value={Number(editingRow.montant).toFixed(2)}
                                        disabled
                                    />
                                    <span className="input-group-text">DH</span>
                                </div>
                            </div>
                            <div className="col-md-6">
                                {/* Frozen with the row: the method decided which
                                    account was credited (UpdateEncaissementRequest). */}
                                <SelectField
                                    id="e-edit-methode"
                                    label="Méthode de paiement"
                                    options={methodeOptions}
                                    required
                                    disabled
                                    value={editForm.data.methode}
                                    onChange={(e) => editForm.setData('methode', e.target.value)}
                                    error={editForm.errors.methode}
                                />
                            </div>
                            <div className="col-md-6">
                                <DateField
                                    id="e-edit-date"
                                    label="Date"
                                    required
                                    value={editForm.data.date_paiement}
                                    onChange={(e) => editForm.setData('date_paiement', e.target.value)}
                                    error={editForm.errors.date_paiement}
                                />
                            </div>
                        </div>
                        {editForm.data.methode === 'Chèque' && (
                            <div className="row">
                                <div className="col-md-4">
                                    <FormField
                                        id="e-edit-numero-cheque"
                                        label="Numéro de chèque"
                                        required
                                        value={editForm.data.numero_cheque}
                                        onChange={(e) => editForm.setData('numero_cheque', e.target.value)}
                                        error={editForm.errors.numero_cheque}
                                    />
                                </div>
                                <div className="col-md-4">
                                    <FormField
                                        id="e-edit-banque"
                                        label="Banque"
                                        required
                                        list="banques-suggestions"
                                        value={editForm.data.banque}
                                        onChange={(e) => editForm.setData('banque', e.target.value)}
                                        error={editForm.errors.banque}
                                        placeholder="ex : Attijariwafa Bank"
                                    />
                                </div>
                                <div className="col-md-4">
                                    <DateField
                                        id="e-edit-date-echeance-cheque"
                                        label="Échéance du chèque"
                                        required
                                        value={editForm.data.date_echeance_cheque}
                                        onChange={(e) => editForm.setData('date_echeance_cheque', e.target.value)}
                                        error={editForm.errors.date_echeance_cheque}
                                    />
                                </div>
                            </div>
                        )}
                        <TextareaField
                            id="e-edit-note"
                            label="Note"
                            value={editForm.data.note}
                            onChange={(e) => editForm.setData('note', e.target.value)}
                            error={editForm.errors.note}
                        />
                    </form>
                )}
            </Modal>

            {/* Email receipt modal — prompts/confirms the recipient address
                (pre-filled from the student's own email when on file, but
                editable since not every student has one) before posting to
                recuEmailUrl; the server renders the same A5 receipt as a PDF
                attachment (EncaissementRecuMail). */}
            <Modal
                show={emailTarget !== null}
                title={emailTarget ? `Envoyer le reçu par email : ${emailTarget.reference}` : ''}
                onClose={closeEmailModal}
                processing={emailForm.processing}
                footer={
                    <FormActions
                        form="encaissement-email-recu-form"
                        onCancel={closeEmailModal}
                        processing={emailForm.processing}
                        submitLabel="Envoyer"
                        cancelLabel="Annuler"
                    />
                }
            >
                {emailTarget && (
                    <form id="encaissement-email-recu-form" onSubmit={submitEmailRecu}>
                        <FormField
                            id="e-email-recu"
                            type="email"
                            label="Adresse email"
                            value={emailForm.data.email}
                            onChange={(e) => emailForm.setData('email', e.target.value)}
                            error={emailForm.errors.email}
                            required
                        />
                    </form>
                )}
            </Modal>

            {/* Convert-to-avance modal — no montant input: select a student,
                one of their inscriptions, then tick the payments to detach
                from their frais (avances.convert). The frais drop back to
                Non payé / Payé partiellement; the amounts reappear on the
                Avances tab (montant utilisé/restant) ready to be applied to
                another inscription's frais — the "changement de groupe"
                money-move flow. */}
            <Modal
                show={showAvanceModal}
                title="Convertir des paiements en avance"
                onClose={closeAvanceModal}
                processing={avanceForm.processing}
                size="lg"
                footer={
                    <FormActions
                        form="avance-form"
                        onCancel={closeAvanceModal}
                        processing={avanceForm.processing}
                        submitLabel="Convertir en avance"
                    />
                }
            >
                <form id="avance-form" onSubmit={submitAvance}>
                    <div className="row">
                        <div className="col-md-6">
                            <SelectField
                                id="av-student"
                                label="Étudiant"
                                options={studentOptions}
                                placeholder="Sélectionner un étudiant"
                                required
                                value={avanceForm.data.student_id}
                                onChange={(e) => onAvanceStudentChange(e.target.value === '' ? '' : Number(e.target.value))}
                                error={avanceForm.errors.student_id}
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="av-inscription"
                                label="Inscription"
                                options={avanceInscriptionOptions}
                                placeholder={loadingAvanceInscriptions ? 'Chargement…' : 'Sélectionner une inscription'}
                                required
                                disabled={avanceForm.data.student_id === '' || loadingAvanceInscriptions}
                                value={avanceForm.data.inscription_id}
                                onChange={(e) => onAvanceInscriptionChange(e.target.value === '' ? '' : Number(e.target.value))}
                                error={avanceForm.errors.inscription_id}
                            />
                        </div>
                    </div>

                    {avanceForm.data.inscription_id !== '' && (
                        loadingAvancePayments ? (
                            <p className="text-muted mb-0">Chargement des paiements…</p>
                        ) : avancePayments.length === 0 ? (
                            <p className="text-muted mb-0">Aucun paiement enregistré sur cette inscription.</p>
                        ) : (
                            <>
                                <p className="text-muted fs-13">
                                    Les paiements cochés seront détachés de leurs frais (qui redeviennent à payer)
                                    et leur montant sera disponible en avance, à appliquer ensuite sur les frais
                                    d'une autre inscription. Aucun paiement n'est supprimé et la caisse n'est pas
                                    modifiée.
                                </p>
                                <div className="table-responsive">
                                    <table className="table table-sm align-middle mb-2">
                                        <thead>
                                            <tr>
                                                <th style={{ width: '2.5rem' }}>
                                                    <input
                                                        type="checkbox"
                                                        className="form-check-input"
                                                        aria-label="Tout sélectionner"
                                                        checked={allAvanceSelected}
                                                        onChange={toggleAllAvancePayments}
                                                    />
                                                </th>
                                                <th>Référence</th>
                                                <th>Frais</th>
                                                <th className="text-end">Montant</th>
                                                <th>Méthode</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {avancePayments.map((payment) => (
                                                <tr key={payment.id}>
                                                    <td>
                                                        <input
                                                            type="checkbox"
                                                            className="form-check-input"
                                                            aria-label={`Sélectionner ${payment.reference}`}
                                                            disabled={payment.rembourse}
                                                            checked={avanceForm.data.encaissement_ids.includes(payment.id)}
                                                            onChange={() => toggleAvancePayment(payment.id)}
                                                        />
                                                    </td>
                                                    <td>
                                                        <code>{payment.reference}</code>
                                                    </td>
                                                    <td>
                                                        {payment.feeNom ?? '—'}
                                                        {payment.rembourse && (
                                                            <span className="badge badge-soft-danger ms-2">Remboursé</span>
                                                        )}
                                                    </td>
                                                    <td className="text-end fw-medium">{Number(payment.montant).toFixed(2)} MAD</td>
                                                    <td>
                                                        <span className="badge badge-soft-info">{payment.methode}</span>
                                                    </td>
                                                    <td>{payment.datePaiement ?? '—'}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                <div className="d-flex justify-content-between border-top pt-2">
                                    <span className="text-muted">
                                        {avanceForm.data.encaissement_ids.length} paiement(s) sélectionné(s)
                                    </span>
                                    <span className="fw-medium">Total à convertir : {avanceSelectedTotal.toFixed(2)} MAD</span>
                                </div>
                            </>
                        )
                    )}
                    {avanceForm.errors.encaissement_ids && (
                        <div className="text-danger fs-13 mt-2">{avanceForm.errors.encaissement_ids}</div>
                    )}
                </form>
            </Modal>

            {/* Apply-avance modal — spends part/all of an avance's remaining
                balance on a fee. The avance row itself is never edited; this
                creates a second Encaissement (AppliquerAvance) linked back
                via applied_from_encaissement_id. */}
            <Modal
                show={applyTarget !== null}
                title={applyTarget ? `Appliquer l'avance ${applyTarget.reference}` : ''}
                onClose={closeApplyModal}
                processing={applyForm.processing}
                size="lg"
                footer={<FormActions form="apply-avance-form" onCancel={closeApplyModal} processing={applyForm.processing} submitLabel="Appliquer" />}
            >
                {applyTarget && (
                    <form id="apply-avance-form" onSubmit={submitApplyAvance}>
                        <div className="d-flex justify-content-between mb-3">
                            <span className="text-muted">Montant restant de l'avance</span>
                            <span className="fw-medium">{avanceRestant.toFixed(2)} MAD</span>
                        </div>
                        <div className="row">
                            <div className="col-md-6">
                                <SelectField
                                    id="ap-inscription"
                                    label="Inscription"
                                    options={applyInscriptionOptions}
                                    placeholder={loadingApplyInscriptions ? 'Chargement…' : 'Sélectionner une inscription'}
                                    required
                                    disabled={loadingApplyInscriptions}
                                    value={applyForm.data.inscription_id}
                                    onChange={(e) => onApplyInscriptionChange(e.target.value === '' ? '' : Number(e.target.value))}
                                    error={applyForm.errors.inscription_id}
                                />
                            </div>
                            <div className="col-md-6">
                                <SelectField
                                    id="ap-fee"
                                    label="Frais"
                                    options={applyFeeOptions}
                                    placeholder={loadingApplyFees ? 'Chargement…' : 'Sélectionner un frais'}
                                    required
                                    disabled={applyForm.data.inscription_id === '' || loadingApplyFees}
                                    value={applyForm.data.fee_id}
                                    onChange={(e) =>
                                        applyForm.setData('fee_id', e.target.value === '' ? '' : Number(e.target.value))
                                    }
                                    error={applyForm.errors.fee_id}
                                />
                            </div>
                        </div>
                        {selectedApplyFee && (
                            <p className="text-muted fs-13">Reste dû sur ce frais : {Number(selectedApplyFee.reste).toFixed(2)} MAD</p>
                        )}
                        <FormField
                            id="ap-montant"
                            label="Montant à appliquer"
                            type="number"
                            step="0.01"
                            min="0.01"
                            max={applyMaxMontant}
                            required
                            disabled={applyForm.data.fee_id === ''}
                            value={applyForm.data.montant}
                            onChange={(e) => applyForm.setData('montant', e.target.value)}
                            error={applyForm.errors.montant}
                        />
                    </form>
                )}
            </Modal>

            {/* Autocomplete suggestions for the Banque text inputs above — sourced
                from the catalog (Paramètres → Banques), but typing any other
                name is still accepted (free text, not a strict dropdown). */}
            <datalist id="banques-suggestions">
                {banques.map((nom) => (
                    <option key={nom} value={nom} />
                ))}
            </datalist>

            <ConfirmDialog
                show={deleteTarget !== null}
                title="Supprimer cet encaissement ?"
                recordLabel={deleteTarget?.reference ?? ''}
                message="Cette action est définitive. Le solde de la caisse sera diminué du montant de ce paiement et le statut du frais recalculé."
                error={deleteError}
                processing={deleting}
                onConfirm={confirmDeleteEncaissement}
                onCancel={() => setDeleteTarget(null)}
            />
        </BackofficeLayout>
    );
}
