import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
import SearchInput from '@/Components/Tables/SearchInput';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import StatusBadge from '@/Components/Details/StatusBadge';
import SelectField from '@/Components/Forms/SelectField';
import DateField from '@/Components/Forms/DateField';
import FormField from '@/Components/Forms/FormField';
import TextareaField from '@/Components/Forms/TextareaField';
import TagsInput from '@/Components/Forms/TagsInput';
import FormActions from '@/Components/Forms/FormActions';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { useFilterReset } from '@/Hooks/useFilterReset';
import type { DepenseRow, DepensesPageProps, EncaissementFormOption, RemboursementCaisseOption, RemboursementRow, SelectOption, SharedProps } from '@/Types';

type Tab = 'depenses' | 'paiements-prof' | 'remboursements' | 'validation';

interface DepenseFormState {
    type_depense_id: number | '';
    group_id: number | '';
    montant: string;
    methode_paiement: string;
    date_depense: string;
    periode_debut: string;
    periode_fin: string;
    reference_facture: string;
    description: string;
    mots_cles: string;
    note: string;
    justificatifs: File[];
}

interface RemboursementFormState {
    beneficiaire_id: number | '';
    encaissement_id: number | '';
    caisse_id: number | '';
    montant: string;
    date_remboursement: string;
    motif: string;
    note: string;
}

/**
 * « Date d'opération » cell — when the row was really keyed in, and when it
 * was last edited if that ever happened.
 *
 * Deliberately distinct from the « Date » column, which shows `dateDepense`:
 * the business date a user types and can backdate at will. The two differing
 * is exactly the signal an auditor looks for, which is why this column exists
 * and why it is super-admin only.
 *
 * The props are optional because the server does not send them at all to a
 * non-super-admin (DepenseController::scrubOperationDates) — this renders a
 * dash rather than guessing.
 */
function OperationDateCell({ createdAt, updatedAt, wasEdited }: {
    createdAt?: string | null;
    updatedAt?: string | null;
    wasEdited?: boolean;
}) {
    if (!createdAt) {
        return <>—</>;
    }

    return (
        <>
            <span className="d-block">{createdAt}</span>
            {wasEdited && updatedAt && (
                <span className="d-block text-warning fs-12 text-normal-case">
                    <i className="ti ti-pencil me-1" aria-hidden="true" />
                    modifiée {updatedAt}
                </span>
            )}
        </>
    );
}

/** One labelled read-only field of « Les détails de dépense ». */
function DetailLine({ label, value }: { label: string; value?: string | null }) {
    return (
        <div>
            <span className="text-muted d-block fs-13">{label}</span>
            <span className="fw-medium text-normal-case">{value || '—'}</span>
        </div>
    );
}

/** Approval workflow statuses -> PreSkool badge variants. */
const DEPENSE_STATUT_BADGE: Record<string, 'success' | 'warning' | 'danger'> = {
    'En attente': 'warning',
    'Approuvée': 'success',
    'Refusée': 'danger',
};

function emptyDepenseForm(): DepenseFormState {
    return {
        type_depense_id: '',
        group_id: '',
        montant: '',
        methode_paiement: '',
        date_depense: new Date().toISOString().slice(0, 10),
        periode_debut: '',
        periode_fin: '',
        reference_facture: '',
        description: '',
        mots_cles: '',
        note: '',
        justificatifs: [],
    };
}

function emptyRemboursementForm(): RemboursementFormState {
    return {
        beneficiaire_id: '',
        encaissement_id: '',
        caisse_id: '',
        montant: '',
        date_remboursement: new Date().toISOString().slice(0, 10),
        motif: '',
        note: '',
    };
}

/**
 * "Gestion des dépenses" — replaces the Livewire tabbed page (Dépenses +
 * Remboursements). montant/caisse_id (and beneficiaire_id for refunds) are
 * server-derived and frozen — the forms never carry a caisse field — everything else stays editable, matching
 * DepensesIndex/RemboursementsIndex exactly. No max-refund-amount check and
 * no insufficient-balance check are added (docs/phase-10-finance-mapping.md
 * Q1: preserved as-is).
 */
export default function DepensesIndex({
    canViewDepenses,
    canViewRemboursements,
    soldeActuel,
    depenses,
    montantTotal,
    paiementsProf,
    paiementsProfTotal,
    validationDepenses,
    validationMontantTotal,
    validationMontantEnAttente,
    validationEnAttenteCount,
    typesDepenses,
    paiementProfTypeId,
    groups,
    methodes,
    justificatifMimes,
    justificatifMaxKb,
    remboursements,
    remboursementsTotaux,
    canCancelRemboursement,
    remboursementCaisses,
    students,
    approvalEnabled,
    canApprove,
    canAudit,
    depenseStatuts,
    montantEnAttente,
    enAttenteCount,
    filters,
}: DepensesPageProps) {
    const isLoading = useInertiaLoading();
    // The Types de dépenses tab links to its own page — UI-gate it like the
    // sidebar does (server enforcement unchanged).
    const { auth } = usePage<SharedProps>().props;
    const canViewTypes = auth.isSuperAdmin || auth.permissions.includes('expense-types.view');
    const requestedTab = new URLSearchParams(window.location.search).get('tab');
    const initialTab: Tab = requestedTab === 'remboursements' && canViewRemboursements
        ? 'remboursements'
        : requestedTab === 'paiements-prof' && canViewDepenses
            ? 'paiements-prof'
            // Super-admin only — a forced ?tab=validation falls through to the
            // normal default for anyone else, matching the server (which
            // sends them no audit data at all).
            : requestedTab === 'validation' && canAudit
                ? 'validation'
                : canViewDepenses
                    ? 'depenses'
                    : 'remboursements';
    const [tab, setTab] = useState<Tab>(initialTab);

    const [showDepenseModal, setShowDepenseModal] = useState(false);
    // Which of the TWO expense modals is open: the ordinary « Ajouter une
    // dépense » one, or the « Paiement prof » one (type locked, Groupe
    // required, payment period instead of a supplier invoice reference).
    const [profMode, setProfMode] = useState(false);
    const [editingDepense, setEditingDepense] = useState<DepenseRow | null>(null);
    // « Les détails de dépense » — the eye on the Validation tab. Everything
    // shown is already on the row, so opening it costs no extra request.
    const [detailsRow, setDetailsRow] = useState<DepenseRow | null>(null);
    const [showRemboursementModal, setShowRemboursementModal] = useState(false);
    const [remboursementToCancel, setRemboursementToCancel] = useState<RemboursementRow | null>(null);
    const [cancelling, setCancelling] = useState(false);
    const [motifAnnulationRemboursement, setMotifAnnulationRemboursement] = useState('');
    const [editingRemboursement, setEditingRemboursement] = useState<RemboursementRow | null>(null);
    const [studentPayments, setStudentPayments] = useState<EncaissementFormOption[]>([]);
    const [loadingStudentPayments, setLoadingStudentPayments] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const typeOptions: SelectOption[] = typesDepenses.map((t) => ({ value: t.id, label: t.nom }));
    // The Dépenses table excludes "Paiement prof", so filtering by it there
    // could only ever return nothing — drop it from that filter. The same
    // list drives the Dépense MODAL: a paiement prof is created from its own
    // modal (locked type + required Groupe + period), so offering the type
    // here would let a user key one in without any of that.
    const filterTypeOptions: SelectOption[] = typeOptions.filter((o) => o.value !== paiementProfTypeId);
    const paiementProfLabel = typeOptions.find((o) => o.value === paiementProfTypeId)?.label ?? 'Paiement prof';
    const groupOptions: SelectOption[] = groups.map((g) => ({ value: g.id, label: g.nom }));
    const methodeOptions: SelectOption[] = methodes.map((m) => ({ value: m, label: m }));
    const studentOptions: SelectOption[] = students.map((s) => ({ value: s.id, label: s.nom }));
    // Balance in the label: the cashier picks the till knowing what is in it.
    const caisseSelectOptions: SelectOption[] = remboursementCaisses.map((c: RemboursementCaisseOption) => ({
        value: c.id,
        label: `${c.nom} (${Number(c.solde).toFixed(2)} MAD)`,
    }));

    const depenseForm = useForm<DepenseFormState>(emptyDepenseForm());
    const remboursementForm = useForm<RemboursementFormState>(emptyRemboursementForm());

    function reload(nextFilters: Partial<typeof filters>) {
        router.get('/backoffice/depenses', { ...filters, ...nextFilters, page: undefined, pageProf: undefined, pageValidation: undefined, tab }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    const filterReset = useFilterReset(filters, reload, { perPage: filters.perPage });

    // --- Approval flow (Paramètres → Système « Validation des dépenses ») ---
    // A pending dépense has debited NOTHING: approving is what moves the
    // money, refusing never moves any. Server refusals (already decided,
    // permission, no employee record…) surface inside the dialog.
    const [decision, setDecision] = useState<{ type: 'approve' | 'refuse'; row: DepenseRow } | null>(null);
    const [decisionError, setDecisionError] = useState<string | undefined>(undefined);
    const [decisionProcessing, setDecisionProcessing] = useState(false);
    const [motifRefus, setMotifRefus] = useState('');

    function openDecision(type: 'approve' | 'refuse', row: DepenseRow) {
        setDecision({ type, row });
        setDecisionError(undefined);
        setMotifRefus('');
    }

    function closeDecision() {
        setDecision(null);
        setDecisionError(undefined);
        setDecisionProcessing(false);
        setMotifRefus('');
    }

    function runDecision() {
        if (!decision) return;

        const { type, row } = decision;
        setDecisionProcessing(true);
        setDecisionError(undefined);

        router.put(
            `/backoffice/depenses/${row.id}/${type === 'approve' ? 'approve' : 'refuse'}`,
            type === 'refuse' ? { motif_refus: motifRefus } : {},
            {
                preserveScroll: true,
                onSuccess: () => closeDecision(),
                onError: (errors) => {
                    setDecisionProcessing(false);
                    setDecisionError(Object.values(errors)[0] ?? "L'opération a échoué.");
                },
                onFinish: () => setDecisionProcessing(false),
            },
        );
    }

    function switchTab(next: Tab) {
        setTab(next);
        router.get('/backoffice/depenses', { ...filters, page: undefined, pageProf: undefined, pageValidation: undefined, tab: next }, { preserveState: true, preserveScroll: true, replace: true });
    }

    // --- Dépenses ---

    /**
     * Ordinary dépense — the type is picked freely (minus « Paiement prof »,
     * which has its own modal), there is NO Groupe field, and Référence
     * facture applies.
     */
    function openCreateDepense() {
        setEditingDepense(null);
        setProfMode(false);
        depenseForm.clearErrors();
        depenseForm.setData(emptyDepenseForm());
        setShowDepenseModal(true);
    }

    /**
     * « Paiement prof » — same table, same till, different contract: the type
     * is fixed and not changeable, Groupe is REQUIRED, the payment period
     * (du / au) is captured, and the supplier-invoice reference is dropped.
     */
    function openCreatePaiementProf() {
        setEditingDepense(null);
        setProfMode(true);
        depenseForm.clearErrors();
        depenseForm.setData({ ...emptyDepenseForm(), type_depense_id: paiementProfTypeId ?? '' });
        setShowDepenseModal(true);
    }

    function openEditDepense(row: DepenseRow) {
        setEditingDepense(row);
        // Which modal an edit opens follows the ROW, not the tab it was
        // clicked from — the Validation tab lists both kinds.
        setProfMode(paiementProfTypeId !== null && row.typeDepenseId === paiementProfTypeId);
        depenseForm.clearErrors();
        depenseForm.setData({
            type_depense_id: row.typeDepenseId ?? '',
            group_id: row.groupId ?? '',
            montant: row.montant,
            methode_paiement: row.methodePaiement ?? '',
            date_depense: row.dateDepense ?? '',
            periode_debut: row.periodeDebut ?? '',
            periode_fin: row.periodeFin ?? '',
            reference_facture: row.referenceFacture ?? '',
            description: row.description ?? '',
            mots_cles: row.motsCles ?? '',
            note: row.note ?? '',
            justificatifs: [],
        });
        setShowDepenseModal(true);
    }

    function closeDepenseModal() {
        setShowDepenseModal(false);
        setEditingDepense(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
    }

    function handleFilesChange(event: React.ChangeEvent<HTMLInputElement>) {
        depenseForm.setData('justificatifs', Array.from(event.target.files ?? []));
    }

    /**
     * Drop the fields the other modal owns before sending. The Form Requests
     * mark them `prohibited` for the opposite type, so leaving a stale value
     * (e.g. a group picked before switching modals) would fail validation —
     * and, more importantly, the server must never receive a Groupe for an
     * ordinary dépense or a supplier invoice ref for a paiement prof.
     */
    function depensePayload(data: DepenseFormState): Record<string, unknown> {
        const { group_id, periode_debut, periode_fin, reference_facture, ...rest } = data;

        return profMode
            ? { ...rest, group_id, periode_debut, periode_fin }
            : { ...rest, reference_facture };
    }

    function submitDepense(event: FormEvent) {
        event.preventDefault();

        if (editingDepense) {
            depenseForm.transform((data) => ({ ...depensePayload(data), _method: 'put' }));
            depenseForm.post(`/backoffice/depenses/${editingDepense.id}`, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => closeDepenseModal(),
                // Reset the transform so a later create on this shared form
                // doesn't POST with a stale _method=put (Phase 12 UX fix).
                onFinish: () => depenseForm.transform((data) => data),
            });

            return;
        }

        depenseForm.transform((data) => depensePayload(data));
        depenseForm.post('/backoffice/depenses', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeDepenseModal(),
            onFinish: () => depenseForm.transform((data) => data),
        });
    }

    // --- Remboursements ---

    // With a single till in the active centre there is nothing to choose —
    // preselect it so the common case stays one click. With several, the
    // cashier must pick: guessing is what silently drained the wrong centre's
    // till before (03/09/2026).
    const defaultCaisseId = remboursementCaisses.length === 1 ? remboursementCaisses[0].id : '';

    function openCreateRemboursement() {
        setEditingRemboursement(null);
        remboursementForm.clearErrors();
        remboursementForm.setData({ ...emptyRemboursementForm(), caisse_id: defaultCaisseId });
        setStudentPayments([]);
        setShowRemboursementModal(true);
    }

    function openEditRemboursement(row: RemboursementRow) {
        setEditingRemboursement(row);
        remboursementForm.clearErrors();
        remboursementForm.setData({
            beneficiaire_id: row.beneficiaireId ?? '',
            encaissement_id: '',
            caisse_id: row.caisseId ?? '',
            montant: row.montant,
            date_remboursement: row.dateRemboursement ?? '',
            motif: row.motif ?? '',
            note: row.note ?? '',
        });
        setStudentPayments([]);
        setShowRemboursementModal(true);
    }

    function closeRemboursementModal() {
        setShowRemboursementModal(false);
        setEditingRemboursement(null);
        setStudentPayments([]);
    }

    // Pre-fill + auto-open the refund modal when arriving from the Chèques
    // page ("Rejeté" chèque that funded exactly one encaissement) — the
    // query string is the only channel between the two pages/routes, the
    // refund itself is still a normal reviewed submit, nothing is
    // auto-created (§11 money invariants: refunds are always a manual,
    // user-confirmed EnregistrerRemboursement call).
    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const prefillBeneficiaire = params.get('prefill_beneficiaire_id');
        const prefillEncaissement = params.get('prefill_encaissement_id');
        const prefillMontant = params.get('prefill_montant');
        const prefillMotif = params.get('prefill_motif');
        const prefillNote = params.get('prefill_note');

        if (!canViewRemboursements || prefillBeneficiaire === null) {
            return;
        }

        remboursementForm.setData({
            beneficiaire_id: Number(prefillBeneficiaire),
            encaissement_id: prefillEncaissement !== null ? Number(prefillEncaissement) : '',
            caisse_id: remboursementCaisses.length === 1 ? remboursementCaisses[0].id : '',
            montant: prefillMontant ?? '',
            date_remboursement: new Date().toISOString().slice(0, 10),
            motif: prefillMotif ?? '',
            note: prefillNote ?? '',
        });
        setShowRemboursementModal(true);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    async function onBeneficiaireChange(studentId: number | '') {
        remboursementForm.setData((previous) => ({
            ...previous,
            beneficiaire_id: studentId,
            encaissement_id: '',
            montant: '',
        }));
        setStudentPayments([]);

        if (studentId === '') {
            return;
        }

        setLoadingStudentPayments(true);
        try {
            const response = await fetch(`/backoffice/students/${studentId}/payments-for-refund`);
            const data: { payments: EncaissementFormOption[] } = await response.json();
            setStudentPayments(data.payments);
        } finally {
            setLoadingStudentPayments(false);
        }
    }

    function selectPaymentToRefund(payment: EncaissementFormOption) {
        // montantRemboursable comes from the server: an avance is capped at
        // what is still unallocated, a fee payment at what it brought in
        // less prior refunds. Subtracting dejaRembourse from montant here
        // would pre-fill a partly-applied avance with money it no longer
        // holds, and the action would reject the submit.
        remboursementForm.setData((previous) => ({
            ...previous,
            encaissement_id: payment.id,
            montant: Number(payment.montantRemboursable).toFixed(2),
        }));
    }

    function submitRemboursement(event: FormEvent) {
        event.preventDefault();

        if (editingRemboursement) {
            remboursementForm.put(`/backoffice/remboursements/${editingRemboursement.id}`, {
                preserveScroll: true,
                onSuccess: () => closeRemboursementModal(),
            });

            return;
        }

        remboursementForm.post('/backoffice/remboursements', {
            preserveScroll: true,
            onSuccess: () => closeRemboursementModal(),
        });
    }

    return (
        <BackofficeLayout
            title="Gestion des dépenses"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Gestion des dépenses' }]}
        >
            {/* wimschool-style page tabs: the module's own sub-views, plus
                Types de dépenses as a sibling page (its own route). */}
            <ul className="nav nav-tabs p-0 border-bottom rounded-0 mb-4" role="tablist">
                {canViewDepenses && (
                    <li className="nav-item" role="presentation">
                        <button
                            type="button"
                            className={`nav-link d-inline-flex align-items-center${tab === 'depenses' ? ' active' : ''}`}
                            aria-current={tab === 'depenses' ? 'page' : undefined}
                            onClick={() => switchTab('depenses')}
                        >
                            <i className="ti ti-receipt me-2" aria-hidden="true" />
                            Dépenses
                        </button>
                    </li>
                )}
                {canViewDepenses && (
                    <li className="nav-item" role="presentation">
                        <button
                            type="button"
                            className={`nav-link d-inline-flex align-items-center${tab === 'paiements-prof' ? ' active' : ''}`}
                            aria-current={tab === 'paiements-prof' ? 'page' : undefined}
                            onClick={() => switchTab('paiements-prof')}
                        >
                            <i className="ti ti-user-dollar me-2" aria-hidden="true" />
                            Paiements prof
                        </button>
                    </li>
                )}
                {canViewRemboursements && (
                    <li className="nav-item" role="presentation">
                        <button
                            type="button"
                            className={`nav-link d-inline-flex align-items-center${tab === 'remboursements' ? ' active' : ''}`}
                            aria-current={tab === 'remboursements' ? 'page' : undefined}
                            onClick={() => switchTab('remboursements')}
                        >
                            <i className="ti ti-arrow-back-up me-2" aria-hidden="true" />
                            Remboursements
                        </button>
                    </li>
                )}
                {/* Super-admin only: `expenses.approve` is in no role preset
                    (PermissionRegistry::matrix()), so canAudit is the
                    Gate::before bypass in practice. */}
                {canAudit && (
                    <li className="nav-item" role="presentation">
                        <button
                            type="button"
                            className={`nav-link d-inline-flex align-items-center${tab === 'validation' ? ' active' : ''}`}
                            aria-current={tab === 'validation' ? 'page' : undefined}
                            onClick={() => switchTab('validation')}
                        >
                            <i className="ti ti-checkup-list me-2" aria-hidden="true" />
                            Validation des dépenses
                            {validationEnAttenteCount > 0 && (
                                <span className="badge bg-warning ms-2">{validationEnAttenteCount}</span>
                            )}
                        </button>
                    </li>
                )}
                {canViewTypes && (
                    <li className="nav-item" role="presentation">
                        <Link href="/backoffice/types-depenses" className="nav-link d-inline-flex align-items-center">
                            <i className="ti ti-receipt-tax me-2" aria-hidden="true" />
                            Types de dépenses
                        </Link>
                    </li>
                )}
            </ul>

            {tab === 'depenses' && canViewDepenses && depenses && (
                <Card
                    title="Dépenses"
                    bodyClassName="p-0 py-3"
                    tools={
                        <button
                            type="button"
                            className="btn btn-primary d-flex align-items-center mb-3"
                            onClick={openCreateDepense}
                        >
                            <i className="ti ti-square-rounded-plus me-2" />
                            Ajouter une dépense
                        </button>
                    }
                >
                    <div className="px-3 pt-2">
                        <TableToolbar onReset={filterReset.reset} resetActive={filterReset.active}>
                            <div style={{ width: 220 }}>
                                <label className="form-label" htmlFor="dep-f-type">
                                    Type
                                </label>
                                <SelectField
                                    id="dep-f-type"
                                    options={filterTypeOptions}
                                    placeholder="Tous les types"
                                    value={filters.typeFilter}
                                    onChange={(event) => reload({ typeFilter: event.target.value })}
                                />
                            </div>
                            {/* Only meaningful while the approval workflow is
                                on: with it off every depense is approved. */}
                            {approvalEnabled && (
                                <div style={{ width: 200 }}>
                                    <label className="form-label" htmlFor="dep-f-statut">
                                        Statut
                                    </label>
                                    <SelectField
                                        id="dep-f-statut"
                                        options={depenseStatuts.map((st) => ({ value: st, label: st }))}
                                        placeholder="Tous les statuts"
                                        value={filters.statutFilter}
                                        onChange={(event) => reload({ statutFilter: event.target.value })}
                                    />
                                </div>
                            )}
                            <div style={{ width: 170 }}>
                                <label className="form-label" htmlFor="dep-f-du">
                                    Du
                                </label>
                                <DateField
                                    id="dep-f-du"
                                    value={filters.dateFrom}
                                    onChange={(event) => reload({ dateFrom: event.target.value })}
                                />
                            </div>
                            <div style={{ width: 170 }}>
                                <label className="form-label" htmlFor="dep-f-au">
                                    Au
                                </label>
                                <DateField
                                    id="dep-f-au"
                                    value={filters.dateTo}
                                    onChange={(event) => reload({ dateTo: event.target.value })}
                                />
                            </div>
                        </TableToolbar>
                    </div>

                    <TableLengthRow
                        search={<SearchInput value={filters.search} onSearch={(value) => reload({ search: value })} />}
                    />

                    {montantTotal !== null && (
                        <p className="fw-medium px-3 mb-3">
                            Montant total : {Number(montantTotal).toFixed(2)} MAD
                            {/* Approved money only. Pending expenses have not
                                left any till, so they are shown apart rather
                                than folded into the spend total. */}
                            {approvalEnabled && enAttenteCount > 0 && (
                                <span className="text-warning ms-3">
                                    <i className="ti ti-clock-hour-4 me-1" />
                                    En attente : {Number(montantEnAttente ?? 0).toFixed(2)} MAD ({enAttenteCount})
                                </span>
                            )}
                        </p>
                    )}

                    {depenses.data.length === 0 ? (
                        <EmptyState title="Aucune dépense" icon="ti ti-receipt" />
                    ) : (
                        <>
                            <DataTable
                                loading={isLoading}
                                head={
                                    <tr>
                                        <th>Référence</th>
                                        <th>Type</th>
                                        <th>Caisse</th>
                                        <th className="text-end">Montant</th>
                                        {approvalEnabled && <th>Statut</th>}
                                        <th>Date</th>
                                        {canAudit && <th>Date d'opération</th>}
                                        <th>Justificatifs</th>
                                        <th className="text-end">Action</th>
                                    </tr>
                                }
                            >
                                {depenses.data.map((row) => (
                                    <tr key={row.id}>
                                        <td>
                                            <code>{row.reference}</code>
                                        </td>
                                        <td>{row.typeDepense ?? '—'}</td>
                                        <td>{row.caisse ?? '—'}</td>
                                        <td className="text-end fw-medium">{Number(row.montant).toFixed(2)} MAD</td>
                                        {approvalEnabled && (
                                            <td>
                                                <StatusBadge
                                                    label={row.statut}
                                                    variant={DEPENSE_STATUT_BADGE[row.statut] ?? 'warning'}
                                                    dot
                                                />
                                                {row.isRefusee && row.motifRefus && (
                                                    <div className="text-muted fs-12 text-normal-case">{row.motifRefus}</div>
                                                )}
                                            </td>
                                        )}
                                        <td>{row.dateDepense ?? '—'}</td>
                                        {canAudit && (
                                            <td className="text-normal-case">
                                                <OperationDateCell
                                                    createdAt={row.createdAt}
                                                    updatedAt={row.updatedAt}
                                                    wasEdited={row.wasEdited}
                                                />
                                            </td>
                                        )}
                                        <td>
                                            {row.receiptsCount > 0 ? (
                                                <span>
                                                    <i className="ti ti-paperclip me-1" />
                                                    {row.receiptsCount}
                                                </span>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td>
                                            <RowActions view={row.showUrl}>
                                                {/* A refused depense is closed history:
                                                    the policy refuses the edit too. */}
                                                {!row.isRefusee && (
                                                    <RowActionItem icon="ti-edit" onClick={() => openEditDepense(row)}>
                                                        Modifier
                                                    </RowActionItem>
                                                )}
                                                {row.isEnAttente && canApprove && (
                                                    <RowActionItem icon="ti-check" onClick={() => openDecision('approve', row)}>
                                                        Approuver
                                                    </RowActionItem>
                                                )}
                                                {row.isEnAttente && canApprove && (
                                                    <RowActionItem icon="ti-x" onClick={() => openDecision('refuse', row)}>
                                                        Refuser
                                                    </RowActionItem>
                                                )}
                                            </RowActions>
                                        </td>
                                    </tr>
                                ))}
                            </DataTable>
                            <Pagination paginator={depenses} />
                        </>
                    )}
                </Card>
            )}

            {/* Paiements prof — the same dépenses (same table, same caisse,
                same modal), only the "Paiement prof" type, listed apart so
                the Dépenses table above stays readable. */}
            {tab === 'paiements-prof' && canViewDepenses && paiementsProf && (
                <Card
                    title="Paiements prof"
                    bodyClassName="p-0 py-3"
                    tools={
                        <button
                            type="button"
                            className="btn btn-primary d-flex align-items-center mb-3"
                            onClick={openCreatePaiementProf}
                        >
                            <i className="ti ti-square-rounded-plus me-2" />
                            Ajouter un paiement prof
                        </button>
                    }
                >
                    <div className="px-3 pt-2">
                        <TableToolbar onReset={filterReset.reset} resetActive={filterReset.active}>
                            <div style={{ width: 170 }}>
                                <label className="form-label" htmlFor="prof-f-du">
                                    Du
                                </label>
                                <DateField
                                    id="prof-f-du"
                                    value={filters.dateFrom}
                                    onChange={(event) => reload({ dateFrom: event.target.value })}
                                />
                            </div>
                            <div style={{ width: 170 }}>
                                <label className="form-label" htmlFor="prof-f-au">
                                    Au
                                </label>
                                <DateField
                                    id="prof-f-au"
                                    value={filters.dateTo}
                                    onChange={(event) => reload({ dateTo: event.target.value })}
                                />
                            </div>
                        </TableToolbar>
                    </div>

                    <TableLengthRow
                        search={<SearchInput value={filters.search} onSearch={(value) => reload({ search: value })} />}
                    />

                    {paiementsProfTotal !== null && (
                        <p className="fw-medium px-3 mb-3">Montant total : {Number(paiementsProfTotal).toFixed(2)} MAD</p>
                    )}

                    {paiementsProf.data.length === 0 ? (
                        <EmptyState title="Aucun paiement prof" icon="ti ti-user-dollar" />
                    ) : (
                        <>
                            <DataTable
                                loading={isLoading}
                                head={
                                    <tr>
                                        <th>Référence</th>
                                        <th>Groupe</th>
                                        <th>Caisse</th>
                                        <th className="text-end">Montant</th>
                                        <th>Date</th>
                                        <th>Période</th>
                                        {canAudit && <th>Date d'opération</th>}
                                        <th>Justificatifs</th>
                                        <th className="text-end">Action</th>
                                    </tr>
                                }
                            >
                                {paiementsProf.data.map((row) => (
                                    <tr key={row.id}>
                                        <td>
                                            <code>{row.reference}</code>
                                        </td>
                                        <td>{row.groupNom ?? '—'}</td>
                                        <td>{row.caisse ?? '—'}</td>
                                        <td className="text-end fw-medium">{Number(row.montant).toFixed(2)} MAD</td>
                                        <td>{row.dateDepense ?? '—'}</td>
                                        <td>
                                            {row.periodeDebut && row.periodeFin
                                                ? `${row.periodeDebut} → ${row.periodeFin}`
                                                : '—'}
                                        </td>
                                        {canAudit && (
                                            <td className="text-normal-case">
                                                <OperationDateCell
                                                    createdAt={row.createdAt}
                                                    updatedAt={row.updatedAt}
                                                    wasEdited={row.wasEdited}
                                                />
                                            </td>
                                        )}
                                        <td>
                                            {row.receiptsCount > 0 ? (
                                                <span>
                                                    <i className="ti ti-paperclip me-1" />
                                                    {row.receiptsCount}
                                                </span>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td>
                                            <RowActions view={row.showUrl}>
                                                <RowActionItem icon="ti-edit" onClick={() => openEditDepense(row)}>
                                                    Modifier
                                                </RowActionItem>
                                            </RowActions>
                                        </td>
                                    </tr>
                                ))}
                            </DataTable>
                            <Pagination paginator={paiementsProf} />
                        </>
                    )}
                </Card>
            )}

            {tab === 'remboursements' && canViewRemboursements && remboursements && (
                <Card
                    title="Remboursements"
                    bodyClassName="p-0 py-3"
                    tools={
                        <button
                            type="button"
                            className="btn btn-primary d-flex align-items-center mb-3"
                            onClick={openCreateRemboursement}
                        >
                            <i className="ti ti-square-rounded-plus me-2" />
                            Ajouter un remboursement
                        </button>
                    }
                >
                    <TableLengthRow
                        search={<SearchInput value={filters.search} onSearch={(value) => reload({ search: value })} />}
                    />

                    {/* Ce qui est REELLEMENT sorti des caisses. Le compteur de
                        pagination ci-dessous compte les LIGNES (annulees
                        comprises, elles restent affichees pour la trace) — ce
                        total, lui, ne compte que l'argent parti. */}
                    {remboursementsTotaux && (
                        <div className="px-3 pb-3 d-flex flex-wrap align-items-center gap-3">
                            <span className="text-muted">Total rembourse :</span>
                            <span className="fw-semibold text-danger fs-16">
                                -{Number(remboursementsTotaux.montant).toFixed(2)} MAD
                            </span>
                            <span className="text-muted">
                                ({remboursementsTotaux.count}
                                {remboursementsTotaux.count > 1 ? ' remboursements' : ' remboursement'})
                            </span>
                            {remboursementsTotaux.annules > 0 && (
                                <span className="badge bg-secondary-transparent">
                                    {remboursementsTotaux.annules} annule
                                    {remboursementsTotaux.annules > 1 ? 's' : ''} (non compte
                                    {remboursementsTotaux.annules > 1 ? 's' : ''})
                                </span>
                            )}
                        </div>
                    )}

                    {remboursements.data.length === 0 ? (
                        <EmptyState title="Aucun remboursement" icon="ti ti-arrow-back-up" />
                    ) : (
                        <>
                            <DataTable
                                loading={isLoading}
                                head={
                                    <tr>
                                        <th>Référence</th>
                                        <th>Bénéficiaire</th>
                                        <th>Caisse</th>
                                        <th className="text-end">Montant</th>
                                        <th>Date</th>
                                        <th className="text-end">Action</th>
                                    </tr>
                                }
                            >
                                {remboursements.data.map((row) => (
                                    // Un remboursement annulé reste listé (les
                                    // enregistrements monétaires ne sont jamais
                                    // supprimés) mais ne doit PAS se lire comme
                                    // de l'argent sorti : sans ce traitement la
                                    // page affichait deux fois -300 MAD pour un
                                    // seul remboursement réel.
                                    <tr key={row.id} className={row.annule ? 'opacity-50' : undefined}>
                                        <td>
                                            <code>{row.reference}</code>
                                        </td>
                                        <td>{row.beneficiaire ?? '—'}</td>
                                        <td>{row.caisse ?? '—'}</td>
                                        <td className="text-end">
                                            {row.annule ? (
                                                <>
                                                    <span className="text-decoration-line-through text-muted">
                                                        -{Number(row.montant).toFixed(2)} MAD
                                                    </span>
                                                    <span className="badge bg-secondary-transparent ms-2">Annulé</span>
                                                </>
                                            ) : (
                                                <span className="fw-medium text-danger">
                                                    -{Number(row.montant).toFixed(2)} MAD
                                                </span>
                                            )}
                                        </td>
                                        <td>{row.dateRemboursement ?? '—'}</td>
                                        <td>
                                            <RowActions>
                                                <RowActionItem icon="ti-edit" onClick={() => openEditRemboursement(row)}>
                                                    Modifier
                                                </RowActionItem>
                                                {/* Annuler recredite la caisse : super-admin
                                                    uniquement, et jamais sur une ligne deja
                                                    annulee (la caisse serait recreditee deux
                                                    fois pour une seule sortie). */}
                                                {canCancelRemboursement && !row.annule && (
                                                    <RowActionItem
                                                        icon="ti-arrow-back-up"
                                                        onClick={() => setRemboursementToCancel(row)}
                                                    >
                                                        Annuler le remboursement
                                                    </RowActionItem>
                                                )}
                                            </RowActions>
                                        </td>
                                    </tr>
                                ))}
                            </DataTable>
                            <Pagination paginator={remboursements} />
                        </>
                    )}
                </Card>
            )}

            {/* « Validation des dépenses » — super-admin only (canAudit).
                Lists every expense with its approval status and the operation
                trail; the eye opens the full details. The approve/refuse
                actions are the SAME endpoints as the Dépenses tab — this tab
                only gathers them in one place for whoever validates. */}
            {tab === 'validation' && canAudit && validationDepenses && (
                <Card title="Validation des dépenses" bodyClassName="p-0 py-3">
                    <div className="px-3 pt-2">
                        <TableToolbar onReset={filterReset.reset} resetActive={filterReset.active}>
                            <div style={{ width: 220 }}>
                                <label className="form-label" htmlFor="val-f-type">
                                    Type
                                </label>
                                <SelectField
                                    id="val-f-type"
                                    options={filterTypeOptions}
                                    placeholder="Tous les types"
                                    value={filters.typeFilter}
                                    onChange={(event) => reload({ typeFilter: event.target.value })}
                                />
                            </div>
                            <div style={{ width: 200 }}>
                                <label className="form-label" htmlFor="val-f-statut">
                                    Statut
                                </label>
                                <SelectField
                                    id="val-f-statut"
                                    options={depenseStatuts.map((st) => ({ value: st, label: st }))}
                                    placeholder="Tous les statuts"
                                    value={filters.statutFilter}
                                    onChange={(event) => reload({ statutFilter: event.target.value })}
                                />
                            </div>
                            <div style={{ width: 170 }}>
                                <label className="form-label" htmlFor="val-f-du">
                                    Du
                                </label>
                                <DateField
                                    id="val-f-du"
                                    value={filters.dateFrom}
                                    onChange={(event) => reload({ dateFrom: event.target.value })}
                                />
                            </div>
                            <div style={{ width: 170 }}>
                                <label className="form-label" htmlFor="val-f-au">
                                    Au
                                </label>
                                <DateField
                                    id="val-f-au"
                                    value={filters.dateTo}
                                    onChange={(event) => reload({ dateTo: event.target.value })}
                                />
                            </div>
                        </TableToolbar>
                    </div>

                    <TableLengthRow
                        search={<SearchInput value={filters.search} onSearch={(value) => reload({ search: value })} />}
                    />

                    {validationMontantTotal !== null && (
                        <p className="fw-medium px-3 mb-3">
                            Montant total : {Number(validationMontantTotal).toFixed(2)} MAD
                            {validationEnAttenteCount > 0 && (
                                <span className="text-warning ms-3">
                                    <i className="ti ti-clock-hour-4 me-1" />
                                    En attente : {Number(validationMontantEnAttente ?? 0).toFixed(2)} MAD ({validationEnAttenteCount})
                                </span>
                            )}
                        </p>
                    )}

                    {validationDepenses.data.length === 0 ? (
                        <EmptyState title="Aucune dépense" icon="ti ti-checkup-list" />
                    ) : (
                        <>
                            <DataTable
                                loading={isLoading}
                                head={
                                    <tr>
                                        <th>Référence</th>
                                        <th>Type</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th>Date d'opération</th>
                                        <th className="text-end">Montant total</th>
                                        <th>Groupe</th>
                                        <th>Mots-clés</th>
                                        <th>Agent</th>
                                        <th className="text-end">Action</th>
                                    </tr>
                                }
                            >
                                {validationDepenses.data.map((row) => (
                                    <tr key={row.id}>
                                        <td>
                                            <code>{row.reference}</code>
                                        </td>
                                        <td>{row.typeDepense ?? '—'}</td>
                                        <td>
                                            <StatusBadge
                                                label={row.statut}
                                                variant={DEPENSE_STATUT_BADGE[row.statut] ?? 'warning'}
                                                dot
                                            />
                                            {row.isRefusee && row.motifRefus && (
                                                <div className="text-muted fs-12 text-normal-case">{row.motifRefus}</div>
                                            )}
                                        </td>
                                        <td>{row.dateDepense ?? '—'}</td>
                                        <td className="text-normal-case">
                                            <OperationDateCell
                                                createdAt={row.createdAt}
                                                updatedAt={row.updatedAt}
                                                wasEdited={row.wasEdited}
                                            />
                                        </td>
                                        <td className="text-end fw-medium">{Number(row.montant).toFixed(2)} MAD</td>
                                        <td>{row.groupNom ?? '—'}</td>
                                        <td className="text-normal-case">
                                            {row.motsCles
                                                ? row.motsCles.split(',').map((mot) => mot.trim()).filter(Boolean).map((mot) => (
                                                    <span key={mot} className="badge bg-secondary me-1">{mot}</span>
                                                ))
                                                : '—'}
                                        </td>
                                        <td>{row.agent ?? '—'}</td>
                                        <td className="text-end">
                                            <RowActions>
                                                <RowActionItem icon="ti-eye" onClick={() => setDetailsRow(row)}>
                                                    Détails
                                                </RowActionItem>
                                                {!row.isRefusee && (
                                                    <RowActionItem icon="ti-edit" onClick={() => openEditDepense(row)}>
                                                        Modifier
                                                    </RowActionItem>
                                                )}
                                                {row.isEnAttente && canApprove && (
                                                    <RowActionItem icon="ti-check" onClick={() => openDecision('approve', row)}>
                                                        Approuver
                                                    </RowActionItem>
                                                )}
                                                {row.isEnAttente && canApprove && (
                                                    <RowActionItem icon="ti-x" onClick={() => openDecision('refuse', row)}>
                                                        Refuser
                                                    </RowActionItem>
                                                )}
                                            </RowActions>
                                        </td>
                                    </tr>
                                ))}
                            </DataTable>
                            <Pagination paginator={validationDepenses} />
                        </>
                    )}
                </Card>
            )}

            {/* « Les détails de dépense » — read-only, opened by the eye on
                the Validation tab. Every field comes from the row already in
                hand, so there is no extra round-trip. */}
            <Modal
                show={detailsRow !== null}
                title="Les détails de dépense"
                onClose={() => setDetailsRow(null)}
                processing={false}
                size="lg"
                footer={
                    <button type="button" className="btn btn-primary" onClick={() => setDetailsRow(null)}>
                        Retour
                    </button>
                }
            >
                {detailsRow && (
                    <div className="row g-3">
                        <div className="col-md-6"><DetailLine label="Référence" value={detailsRow.reference} /></div>
                        <div className="col-md-6"><DetailLine label="Type" value={detailsRow.typeDepense} /></div>
                        <div className="col-md-6"><DetailLine label="Groupe" value={detailsRow.groupNom} /></div>
                        <div className="col-md-6"><DetailLine label="Méthode de paiement" value={detailsRow.methodePaiement} /></div>
                        <div className="col-md-6">
                            <DetailLine label="Montant total" value={`${Number(detailsRow.montant).toFixed(2)} MAD`} />
                        </div>
                        <div className="col-md-6"><DetailLine label="Référence de facture" value={detailsRow.referenceFacture} /></div>
                        <div className="col-md-6"><DetailLine label="Date" value={detailsRow.dateDepense} /></div>
                        {/* « Paiement prof » only — the period the payment covers. */}
                        {detailsRow.periodeDebut && detailsRow.periodeFin && (
                            <div className="col-md-6">
                                <DetailLine
                                    label="Période payée"
                                    value={`${detailsRow.periodeDebut} → ${detailsRow.periodeFin}`}
                                />
                            </div>
                        )}
                        <div className="col-md-6"><DetailLine label="Ajouté par" value={detailsRow.agent} /></div>

                        {/* The operation trail — the reason this modal is
                            super-admin only. `dateDepense` above is the date
                            the user typed; these are when it really happened. */}
                        <div className="col-md-6">
                            <DetailLine label="Date d'opération (création)" value={detailsRow.createdAt} />
                        </div>
                        <div className="col-md-6">
                            <DetailLine
                                label="Date d'opération (modification)"
                                value={detailsRow.wasEdited ? detailsRow.updatedAt : 'Jamais modifiée'}
                            />
                        </div>

                        <div className="col-md-6"><DetailLine label="Statut" value={detailsRow.statut} /></div>
                        <div className="col-md-6">
                            <DetailLine
                                label={detailsRow.isRefusee ? 'Refusée par' : 'Approuvée par'}
                                value={detailsRow.approvedBy}
                            />
                        </div>
                        {detailsRow.approvedAt && (
                            <div className="col-md-6">
                                <DetailLine label="Décision le" value={detailsRow.approvedAt} />
                            </div>
                        )}
                        {detailsRow.isRefusee && detailsRow.motifRefus && (
                            <div className="col-12"><DetailLine label="Motif du refus" value={detailsRow.motifRefus} /></div>
                        )}

                        <div className="col-12"><DetailLine label="Description" value={detailsRow.description} /></div>
                        <div className="col-12"><DetailLine label="Note" value={detailsRow.note} /></div>
                        <div className="col-12">
                            <span className="text-muted d-block fs-13">Mots-clés</span>
                            {detailsRow.motsCles
                                ? detailsRow.motsCles.split(',').map((mot) => mot.trim()).filter(Boolean).map((mot) => (
                                    <span key={mot} className="badge bg-secondary me-1 text-normal-case">{mot}</span>
                                ))
                                : <span className="fw-medium">—</span>}
                        </div>
                    </div>
                )}
            </Modal>

            {/* Dépense / Paiement prof modal — ONE form, two contracts
                (see openCreateDepense / openCreatePaiementProf). */}
            <Modal
                show={showDepenseModal}
                title={
                    profMode
                        ? editingDepense
                            ? 'Modifier le paiement prof'
                            : 'Ajouter un paiement prof'
                        : editingDepense
                            ? 'Modifier la dépense'
                            : 'Ajouter une dépense'
                }
                onClose={closeDepenseModal}
                processing={depenseForm.processing}
                size="xl"
                footer={<FormActions form="depense-form" onCancel={closeDepenseModal} processing={depenseForm.processing} />}
            >
                <form id="depense-form" onSubmit={submitDepense}>
                    {editingDepense && (
                        <div className="alert alert-warning">
                            Le montant et la caisse ne peuvent pas être modifiés après création.
                        </div>
                    )}
                    {!editingDepense && soldeActuel !== null && (
                        <div className="alert alert-info d-flex justify-content-between align-items-center">
                            <span>Solde actuel de votre caisse</span>
                            <span className="fw-semibold">{Number(soldeActuel).toFixed(2)} MAD</span>
                        </div>
                    )}
                    <div className="row">
                        <div className="col-md-4">
                            {profMode ? (
                                /* The type IS the modal — locked, so a
                                   paiement prof can never be keyed in as an
                                   ordinary dépense (and vice-versa). */
                                <div className="mb-3">
                                    <label className="form-label">Type de dépense</label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        value={paiementProfLabel}
                                        disabled
                                        readOnly
                                    />
                                </div>
                            ) : (
                                <SelectField
                                    id="d-type"
                                    label="Type de dépense"
                                    options={filterTypeOptions}
                                    placeholder="Sélectionner un type"
                                    required
                                    value={depenseForm.data.type_depense_id}
                                    onChange={(e) => depenseForm.setData('type_depense_id', e.target.value === '' ? '' : Number(e.target.value))}
                                    error={depenseForm.errors.type_depense_id}
                                />
                            )}
                        </div>
                        {/* Groupe: required on a paiement prof (a teacher is
                            always paid for a given group), absent entirely
                            from the Dépenses modal. */}
                        {profMode && (
                            <div className="col-md-4">
                                <SelectField
                                    id="d-group"
                                    label="Groupe"
                                    options={groupOptions}
                                    placeholder="Sélectionner un groupe"
                                    required
                                    value={depenseForm.data.group_id}
                                    onChange={(e) => depenseForm.setData('group_id', e.target.value === '' ? '' : Number(e.target.value))}
                                    error={depenseForm.errors.group_id}
                                />
                            </div>
                        )}
                        <div className="col-md-4">
                            {editingDepense ? (
                                <div className="d-flex justify-content-between mb-3">
                                    <span className="text-muted">Montant</span>
                                    <span className="fw-medium">{Number(depenseForm.data.montant).toFixed(2)} MAD</span>
                                </div>
                            ) : (
                                <FormField
                                    id="d-montant"
                                    label="Montant"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    value={depenseForm.data.montant}
                                    onChange={(e) => depenseForm.setData('montant', e.target.value)}
                                    error={depenseForm.errors.montant}
                                />
                            )}
                        </div>
                        <div className="col-md-4">
                            <SelectField
                                id="d-methode"
                                label="Méthode de paiement"
                                options={methodeOptions}
                                placeholder="Sélectionner une méthode"
                                required={!editingDepense}
                                value={depenseForm.data.methode_paiement}
                                onChange={(e) => depenseForm.setData('methode_paiement', e.target.value)}
                                error={depenseForm.errors.methode_paiement}
                            />
                        </div>
                        <div className="col-md-4">
                            <DateField
                                id="d-date"
                                label="Date"
                                required
                                value={depenseForm.data.date_depense}
                                onChange={(e) => depenseForm.setData('date_depense', e.target.value)}
                                error={depenseForm.errors.date_depense}
                            />
                        </div>
                        {/* Supplier invoice on a dépense; the teaching period
                            the payment covers on a paiement prof. */}
                        {profMode ? (
                            <>
                                <div className="col-md-4">
                                    <DateField
                                        id="d-periode-debut"
                                        label="Période du"
                                        required
                                        value={depenseForm.data.periode_debut}
                                        onChange={(e) => depenseForm.setData('periode_debut', e.target.value)}
                                        error={depenseForm.errors.periode_debut}
                                    />
                                </div>
                                <div className="col-md-4">
                                    <DateField
                                        id="d-periode-fin"
                                        label="Période au"
                                        required
                                        value={depenseForm.data.periode_fin}
                                        onChange={(e) => depenseForm.setData('periode_fin', e.target.value)}
                                        error={depenseForm.errors.periode_fin}
                                    />
                                </div>
                            </>
                        ) : (
                            <div className="col-md-4">
                                <FormField
                                    id="d-reference-facture"
                                    label="Référence facture fournisseur"
                                    value={depenseForm.data.reference_facture}
                                    onChange={(e) => depenseForm.setData('reference_facture', e.target.value)}
                                    error={depenseForm.errors.reference_facture}
                                />
                            </div>
                        )}
                    </div>
                    <div className="row">
                        <div className="col-md-6">
                            <TextareaField
                                id="d-description"
                                label="Description"
                                rows={2}
                                required
                                value={depenseForm.data.description}
                                onChange={(e) => depenseForm.setData('description', e.target.value)}
                                error={depenseForm.errors.description}
                            />
                        </div>
                        <div className="col-md-6">
                            <TagsInput
                                id="d-mots-cles"
                                label="Mots-clés"
                                value={depenseForm.data.mots_cles}
                                onChange={(value) => depenseForm.setData('mots_cles', value)}
                                error={depenseForm.errors.mots_cles}
                                placeholder="Taper un mot-clé puis Entrée…"
                            />
                        </div>
                    </div>
                    <TextareaField
                        id="d-note"
                        label="Note"
                        value={depenseForm.data.note}
                        onChange={(e) => depenseForm.setData('note', e.target.value)}
                        error={depenseForm.errors.note}
                    />

                    <div className="mb-3">
                        <label className="form-label" htmlFor="d-justificatifs">
                            Justificatifs
                        </label>
                        <input
                            ref={fileInputRef}
                            id="d-justificatifs"
                            type="file"
                            multiple
                            accept={justificatifMimes.map((m) => `.${m}`).join(',')}
                            className={`form-control${depenseForm.errors['justificatifs.0'] ? ' is-invalid' : ''}`}
                            onChange={handleFilesChange}
                        />
                        <div className="form-text">Formats acceptés : {justificatifMimes.join(', ')} — max {Math.round(justificatifMaxKb / 1024)} Mo</div>
                    </div>
                </form>
            </Modal>

            {/* Remboursement modal */}
            <Modal
                show={showRemboursementModal}
                title={editingRemboursement ? 'Modifier le remboursement' : 'Ajouter un remboursement'}
                onClose={closeRemboursementModal}
                processing={remboursementForm.processing}
                size="xl"
                footer={<FormActions form="remboursement-form" onCancel={closeRemboursementModal} processing={remboursementForm.processing} />}
            >
                <form id="remboursement-form" onSubmit={submitRemboursement}>
                    {editingRemboursement && (
                        <div className="alert alert-warning">
                            Le montant et la caisse ne peuvent pas être modifiés après création.
                        </div>
                    )}
                    <div className="row">
                        <div className={editingRemboursement ? 'col-12' : 'col-md-4'}>
                            <SelectField
                                id="r-beneficiaire"
                                label="Bénéficiaire"
                                options={studentOptions}
                                placeholder="Sélectionner un étudiant"
                                required
                                disabled={!!editingRemboursement}
                                value={remboursementForm.data.beneficiaire_id}
                                onChange={(e) => onBeneficiaireChange(e.target.value === '' ? '' : Number(e.target.value))}
                                error={remboursementForm.errors.beneficiaire_id}
                            />
                        </div>
                        <div className="col-md-4">
                            {editingRemboursement ? (
                                <div className="d-flex justify-content-between mb-3">
                                    <span className="text-muted">Montant</span>
                                    <span className="fw-medium text-danger">
                                        -{Number(remboursementForm.data.montant).toFixed(2)} MAD
                                    </span>
                                </div>
                            ) : (
                                <FormField
                                    id="r-montant"
                                    label="Montant"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    value={remboursementForm.data.montant}
                                    onChange={(e) => remboursementForm.setData('montant', e.target.value)}
                                    error={remboursementForm.errors.montant}
                                />
                            )}
                        </div>
                        <div className="col-md-4">
                            <DateField
                                id="r-date"
                                label="Date"
                                required
                                value={remboursementForm.data.date_remboursement}
                                onChange={(e) => remboursementForm.setData('date_remboursement', e.target.value)}
                                error={remboursementForm.errors.date_remboursement}
                            />
                        </div>
                    </div>

                    {/* Which till the cash actually leaves. Read-only once
                        recorded — the balance has already moved. */}
                    {editingRemboursement ? (
                        <div className="d-flex justify-content-between mb-3">
                            <span className="text-muted">Caisse débitée</span>
                            <span className="fw-medium">{editingRemboursement.caisse ?? '—'}</span>
                        </div>
                    ) : (
                        <div className="row">
                            <div className="col-md-8">
                                <SelectField
                                    id="r-caisse"
                                    label="Caisse à débiter"
                                    options={caisseSelectOptions}
                                    placeholder="Sélectionner une caisse"
                                    required
                                    value={remboursementForm.data.caisse_id}
                                    onChange={(e) =>
                                        remboursementForm.setData('caisse_id', e.target.value === '' ? '' : Number(e.target.value))
                                    }
                                    error={remboursementForm.errors.caisse_id}
                                />
                                <div className="form-text mt-n2 mb-3">
                                    L&apos;argent sort de cette caisse. Seules les caisses espèces du centre actif sont proposées.
                                </div>
                            </div>
                        </div>
                    )}

                    {!editingRemboursement && loadingStudentPayments && (
                        <p className="text-muted mb-3">Chargement des paiements…</p>
                    )}
                    {!editingRemboursement &&
                        !loadingStudentPayments &&
                        remboursementForm.data.beneficiaire_id !== '' &&
                        studentPayments.length === 0 && (
                            <p className="text-muted mb-3">Aucun paiement trouvé pour cet étudiant.</p>
                        )}

                    {!editingRemboursement && studentPayments.length > 0 && (
                        <div className="mb-3">
                            <label className="form-label">Paiement à rembourser</label>
                            <div className="table-responsive border rounded" style={{ maxHeight: 260, overflowY: 'auto' }}>
                                <table className="table table-sm align-middle mb-0">
                                    <thead className="table-light" style={{ position: 'sticky', top: 0, zIndex: 1 }}>
                                        <tr>
                                            <th></th>
                                            <th>Référence</th>
                                            <th>Frais</th>
                                            <th>Méthode</th>
                                            <th>Date</th>
                                            <th className="text-end">Montant</th>
                                            <th className="text-end">Remboursable</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {studentPayments.map((payment) => (
                                            <tr
                                                key={payment.id}
                                                className={remboursementForm.data.encaissement_id === payment.id ? 'table-primary' : undefined}
                                                style={{ cursor: 'pointer' }}
                                                onClick={() => selectPaymentToRefund(payment)}
                                            >
                                                <td>
                                                    <input
                                                        type="radio"
                                                        className="form-check-input"
                                                        checked={remboursementForm.data.encaissement_id === payment.id}
                                                        onChange={() => selectPaymentToRefund(payment)}
                                                    />
                                                </td>
                                                <td>
                                                    <code>{payment.reference}</code>
                                                </td>
                                                <td>
                                                    {payment.isAvance ? (
                                                        <span className="badge bg-info-transparent">Avance</span>
                                                    ) : (
                                                        (payment.feeNom ?? '—')
                                                    )}
                                                </td>
                                                <td>{payment.methode}</td>
                                                <td>{payment.date ?? '—'}</td>
                                                <td className="text-end">{Number(payment.montant).toFixed(2)} MAD</td>
                                                <td className="text-end fw-medium">
                                                    {Number(payment.montantRemboursable).toFixed(2)} MAD
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    <TextareaField
                        id="r-note"
                        label="Note"
                        value={remboursementForm.data.note}
                        onChange={(e) => remboursementForm.setData('note', e.target.value)}
                        error={remboursementForm.errors.note}
                    />
                </form>
            </Modal>

            {/* Approve / refuse a pending depense. Approving is the moment
                the till is actually debited; refusing moves no money and
                keeps the row for the audit trail. Server refusals (already
                decided, permission, no employee record) are shown inline. */}
            <ConfirmDialog
                show={decision !== null}
                title={decision?.type === 'approve' ? 'Approuver la depense' : 'Refuser la depense'}
                message={
                    decision?.type === 'approve'
                        ? 'La caisse sera debitee immediatement de ce montant.'
                        : 'Aucun montant ne sera debite. La depense restera visible comme refusee.'
                }
                recordLabel={
                    decision
                        ? Number(decision.row.montant).toFixed(2)
                          + ' MAD - '
                          + (decision.row.typeDepense ?? decision.row.reference)
                        : ''
                }
                error={decisionError}
                processing={decisionProcessing}
                onConfirm={runDecision}
                onCancel={closeDecision}
                icon={decision?.type === 'approve' ? 'ti-circle-check' : 'ti-circle-x'}
                variant={decision?.type === 'approve' ? 'primary' : 'danger'}
                confirmLabel={decision?.type === 'approve' ? 'Approuver' : 'Refuser'}
                processingLabel={decision?.type === 'approve' ? 'Approbation...' : 'Refus...'}
            >
                {decision?.type === 'refuse' && (
                    <TextareaField
                        id="dep-motif-refus"
                        label="Motif du refus (optionnel)"
                        value={motifRefus}
                        onChange={(e) => setMotifRefus(e.target.value)}
                    />
                )}
            </ConfirmDialog>

            {/* Annulation d'un remboursement (super-admin). La caisse est
                recreditee par ecriture compensatoire : la ligne reste
                listee, barree, et sort des totaux — elle n'est jamais
                supprimee (§11). Le libelle nomme la caisse creditee, parce
                que c'est un mouvement d'argent, pas un changement d'etat. */}
            <ConfirmDialog
                show={remboursementToCancel !== null}
                title="Annuler le remboursement"
                message={
                    'La caisse sera recreditee de ce montant. Le remboursement restera '
                    + 'visible, barre et marque « Annule », mais ne comptera plus dans les totaux.'
                }
                recordLabel={
                    remboursementToCancel
                        ? remboursementToCancel.reference
                          + ' - '
                          + Number(remboursementToCancel.montant).toFixed(2)
                          + ' MAD vers ' + (remboursementToCancel.caisse ?? 'la caisse')
                        : ''
                }
                processing={cancelling}
                onConfirm={() => {
                    if (remboursementToCancel === null) {
                        return;
                    }

                    setCancelling(true);
                    router.post(
                        `/backoffice/remboursements/${remboursementToCancel.id}/annuler`,
                        { motif: motifAnnulationRemboursement },
                        {
                            preserveScroll: true,
                            onFinish: () => {
                                setCancelling(false);
                                setRemboursementToCancel(null);
                                setMotifAnnulationRemboursement('');
                            },
                        },
                    );
                }}
                onCancel={() => {
                    setRemboursementToCancel(null);
                    setMotifAnnulationRemboursement('');
                }}
                icon="ti-arrow-back-up"
                confirmLabel="Annuler le remboursement"
                processingLabel="Annulation..."
            >
                <TextareaField
                    id="rmb-motif-annulation"
                    label="Motif de l'annulation (optionnel)"
                    value={motifAnnulationRemboursement}
                    onChange={(e) => setMotifAnnulationRemboursement(e.target.value)}
                />
            </ConfirmDialog>
        </BackofficeLayout>
    );
}
