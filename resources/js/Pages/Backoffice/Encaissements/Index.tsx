import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import FilterTextInput from '@/Components/Tables/FilterTextInput';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
import SearchInput from '@/Components/Tables/SearchInput';
import Pagination from '@/Components/Tables/Pagination';
import LocalPagination from '@/Components/Tables/LocalPagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import Modal from '@/Components/Modals/Modal';
import SelectField from '@/Components/Forms/SelectField';
import DateField from '@/Components/Forms/DateField';
import FormField from '@/Components/Forms/FormField';
import TextareaField from '@/Components/Forms/TextareaField';
import FormActions from '@/Components/Forms/FormActions';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import type { EncaissementRow, EncaissementsPageProps, PaymentLine, SelectOption, UnpaidFee } from '@/Types';

interface CreateFormState {
    student_id: number | '';
    inscription_id: number | '';
    date_paiement: string;
    note: string;
    payment_lines: PaymentLine[];
    numero_cheque: string;
    banque: string;
    date_echeance_cheque: string;
}

interface EditFormState {
    methode: string;
    date_paiement: string;
    numero_cheque: string;
    banque: string;
    date_echeance_cheque: string;
    note: string;
}

interface AvanceFormState {
    student_id: number | '';
    montant: string;
    methode: string;
    date_paiement: string;
    numero_cheque: string;
    banque: string;
    date_echeance_cheque: string;
    note: string;
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
        numero_cheque: '',
        banque: '',
        date_echeance_cheque: '',
    };
}

function emptyAvanceForm(): AvanceFormState {
    return {
        student_id: '',
        montant: '',
        methode: 'Espèces',
        date_paiement: new Date().toISOString().slice(0, 10),
        numero_cheque: '',
        banque: '',
        date_echeance_cheque: '',
        note: '',
    };
}

/**
 * Replaces App\Livewire\Backoffice\Encaissements\EncaissementsIndex — same
 * cascading student→inscription→fee-lines create form (one row per unpaid
 * fee, no till picker at all since the acting employee's own till is always
 * used server-side), same edit-mode freeze on montant/caisse/student/fee
 * (only méthode/date/chèque/note are live inputs). Every discount/remaining-
 * balance figure shown here is display-only — the server independently
 * recomputes and re-validates (including the per-row max:reste cap and the
 * fee->inscription_id ownership check) on save.
 */
export default function EncaissementsIndex({ encaissements, caisses, students, methodes, banques, filters }: EncaissementsPageProps) {
    const isLoading = useInertiaLoading();
    const [showModal, setShowModal] = useState(false);
    const [editingRow, setEditingRow] = useState<EncaissementRow | null>(null);
    const [inscriptionOptions, setInscriptionOptions] = useState<SelectOption[]>([]);
    const [loadingInscriptions, setLoadingInscriptions] = useState(false);
    const [loadingFees, setLoadingFees] = useState(false);
    const [feeLinesPage, setFeeLinesPage] = useState(1);
    const FEE_LINES_PER_PAGE = 4;
    const [showAvanceModal, setShowAvanceModal] = useState(false);
    const [applyTarget, setApplyTarget] = useState<EncaissementRow | null>(null);
    const [applyInscriptionOptions, setApplyInscriptionOptions] = useState<SelectOption[]>([]);
    const [applyFeeOptions, setApplyFeeOptions] = useState<Array<SelectOption & { reste: string }>>([]);
    const [loadingApplyInscriptions, setLoadingApplyInscriptions] = useState(false);
    const [loadingApplyFees, setLoadingApplyFees] = useState(false);

    const caisseOptions: SelectOption[] = caisses.map((c) => ({ value: c.id, label: c.nom }));
    const studentOptions: SelectOption[] = students.map((s) => ({ value: s.id, label: s.nom }));
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

    function reload(nextFilters: Partial<typeof filters>) {
        router.get('/backoffice/encaissements', { ...filters, ...nextFilters, page: undefined }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function openCreate() {
        setEditingRow(null);
        setInscriptionOptions([]);
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

    function openCreateAvance() {
        avanceForm.clearErrors();
        avanceForm.setData(emptyAvanceForm());
        setShowAvanceModal(true);
    }

    function closeAvanceModal() {
        setShowAvanceModal(false);
    }

    function submitAvance(event: FormEvent) {
        event.preventDefault();
        avanceForm.post('/backoffice/avances', {
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
        }));
        setInscriptionOptions([]);
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

    const anyLineIsCheque = createForm.data.payment_lines.some((l) => l.montant !== '' && l.methode === 'Chèque');

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
            title="Encaissements"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Encaissements' }]}
            actions={
                filters.view === 'avance' ? (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreateAvance}>
                        <i className="ti ti-square-rounded-plus me-2" />
                        Enregistrer une avance
                    </button>
                ) : (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                        <i className="ti ti-square-rounded-plus me-2" />
                        Enregistrer un paiement
                    </button>
                )
            }
        >
            {/* wimschool-style view tabs — server-side read-only filters on the same list. */}
            <ul className="nav nav-tabs p-0 border-bottom rounded-0 mb-4" role="tablist">
                {[
                    { view: '', label: 'Encaissements', icon: 'ti ti-cash-banknote' },
                    { view: 'avance', label: 'Avances', icon: 'ti ti-clock-dollar' },
                    { view: 'cheque', label: 'Chèques', icon: 'ti ti-file-invoice' },
                ].map((tab) => (
                    <li className="nav-item" key={tab.view} role="presentation">
                        <button
                            type="button"
                            className={`nav-link d-inline-flex align-items-center${filters.view === tab.view ? ' active' : ''}`}
                            aria-current={filters.view === tab.view ? 'page' : undefined}
                            onClick={() => reload({ view: tab.view })}
                        >
                            <i className={`${tab.icon} me-2`} aria-hidden="true" />
                            {tab.label}
                        </button>
                    </li>
                ))}
            </ul>

            <Card title="Encaissements" bodyClassName="p-0 py-3">
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
                            </>
                        )}
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
                                        <td className="text-end fw-medium">{Number(row.montant).toFixed(2)} MAD</td>
                                        <td>
                                            <span className="badge badge-soft-info">{row.methode}</span>
                                        </td>
                                        <td>{row.datePaiement ?? '—'}</td>
                                        <td>
                                            <RowActions view={row.showUrl}>
                                                <RowActionItem icon="ti-edit" onClick={() => openEdit(row)}>
                                                    Modifier
                                                </RowActionItem>
                                            </RowActions>
                                        </td>
                                    </tr>
                                ))}
                            </DataTable>
                        )}
                        <Pagination paginator={encaissements} showJumpToPage />
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

                                                return (
                                                    <tr key={line.feeId}>
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
                                                                onChange={(e) => setLine(index, { methode: e.target.value })}
                                                            />
                                                        </td>
                                                        <td>
                                                            <DateField
                                                                id={`pl-date-${index}`}
                                                                value={line.datePaiement}
                                                                onChange={(e) => setLine(index, { datePaiement: e.target.value })}
                                                                error={dateError}
                                                            />
                                                        </td>
                                                    </tr>
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

                    {anyLineIsCheque && (
                        <div className="border-top pt-3 mt-2 row">
                            <div className="col-md-4">
                                <FormField
                                    id="e-numero-cheque"
                                    label="Numéro de chèque"
                                    required
                                    value={createForm.data.numero_cheque}
                                    onChange={(e) => createForm.setData('numero_cheque', e.target.value)}
                                    error={createForm.errors.numero_cheque}
                                />
                            </div>
                            <div className="col-md-4">
                                <FormField
                                    id="e-banque"
                                    label="Banque"
                                    required
                                    list="banques-suggestions"
                                    value={createForm.data.banque}
                                    onChange={(e) => createForm.setData('banque', e.target.value)}
                                    error={createForm.errors.banque}
                                    placeholder="ex : Attijariwafa Bank"
                                />
                            </div>
                            <div className="col-md-4">
                                <DateField
                                    id="e-date-echeance-cheque"
                                    label="Échéance du chèque"
                                    required
                                    value={createForm.data.date_echeance_cheque}
                                    onChange={(e) => createForm.setData('date_echeance_cheque', e.target.value)}
                                    error={createForm.errors.date_echeance_cheque}
                                />
                            </div>
                        </div>
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

            {/* Edit modal — only méthode/date/chèque/note are live; amount/caisse/student/fee are frozen. */}
            <Modal
                show={showModal && editingRow !== null}
                title={editingRow ? `Modifier le paiement ${editingRow.reference}` : ''}
                onClose={closeModal}
                processing={editForm.processing}
                size="lg"
                footer={<FormActions form="encaissement-edit-form" onCancel={closeModal} processing={editForm.processing} submitLabel="Enregistrer" />}
            >
                {editingRow && (
                    <form id="encaissement-edit-form" onSubmit={submitEdit}>
                        <div className="alert alert-info">Le montant et la caisse ne peuvent pas être modifiés.</div>
                        <div className="d-flex justify-content-between mb-2">
                            <span className="text-muted">Montant</span>
                            <span className="fw-medium">{Number(editingRow.montant).toFixed(2)} MAD</span>
                        </div>
                        <div className="row">
                            <div className="col-md-6">
                                <SelectField
                                    id="e-edit-methode"
                                    label="Méthode"
                                    options={methodeOptions}
                                    required
                                    value={editForm.data.methode}
                                    onChange={(e) => editForm.setData('methode', e.target.value)}
                                    error={editForm.errors.methode}
                                />
                            </div>
                            <div className="col-md-6">
                                <DateField
                                    id="e-edit-date"
                                    label="Date de paiement"
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

            {/* Avance create modal — no fee/inscription cascade: just who paid,
                how much, how, and when. Recorded with inscription_fee_id =
                null (Encaissement::isAvance()), applied to a fee later. */}
            <Modal
                show={showAvanceModal}
                title="Enregistrer une avance"
                onClose={closeAvanceModal}
                processing={avanceForm.processing}
                size="lg"
                footer={<FormActions form="avance-form" onCancel={closeAvanceModal} processing={avanceForm.processing} submitLabel="Enregistrer" />}
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
                                onChange={(e) => avanceForm.setData('student_id', e.target.value === '' ? '' : Number(e.target.value))}
                                error={avanceForm.errors.student_id}
                            />
                        </div>
                        <div className="col-md-6">
                            <FormField
                                id="av-montant"
                                label="Montant"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                value={avanceForm.data.montant}
                                onChange={(e) => avanceForm.setData('montant', e.target.value)}
                                error={avanceForm.errors.montant}
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="av-methode"
                                label="Méthode"
                                options={methodeOptions}
                                required
                                value={avanceForm.data.methode}
                                onChange={(e) => avanceForm.setData('methode', e.target.value)}
                                error={avanceForm.errors.methode}
                            />
                        </div>
                        <div className="col-md-6">
                            <DateField
                                id="av-date"
                                label="Date"
                                required
                                value={avanceForm.data.date_paiement}
                                onChange={(e) => avanceForm.setData('date_paiement', e.target.value)}
                                error={avanceForm.errors.date_paiement}
                            />
                        </div>
                    </div>
                    {avanceForm.data.methode === 'Chèque' && (
                        <div className="row">
                            <div className="col-md-4">
                                <FormField
                                    id="av-numero-cheque"
                                    label="Numéro de chèque"
                                    required
                                    value={avanceForm.data.numero_cheque}
                                    onChange={(e) => avanceForm.setData('numero_cheque', e.target.value)}
                                    error={avanceForm.errors.numero_cheque}
                                />
                            </div>
                            <div className="col-md-4">
                                <FormField
                                    id="av-banque"
                                    label="Banque"
                                    required
                                    list="banques-suggestions"
                                    value={avanceForm.data.banque}
                                    onChange={(e) => avanceForm.setData('banque', e.target.value)}
                                    error={avanceForm.errors.banque}
                                    placeholder="ex : Attijariwafa Bank"
                                />
                            </div>
                            <div className="col-md-4">
                                <DateField
                                    id="av-date-echeance-cheque"
                                    label="Échéance du chèque"
                                    required
                                    value={avanceForm.data.date_echeance_cheque}
                                    onChange={(e) => avanceForm.setData('date_echeance_cheque', e.target.value)}
                                    error={avanceForm.errors.date_echeance_cheque}
                                />
                            </div>
                        </div>
                    )}
                    <TextareaField
                        id="av-note"
                        label="Note"
                        value={avanceForm.data.note}
                        onChange={(e) => avanceForm.setData('note', e.target.value)}
                        error={avanceForm.errors.note}
                    />
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
        </BackofficeLayout>
    );
}
