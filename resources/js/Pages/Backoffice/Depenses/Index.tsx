import { router, useForm } from '@inertiajs/react';
import { useRef, useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import SearchInput from '@/Components/Tables/SearchInput';
import Pagination from '@/Components/Tables/Pagination';
import RowActions from '@/Components/Tables/RowActions';
import Modal from '@/Components/Modals/Modal';
import SelectField from '@/Components/Forms/SelectField';
import FormField from '@/Components/Forms/FormField';
import TextareaField from '@/Components/Forms/TextareaField';
import FormActions from '@/Components/Forms/FormActions';
import type { DepenseRow, DepensesPageProps, RemboursementRow, SelectOption } from '@/Types';

type Tab = 'depenses' | 'remboursements';

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
    caisse_id: number | '';
    montant: string;
    date_remboursement: string;
    motif: string;
    note: string;
}

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
        caisse_id: '',
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
    depenses,
    montantTotal,
    typesDepenses,
    groups,
    methodes,
    justificatifMimes,
    justificatifMaxKb,
    remboursements,
    students,
    filters,
}: DepensesPageProps) {
    const initialTab: Tab = new URLSearchParams(window.location.search).get('tab') === 'remboursements' && canViewRemboursements
        ? 'remboursements'
        : canViewDepenses
            ? 'depenses'
            : 'remboursements';
    const [tab, setTab] = useState<Tab>(initialTab);

    const [showDepenseModal, setShowDepenseModal] = useState(false);
    const [editingDepense, setEditingDepense] = useState<DepenseRow | null>(null);
    const [showRemboursementModal, setShowRemboursementModal] = useState(false);
    const [editingRemboursement, setEditingRemboursement] = useState<RemboursementRow | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const typeOptions: SelectOption[] = typesDepenses.map((t) => ({ value: t.id, label: t.nom }));
    const groupOptions: SelectOption[] = groups.map((g) => ({ value: g.id, label: g.nom }));
    const methodeOptions: SelectOption[] = methodes.map((m) => ({ value: m, label: m }));
    const studentOptions: SelectOption[] = students.map((s) => ({ value: s.id, label: s.nom }));

    const depenseForm = useForm<DepenseFormState>(emptyDepenseForm());
    const remboursementForm = useForm<RemboursementFormState>(emptyRemboursementForm());

    function reload(nextFilters: Partial<typeof filters>) {
        router.get('/backoffice/depenses', { ...filters, ...nextFilters, page: undefined, tab }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function switchTab(next: Tab) {
        setTab(next);
        router.get('/backoffice/depenses', { ...filters, tab: next }, { preserveState: true, preserveScroll: true, replace: true });
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
        setShowRemboursementModal(true);
    }

    function openEditRemboursement(row: RemboursementRow) {
        setEditingRemboursement(row);
        remboursementForm.clearErrors();
        remboursementForm.setData({
            beneficiaire_id: row.beneficiaireId ?? '',
            caisse_id: row.caisseId ?? '',
            montant: row.montant,
            date_remboursement: row.dateRemboursement ?? '',
            motif: row.motif ?? '',
            note: row.note ?? '',
        });
        setShowRemboursementModal(true);
    }

    function closeRemboursementModal() {
        setShowRemboursementModal(false);
        setEditingRemboursement(null);
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
            <ul className="nav nav-tabs nav-tabs-solid mb-4" role="tablist">
                {canViewDepenses && (
                    <li className="nav-item">
                        <button
                            type="button"
                            className={`nav-link${tab === 'depenses' ? ' active' : ''}`}
                            onClick={() => switchTab('depenses')}
                        >
                            <i className="ti ti-receipt me-1" />
                            Dépenses
                        </button>
                    </li>
                )}
                {canViewRemboursements && (
                    <li className="nav-item">
                        <button
                            type="button"
                            className={`nav-link${tab === 'remboursements' ? ' active' : ''}`}
                            onClick={() => switchTab('remboursements')}
                        >
                            <i className="ti ti-arrow-back-up me-1" />
                            Remboursements
                        </button>
                    </li>
                )}
            </ul>

            {tab === 'depenses' && canViewDepenses && depenses && (
                <Card
                    title="Dépenses"
                    tools={
                        <button type="button" className="btn btn-primary" onClick={openCreateDepense}>
                            <i className="ti ti-plus me-1" />
                            Ajouter une dépense
                        </button>
                    }
                >
                    <TableToolbar
                        search={<SearchInput value={filters.search} onSearch={(value) => reload({ search: value })} />}
                    >
                        <SelectField
                            id="d-type-filter"
                            label="Type"
                            options={typeOptions}
                            placeholder="Tous les types"
                            value={filters.typeFilter}
                            onChange={(e) => reload({ typeFilter: e.target.value })}
                        />
                        <div style={{ width: 160 }}>
                            <label className="form-label" htmlFor="d-date-from">
                                Du
                            </label>
                            <input
                                id="d-date-from"
                                type="date"
                                className="form-control"
                                value={filters.dateFrom}
                                onChange={(e) => reload({ dateFrom: e.target.value })}
                            />
                        </div>
                        <div style={{ width: 160 }}>
                            <label className="form-label" htmlFor="d-date-to">
                                Au
                            </label>
                            <input
                                id="d-date-to"
                                type="date"
                                className="form-control"
                                value={filters.dateTo}
                                onChange={(e) => reload({ dateTo: e.target.value })}
                            />
                        </div>
                    </TableToolbar>

                    {montantTotal !== null && (
                        <p className="fw-medium mb-3">Montant total : {Number(montantTotal).toFixed(2)} MAD</p>
                    )}

                    {depenses.data.length === 0 ? (
                        <EmptyState title="Aucune dépense" icon="ti ti-receipt" />
                    ) : (
                        <>
                            <DataTable
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
                                                <li>
                                                    <button
                                                        type="button"
                                                        className="dropdown-item rounded-1 w-100 text-start border-0 bg-transparent"
                                                        onClick={() => openEditDepense(row)}
                                                    >
                                                        <i className="ti ti-edit me-2" />
                                                        Modifier
                                                    </button>
                                                </li>
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

            {tab === 'remboursements' && canViewRemboursements && remboursements && (
                <Card
                    title="Remboursements"
                    tools={
                        <button type="button" className="btn btn-primary" onClick={openCreateRemboursement}>
                            <i className="ti ti-plus me-1" />
                            Ajouter un remboursement
                        </button>
                    }
                >
                    <TableToolbar
                        search={<SearchInput value={filters.search} onSearch={(value) => reload({ search: value })} />}
                    />

                    {remboursements.data.length === 0 ? (
                        <EmptyState title="Aucun remboursement" icon="ti ti-arrow-back-up" />
                    ) : (
                        <>
                            <DataTable
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
                                                <li>
                                                    <button
                                                        type="button"
                                                        className="dropdown-item rounded-1 w-100 text-start border-0 bg-transparent"
                                                        onClick={() => openEditRemboursement(row)}
                                                    >
                                                        <i className="ti ti-edit me-2" />
                                                        Modifier
                                                    </button>
                                                </li>
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

            {/* Dépense modal */}
            <Modal
                show={showDepenseModal}
                title={editingDepense ? 'Modifier la dépense' : 'Ajouter une dépense'}
                onClose={closeDepenseModal}
                processing={depenseForm.processing}
                size="lg"
                footer={<FormActions onCancel={closeDepenseModal} processing={depenseForm.processing} />}
            >
                <form id="depense-form" onSubmit={submitDepense}>
                    {editingDepense && (
                        <div className="alert alert-warning">
                            Le montant et la caisse ne peuvent pas être modifiés après création.
                        </div>
                    )}
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
                    <SelectField
                        id="d-group"
                        label="Groupe (optionnel)"
                        options={groupOptions}
                        placeholder="Aucun groupe"
                        value={depenseForm.data.group_id}
                        onChange={(e) => depenseForm.setData('group_id', e.target.value === '' ? '' : Number(e.target.value))}
                        error={depenseForm.errors.group_id}
                    />
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
                    <FormField
                        id="d-date"
                        label="Date"
                        type="date"
                        required
                        value={depenseForm.data.date_depense}
                        onChange={(e) => depenseForm.setData('date_depense', e.target.value)}
                        error={depenseForm.errors.date_depense}
                    />
                    <FormField
                        id="d-reference-facture"
                        label="Référence facture fournisseur"
                        value={depenseForm.data.reference_facture}
                        onChange={(e) => depenseForm.setData('reference_facture', e.target.value)}
                        error={depenseForm.errors.reference_facture}
                    />
                    <TextareaField
                        id="d-description"
                        label="Description"
                        value={depenseForm.data.description}
                        onChange={(e) => depenseForm.setData('description', e.target.value)}
                        error={depenseForm.errors.description}
                    />
                    <FormField
                        id="d-mots-cles"
                        label="Mots-clés (séparés par des virgules)"
                        value={depenseForm.data.mots_cles}
                        onChange={(e) => depenseForm.setData('mots_cles', e.target.value)}
                        error={depenseForm.errors.mots_cles}
                    />
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
                footer={<FormActions onCancel={closeRemboursementModal} processing={remboursementForm.processing} />}
            >
                <form id="remboursement-form" onSubmit={submitRemboursement}>
                    {editingRemboursement && (
                        <div className="alert alert-warning">
                            Le montant et la caisse ne peuvent pas être modifiés après création.
                        </div>
                    )}
                    <SelectField
                        id="r-beneficiaire"
                        label="Bénéficiaire"
                        options={studentOptions}
                        placeholder="Sélectionner un étudiant"
                        required
                        disabled={!!editingRemboursement}
                        value={remboursementForm.data.beneficiaire_id}
                        onChange={(e) => remboursementForm.setData('beneficiaire_id', e.target.value === '' ? '' : Number(e.target.value))}
                        error={remboursementForm.errors.beneficiaire_id}
                    />
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
                    <FormField
                        id="r-date"
                        label="Date"
                        type="date"
                        required
                        value={remboursementForm.data.date_remboursement}
                        onChange={(e) => remboursementForm.setData('date_remboursement', e.target.value)}
                        error={remboursementForm.errors.date_remboursement}
                    />
                    <FormField
                        id="r-motif"
                        label="Motif"
                        value={remboursementForm.data.motif}
                        onChange={(e) => remboursementForm.setData('motif', e.target.value)}
                        error={remboursementForm.errors.motif}
                    />
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
