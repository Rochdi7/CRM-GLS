import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
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
import FormField from '@/Components/Forms/FormField';
import SelectField from '@/Components/Forms/SelectField';
import FormActions from '@/Components/Forms/FormActions';
import StatusBadge from '@/Components/Details/StatusBadge';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import type {
    CompteCaisseForm,
    CompteCaisseRow,
    CrudPermissions,
    PaginatedData,
    SelectOption,
} from '@/Types';

interface ComptesPanelProps {
    comptes: PaginatedData<CompteCaisseRow>;
    /** Creatable kinds — "Externe" only; see GetComptesCaisse::CREATABLE_TYPES. */
    compteTypes: string[];
    /** Every kind the tab can show, for the filter. */
    compteTypeFilters: string[];
    compteEtablissements: { id: number; nom: string }[];
    permissions: CrudPermissions;
    filters: { compteSearch: string; compteTypeFilter: string };
    /** Kept in sync with the parent so the toolbar reflects the URL. */
    onFilter: (next: Partial<{ compteSearch: string; compteTypeFilter: string }>) => void;
}

const TYPE_BADGE: Record<string, 'primary' | 'info' | 'warning' | 'secondary' | 'success'> = {
    'Caissière': 'primary',
    TPE: 'info',
    'Chèque': 'warning',
    Virement: 'success',
    Externe: 'secondary',
};

/**
 * « Comptes de caisse » — every account money sits in, one flat list,
 * newest first (the legacy screen's layout):
 *
 *  - employee tills ("Caissière", provisioned with the employee) and
 *    "Externe" cash accounts, the only kind created here;
 *  - the centres' TPE / Chèque / Virement accounts (`compteMethode: true`),
 *    provisioned with the centre — real rows with a ledger-kept solde, so
 *    they open like any account but are never edited or deleted here.
 *
 * Super-admin only in practice: `cash-accounts.*` is absent from every role
 * in PermissionRegistry::matrix(). The `permissions` prop is UI convenience
 * only (CLAUDE.md §5) — CaisseController re-checks every mutation.
 *
 * ⚠ No « Centre » column/filter here on purpose: unlike the CRUD list pages
 * covered by the centerLocked rule, this tab is deliberately NOT scoped to
 * the active center — its whole point is the global view of where the money
 * is, and only `centers.access-all` users reach it anyway.
 */
export default function ComptesPanel({
    comptes,
    compteTypes,
    compteTypeFilters,
    compteEtablissements,
    permissions,
    filters,
    onFilter,
}: ComptesPanelProps) {
    const isLoading = useInertiaLoading();
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<CompteCaisseRow | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<CompteCaisseRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);

    const defaultType = compteTypes[0] ?? '';
    const form = useForm<CompteCaisseForm>({ nom: '', type: defaultType, etablissement_id: '' });

    const typeOptions: SelectOption[] = compteTypes.map((t) => ({ value: t, label: t }));
    const typeFilterOptions: SelectOption[] = compteTypeFilters.map((t) => ({ value: t, label: t }));
    const etablissementOptions: SelectOption[] = compteEtablissements.map((e) => ({ value: e.id, label: e.nom }));

    function openCreate() {
        form.setData({ nom: '', type: defaultType, etablissement_id: '' });
        form.clearErrors();
        setEditing(null);
        setShowModal(true);
    }

    function openEdit(row: CompteCaisseRow) {
        form.setData({
            nom: row.nom,
            type: row.type,
            etablissement_id: compteEtablissements.find((e) => e.nom === row.centre)?.id ?? '',
        });
        form.clearErrors();
        setEditing(row);
        setShowModal(true);
    }

    function closeModal() {
        setShowModal(false);
        setEditing(null);
        form.clearErrors();
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => closeModal() };

        if (editing) {
            // UpdateCaisseRequest takes neither `type` nor `solde` — the type
            // is frozen at creation and a balance only moves through
            // CaisseLedger. Not sending them keeps the intent explicit.
            form.transform(({ nom, etablissement_id }) => ({
                nom,
                etablissement_id,
                statut: editing.statut,
            }));
            form.put(`/backoffice/caisses/${editing.id}`, {
                ...options,
                onFinish: () => form.transform((data) => data),
            });

            return;
        }

        form.post('/backoffice/caisses', options);
    }

    function confirmDelete() {
        if (!deleteTarget?.id) {
            return;
        }

        setDeleting(true);
        setDeleteError(undefined);

        // A refusal (movements, non-zero balance, not an Externe account)
        // comes back as a `delete` field error so the dialog stays open and
        // shows it inline.
        router.delete(`/backoffice/caisses/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteTarget(null),
            onError: (errors) => setDeleteError(errors.delete ?? 'Suppression impossible.'),
            onFinish: () => setDeleting(false),
        });
    }

    return (
        <Card bodyClassName="p-0 py-3">
            <div className="px-3 pt-2">
                <TableToolbar
                    actions={
                        permissions.create ? (
                            <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                                <i className="ti ti-square-rounded-plus me-2" />
                                Ajouter
                            </button>
                        ) : undefined
                    }
                >
                    <div style={{ width: 260 }}>
                        <label className="form-label" htmlFor="cc-f-type">
                            Type
                        </label>
                        <SelectField
                            id="cc-f-type"
                            options={typeFilterOptions}
                            placeholder="Choisir un type"
                            value={filters.compteTypeFilter}
                            onChange={(event) => onFilter({ compteTypeFilter: event.target.value })}
                        />
                    </div>
                </TableToolbar>
            </div>

            <TableLengthRow
                search={<SearchInput value={filters.compteSearch} onSearch={(value) => onFilter({ compteSearch: value })} />}
            />

            {comptes.data.length === 0 ? (
                <EmptyState title="Aucun compte de caisse" icon="ti ti-cash" />
            ) : (
                <>
                    <DataTable
                        loading={isLoading}
                        head={
                            <tr>
                                <th>Désignation</th>
                                <th>Type</th>
                                <th className="text-end">Encaissments</th>
                                <th className="text-end">Dépenses</th>
                                <th className="text-end">Solde</th>
                                <th>Date d'ajout</th>
                                <th className="text-end">Action</th>
                            </tr>
                        }
                    >
                        {comptes.data.map((row) => (
                            <tr key={`caisse-${row.id}`}>
                                <td>{row.nom}</td>
                                <td>
                                    <StatusBadge label={row.type} variant={TYPE_BADGE[row.type] ?? 'secondary'} />
                                </td>
                                <td className="text-end text-success fw-medium">{Number(row.encaissements).toFixed(2)} DH</td>
                                <td className="text-end text-danger fw-medium">{Number(row.depenses).toFixed(2)} DH</td>
                                <td className="text-end fw-medium">{Number(row.solde).toFixed(2)} DH</td>
                                <td>{row.dateAjout ?? '—'}</td>
                                <td>
                                    {/* A centre's method account is provisioned,
                                        not managed: open only. Only an Externe
                                        account is ever deletable — the server
                                        refuses the rest too, this just hides the
                                        dead affordance. */}
                                    <RowActions view={row.showUrl}>
                                        {permissions.update && !row.compteMethode && (
                                            <RowActionItem icon="ti-edit" onClick={() => openEdit(row)}>
                                                Modifier
                                            </RowActionItem>
                                        )}
                                        {permissions.delete && row.type === 'Externe' && (
                                            <RowActionItem icon="ti-trash" onClick={() => setDeleteTarget(row)}>
                                                Supprimer
                                            </RowActionItem>
                                        )}
                                    </RowActions>
                                </td>
                            </tr>
                        ))}
                    </DataTable>
                    <Pagination paginator={comptes} />
                </>
            )}

            <Modal
                show={showModal}
                title={editing ? 'Modifier le compte de caisse' : 'Ajouter un compte de caisse'}
                onClose={closeModal}
                processing={form.processing}
                footer={<FormActions form="compte-caisse-form" onCancel={closeModal} processing={form.processing} submitLabel="Enregistrer" cancelLabel="Fermer" />}
            >
                <form id="compte-caisse-form" onSubmit={submit}>
                    {editing ? (
                        <SelectField
                            id="cc-type-ro"
                            label="Type"
                            options={[{ value: editing.type, label: editing.type }]}
                            required
                            disabled
                            value={editing.type}
                            onChange={() => undefined}
                        />
                    ) : (
                        <SelectField
                            id="cc-type"
                            label="Type"
                            options={typeOptions}
                            placeholder="Choisir un type"
                            required
                            value={form.data.type}
                            onChange={(e) => form.setData('type', e.target.value)}
                            error={form.errors.type}
                        />
                    )}

                    <FormField
                        id="cc-nom"
                        label="Nom"
                        required
                        placeholder="Nom"
                        value={form.data.nom}
                        onChange={(e) => form.setData('nom', e.target.value)}
                        error={form.errors.nom}
                    />

                    <SelectField
                        id="cc-etablissement"
                        label="Centre"
                        options={etablissementOptions}
                        placeholder="Tous les centres"
                        value={form.data.etablissement_id}
                        onChange={(e) => form.setData('etablissement_id', e.target.value === '' ? '' : Number(e.target.value))}
                        error={form.errors.etablissement_id}
                    />
                </form>
            </Modal>

            <ConfirmDialog
                show={deleteTarget !== null}
                title="Supprimer le compte de caisse"
                message="Un compte ne peut être supprimé que s'il est encore vide — sinon désactivez-le."
                recordLabel={deleteTarget ? `${deleteTarget.nom} — ${deleteTarget.type}` : ''}
                error={deleteError}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => {
                    setDeleteTarget(null);
                    setDeleteError(undefined);
                }}
            />
        </Card>
    );
}
