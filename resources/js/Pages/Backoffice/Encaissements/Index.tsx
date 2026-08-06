import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import PageTabs from '@/Components/Navigation/PageTabs';
import { FINANCE_TABS } from '@/Config/pageTabs';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import FilterDropdown from '@/Components/Tables/FilterDropdown';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
import SearchInput from '@/Components/Tables/SearchInput';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import Modal from '@/Components/Modals/Modal';
import SelectField from '@/Components/Forms/SelectField';
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
export default function EncaissementsIndex({ encaissements, caisses, students, methodes, filters }: EncaissementsPageProps) {
    const isLoading = useInertiaLoading();
    const [showModal, setShowModal] = useState(false);
    const [editingRow, setEditingRow] = useState<EncaissementRow | null>(null);
    const [inscriptionOptions, setInscriptionOptions] = useState<SelectOption[]>([]);
    const [loadingInscriptions, setLoadingInscriptions] = useState(false);
    const [loadingFees, setLoadingFees] = useState(false);

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

    async function onStudentChange(studentId: number | '') {
        createForm.setData((previous) => ({
            ...previous,
            student_id: studentId,
            inscription_id: '',
            payment_lines: [],
        }));
        setInscriptionOptions([]);

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
                    reste: fee.reste,
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
            title="Paiements"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Paiements' }]}
            actions={
                <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                    <i className="ti ti-square-rounded-plus me-2" />
                    Enregistrer un paiement
                </button>
            }
        >
            <PageTabs tabs={FINANCE_TABS} />

            <Card
                title="Paiements"
                bodyClassName="p-0 py-3"
                tools={
                    <FilterDropdown
                        fields={[
                            {
                                name: 'caisseFilter',
                                label: 'Caisse',
                                value: filters.caisseFilter,
                                options: caisseOptions,
                                placeholder: 'Toutes les caisses',
                            },
                            {
                                name: 'methodeFilter',
                                label: 'Méthode',
                                value: filters.methodeFilter,
                                options: methodeOptions,
                                placeholder: 'Toutes les méthodes',
                            },
                        ]}
                        onApply={(values) => reload(values)}
                        onReset={() => reload({ caisseFilter: '', methodeFilter: '' })}
                    />
                }
            >
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
                    <EmptyState title="Aucun paiement" icon="ti ti-cash-banknote" />
                ) : (
                    <>
                        <DataTable
                            loading={isLoading}
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th>Étudiant</th>
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
                size="lg"
                footer={
                    <FormActions form="encaissement-create-form" onCancel={closeModal} processing={createForm.processing} submitLabel="Enregistrer" />
                }
            >
                <form id="encaissement-create-form" onSubmit={submitCreate}>
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

                    {loadingFees && <p className="text-muted">Chargement des frais…</p>}

                    {createForm.data.payment_lines.length > 0 && (
                        <div className="table-responsive mb-3">
                            <table className="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Frais</th>
                                        <th className="text-end">Restant</th>
                                        <th>Montant</th>
                                        <th>Méthode</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {createForm.data.payment_lines.map((line, index) => {
                                        // Server errors arrive keyed per submitted row
                                        // (payment_lines.<i>.montant/…) — surface them on
                                        // the row so a refused save is never silent.
                                        const rowErrors = createForm.errors as Record<string, string | undefined>;
                                        const montantError = rowErrors[`payment_lines.${index}.montant`];
                                        const dateError = rowErrors[`payment_lines.${index}.date_paiement`];

                                        return (
                                            <tr key={line.feeId}>
                                                <td>{line.nom}</td>
                                                <td className="text-end">{Number(line.reste).toFixed(2)} MAD</td>
                                                <td>
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        max={line.reste}
                                                        className={`form-control form-control-sm${montantError ? ' is-invalid' : ''}`}
                                                        value={line.montant}
                                                        onChange={(e) => setLine(index, { montant: e.target.value })}
                                                        aria-invalid={montantError ? true : undefined}
                                                    />
                                                    {montantError && <div className="text-danger fs-12 mt-1">{montantError}</div>}
                                                </td>
                                                <td>
                                                    <select
                                                        className="form-select form-select-sm"
                                                        value={line.methode}
                                                        onChange={(e) => setLine(index, { methode: e.target.value })}
                                                    >
                                                        {methodeOptions.map((m) => (
                                                            <option key={m.value} value={m.value}>
                                                                {m.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                </td>
                                                <td>
                                                    <input
                                                        type="date"
                                                        className={`form-control form-control-sm${dateError ? ' is-invalid' : ''}`}
                                                        value={line.datePaiement}
                                                        onChange={(e) => setLine(index, { datePaiement: e.target.value })}
                                                        aria-invalid={dateError ? true : undefined}
                                                    />
                                                    {dateError && <div className="text-danger fs-12 mt-1">{dateError}</div>}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                            {createForm.errors.payment_lines && (
                                <div className="text-danger small mt-1">{createForm.errors.payment_lines}</div>
                            )}
                        </div>
                    )}

                    {anyLineIsCheque && (
                        <div className="border-top pt-3 mt-2">
                            <FormField
                                id="e-numero-cheque"
                                label="Numéro de chèque"
                                required
                                value={createForm.data.numero_cheque}
                                onChange={(e) => createForm.setData('numero_cheque', e.target.value)}
                                error={createForm.errors.numero_cheque}
                            />
                            <FormField
                                id="e-banque"
                                label="Banque"
                                required
                                value={createForm.data.banque}
                                onChange={(e) => createForm.setData('banque', e.target.value)}
                                error={createForm.errors.banque}
                            />
                            <FormField
                                id="e-date-echeance-cheque"
                                label="Échéance du chèque"
                                type="date"
                                required
                                value={createForm.data.date_echeance_cheque}
                                onChange={(e) => createForm.setData('date_echeance_cheque', e.target.value)}
                                error={createForm.errors.date_echeance_cheque}
                            />
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
                footer={<FormActions form="encaissement-edit-form" onCancel={closeModal} processing={editForm.processing} submitLabel="Enregistrer" />}
            >
                {editingRow && (
                    <form id="encaissement-edit-form" onSubmit={submitEdit}>
                        <div className="alert alert-info">Le montant et la caisse ne peuvent pas être modifiés.</div>
                        <div className="d-flex justify-content-between mb-2">
                            <span className="text-muted">Montant</span>
                            <span className="fw-medium">{Number(editingRow.montant).toFixed(2)} MAD</span>
                        </div>
                        <SelectField
                            id="e-edit-methode"
                            label="Méthode"
                            options={methodeOptions}
                            required
                            value={editForm.data.methode}
                            onChange={(e) => editForm.setData('methode', e.target.value)}
                            error={editForm.errors.methode}
                        />
                        <FormField
                            id="e-edit-date"
                            label="Date de paiement"
                            type="date"
                            required
                            value={editForm.data.date_paiement}
                            onChange={(e) => editForm.setData('date_paiement', e.target.value)}
                            error={editForm.errors.date_paiement}
                        />
                        {editForm.data.methode === 'Chèque' && (
                            <>
                                <FormField
                                    id="e-edit-numero-cheque"
                                    label="Numéro de chèque"
                                    required
                                    value={editForm.data.numero_cheque}
                                    onChange={(e) => editForm.setData('numero_cheque', e.target.value)}
                                    error={editForm.errors.numero_cheque}
                                />
                                <FormField
                                    id="e-edit-banque"
                                    label="Banque"
                                    required
                                    value={editForm.data.banque}
                                    onChange={(e) => editForm.setData('banque', e.target.value)}
                                    error={editForm.errors.banque}
                                />
                                <FormField
                                    id="e-edit-date-echeance-cheque"
                                    label="Échéance du chèque"
                                    type="date"
                                    required
                                    value={editForm.data.date_echeance_cheque}
                                    onChange={(e) => editForm.setData('date_echeance_cheque', e.target.value)}
                                    error={editForm.errors.date_echeance_cheque}
                                />
                            </>
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
        </BackofficeLayout>
    );
}
