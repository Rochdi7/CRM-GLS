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
import SelectField from '@/Components/Forms/SelectField';
import DateField from '@/Components/Forms/DateField';
import FormField from '@/Components/Forms/FormField';
import TextareaField from '@/Components/Forms/TextareaField';
import TagsInput from '@/Components/Forms/TagsInput';
import FormActions from '@/Components/Forms/FormActions';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import type { DepenseRow, DepensesPageProps, EncaissementFormOption, RemboursementRow, SelectOption, SharedProps } from '@/Types';

type Tab = 'depenses' | 'paiements-prof' | 'remboursements';

interface DepenseFormState {
    type_depense_id: number | '';
    caisse_id: number | '';
    group_id: number | '';
    montant: string;
    methode_paiement: string;
    date_depense: string;
    reference_facture: string;
    description: string;
    mots_cles: string;
    note: string;
    justificatifs: File[];
}

interface RemboursementFormState {
    beneficiaire_id: number | '';
    encaissement_id: number | '';
    montant: string;
    date_remboursement: string;
    motif: string;
    note: string;
}

/** Local list — the controller sends no perPageOptions prop (default perPage is 15, unclamped server-side). */
const PER_PAGE_OPTIONS = [15, 25, 50, 100];

function emptyDepenseForm(): DepenseFormState {
    return {
        type_depense_id: '',
        caisse_id: '',
        group_id: '',
        montant: '',
        methode_paiement: '',
        date_depense: new Date().toISOString().slice(0, 10),
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
        montant: '',
        date_remboursement: new Date().toISOString().slice(0, 10),
        motif: '',
        note: '',
    };
}

/**
 * "Gestion des dépenses" — replaces the Livewire tabbed page (Dépenses +
 * Remboursements). Only montant/caisse_id (and beneficiaire_id for refunds)
 * are frozen after creation — everything else stays editable, matching
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
    typesDepenses,
    paiementProfTypeId,
    groups,
    methodes,
    justificatifMimes,
    justificatifMaxKb,
    remboursements,
    students,
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
            : canViewDepenses
                ? 'depenses'
                : 'remboursements';
    const [tab, setTab] = useState<Tab>(initialTab);

    const [showDepenseModal, setShowDepenseModal] = useState(false);
    const [editingDepense, setEditingDepense] = useState<DepenseRow | null>(null);
    const [showRemboursementModal, setShowRemboursementModal] = useState(false);
    const [editingRemboursement, setEditingRemboursement] = useState<RemboursementRow | null>(null);
    const [studentPayments, setStudentPayments] = useState<EncaissementFormOption[]>([]);
    const [loadingStudentPayments, setLoadingStudentPayments] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const typeOptions: SelectOption[] = typesDepenses.map((t) => ({ value: t.id, label: t.nom }));
    // The Dépenses table excludes "Paiement prof", so filtering by it there
    // could only ever return nothing — drop it from that filter only.
    const filterTypeOptions: SelectOption[] = typeOptions.filter((o) => o.value !== paiementProfTypeId);
    const groupOptions: SelectOption[] = groups.map((g) => ({ value: g.id, label: g.nom }));
    const methodeOptions: SelectOption[] = methodes.map((m) => ({ value: m, label: m }));
    const studentOptions: SelectOption[] = students.map((s) => ({ value: s.id, label: s.nom }));

    const depenseForm = useForm<DepenseFormState>(emptyDepenseForm());
    const remboursementForm = useForm<RemboursementFormState>(emptyRemboursementForm());

    function reload(nextFilters: Partial<typeof filters>) {
        router.get('/backoffice/depenses', { ...filters, ...nextFilters, page: undefined, pageProf: undefined, tab }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function switchTab(next: Tab) {
        setTab(next);
        router.get('/backoffice/depenses', { ...filters, page: undefined, pageProf: undefined, tab: next }, { preserveState: true, preserveScroll: true, replace: true });
    }

    // --- Dépenses ---

    function openCreateDepense() {
        setEditingDepense(null);
        depenseForm.clearErrors();
        depenseForm.setData(emptyDepenseForm());
        setShowDepenseModal(true);
    }

    function openEditDepense(row: DepenseRow) {
        setEditingDepense(row);
        depenseForm.clearErrors();
        depenseForm.setData({
            type_depense_id: row.typeDepenseId ?? '',
            caisse_id: row.caisseId ?? '',
            group_id: row.groupId ?? '',
            montant: row.montant,
            methode_paiement: row.methodePaiement ?? '',
            date_depense: row.dateDepense ?? '',
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

    function submitDepense(event: FormEvent) {
        event.preventDefault();

        if (editingDepense) {
            depenseForm.transform((data) => ({ ...data, _method: 'put' }));
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

        depenseForm.post('/backoffice/depenses', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeDepenseModal(),
        });
    }

    // --- Remboursements ---

    function openCreateRemboursement() {
        setEditingRemboursement(null);
        remboursementForm.clearErrors();
        remboursementForm.setData(emptyRemboursementForm());
        setStudentPayments([]);
        setShowRemboursementModal(true);
    }

    function openEditRemboursement(row: RemboursementRow) {
        setEditingRemboursement(row);
        remboursementForm.clearErrors();
        remboursementForm.setData({
            beneficiaire_id: row.beneficiaireId ?? '',
            encaissement_id: '',
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
        const dejaRembourse = Number(payment.dejaRembourse);
        const resteRemboursable = Math.max(0, Number(payment.montant) - dejaRembourse);

        remboursementForm.setData((previous) => ({
            ...previous,
            encaissement_id: payment.id,
            montant: resteRemboursable.toFixed(2),
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
                        <TableToolbar>
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
                        perPage={filters.perPage}
                        perPageOptions={PER_PAGE_OPTIONS}
                        onPerPageChange={(perPage) => reload({ perPage })}
                        search={<SearchInput value={filters.search} onSearch={(value) => reload({ search: value })} />}
                    />

                    {montantTotal !== null && (
                        <p className="fw-medium px-3 mb-3">Montant total : {Number(montantTotal).toFixed(2)} MAD</p>
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
                                        <th>Date</th>
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
                                        <td>{row.dateDepense ?? '—'}</td>
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
                            <Pagination paginator={depenses} showJumpToPage />
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
                            onClick={openCreateDepense}
                        >
                            <i className="ti ti-square-rounded-plus me-2" />
                            Ajouter une dépense
                        </button>
                    }
                >
                    <div className="px-3 pt-2">
                        <TableToolbar>
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
                        perPage={filters.perPage}
                        perPageOptions={PER_PAGE_OPTIONS}
                        onPerPageChange={(perPage) => reload({ perPage })}
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
                                        <th>Caisse</th>
                                        <th className="text-end">Montant</th>
                                        <th>Date</th>
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
                                        <td>{row.caisse ?? '—'}</td>
                                        <td className="text-end fw-medium">{Number(row.montant).toFixed(2)} MAD</td>
                                        <td>{row.dateDepense ?? '—'}</td>
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
                            <Pagination paginator={paiementsProf} showJumpToPage />
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
                                    <tr key={row.id}>
                                        <td>
                                            <code>{row.reference}</code>
                                        </td>
                                        <td>{row.beneficiaire ?? '—'}</td>
                                        <td>{row.caisse ?? '—'}</td>
                                        <td className="text-end fw-medium text-danger">
                                            -{Number(row.montant).toFixed(2)} MAD
                                        </td>
                                        <td>{row.dateRemboursement ?? '—'}</td>
                                        <td>
                                            <RowActions>
                                                <RowActionItem icon="ti-edit" onClick={() => openEditRemboursement(row)}>
                                                    Modifier
                                                </RowActionItem>
                                            </RowActions>
                                        </td>
                                    </tr>
                                ))}
                            </DataTable>
                            <Pagination paginator={remboursements} showJumpToPage />
                        </>
                    )}
                </Card>
            )}

            {/* Dépense modal */}
            <Modal
                show={showDepenseModal}
                title={editingDepense ? 'Modifier la dépense' : 'Ajouter une dépense'}
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
                            <SelectField
                                id="d-type"
                                label="Type de dépense"
                                options={typeOptions}
                                placeholder="Sélectionner un type"
                                required
                                value={depenseForm.data.type_depense_id}
                                onChange={(e) => depenseForm.setData('type_depense_id', e.target.value === '' ? '' : Number(e.target.value))}
                                error={depenseForm.errors.type_depense_id}
                            />
                        </div>
                        <div className="col-md-4">
                            <SelectField
                                id="d-group"
                                label="Groupe (optionnel)"
                                options={groupOptions}
                                placeholder="Aucun groupe"
                                value={depenseForm.data.group_id}
                                onChange={(e) => depenseForm.setData('group_id', e.target.value === '' ? '' : Number(e.target.value))}
                                error={depenseForm.errors.group_id}
                            />
                        </div>
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
                        <div className="col-md-4">
                            <FormField
                                id="d-reference-facture"
                                label="Référence facture fournisseur"
                                value={depenseForm.data.reference_facture}
                                onChange={(e) => depenseForm.setData('reference_facture', e.target.value)}
                                error={depenseForm.errors.reference_facture}
                            />
                        </div>
                    </div>
                    <div className="row">
                        <div className="col-md-6">
                            <TextareaField
                                id="d-description"
                                label="Description"
                                rows={2}
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
                                                <td>{payment.feeNom ?? '—'}</td>
                                                <td>{payment.methode}</td>
                                                <td>{payment.date ?? '—'}</td>
                                                <td className="text-end fw-medium">{Number(payment.montant).toFixed(2)} MAD</td>
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
        </BackofficeLayout>
    );
}
