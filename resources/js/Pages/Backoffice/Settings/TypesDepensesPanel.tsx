import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import FormField from '@/Components/Forms/FormField';
import SelectField from '@/Components/Forms/SelectField';
import FormActions from '@/Components/Forms/FormActions';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import StatusBadge from '@/Components/Details/StatusBadge';
import Pagination from '@/Components/Tables/Pagination';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
import SearchInput from '@/Components/Tables/SearchInput';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import type { CrudPermissions, PaginatedData, SelectOption, TypeDepenseForm, TypeDepenseRow } from '@/Types';

interface TypesDepensesPanelProps {
    types: PaginatedData<TypeDepenseRow>;
    filters: { search: string };
    permissions: CrudPermissions;
}

const STATUT_OPTIONS: SelectOption[] = [
    { value: 'Actif', label: 'Actif' },
    { value: 'Inactif', label: 'Inactif' },
];

const EMPTY_FORM: TypeDepenseForm = { nom: '', statut: 'Actif' };

/**
 * Types de dépenses — a Paramètres tab since 02/11/2026 (it was its own
 * page, linked as the last tab of Gestion des dépenses). It is a catalog
 * like Banques or Frais, so it lives with them; the mutations still go to
 * the `backoffice.types-depenses.*` endpoints (TypeDepenseController), which
 * redirect back here. is_system rows never show edit/delete controls —
 * visual-only, the controller's unconditional guard is authoritative.
 */
export default function TypesDepensesPanel({ types, filters, permissions }: TypesDepensesPanelProps) {
    const [showModal, setShowModal] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<TypeDepenseRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);

    const form = useForm<TypeDepenseForm>(EMPTY_FORM);

    function handleSearch(search: string) {
        router.get(
            '/backoffice/settings',
            { tab: 'types-depenses', search },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function openCreate() {
        form.reset();
        form.clearErrors();
        setEditingId(null);
        setShowModal(true);
    }

    function openEdit(row: TypeDepenseRow) {
        form.setData({ nom: row.nom, statut: row.statut });
        form.clearErrors();
        setEditingId(row.id);
        setShowModal(true);
    }

    function closeModal() {
        setShowModal(false);
        setEditingId(null);
        form.reset();
        form.clearErrors();
    }

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => closeModal() };

        if (editingId) {
            form.put(`/backoffice/types-depenses/${editingId}`, options);
        } else {
            form.post('/backoffice/types-depenses', options);
        }
    }

    function confirmDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        setDeleteError(undefined);

        form.transform(() => ({}));
        form.delete(`/backoffice/types-depenses/${deleteTarget.id}`, {
            preserveScroll: true,
            // Reset the transform so a later create/update on this shared
            // form doesn't submit an empty payload (Phase 12 UX fix).
            onFinish: () => form.transform((data) => data),
            onSuccess: () => {
                setDeleteTarget(null);
                setDeleting(false);
            },
            onError: (errors: Record<string, string>) => {
                setDeleteError(errors.delete ?? 'Suppression impossible.');
                setDeleting(false);
            },
        });
    }

    return (
        <div>
            <div className="d-flex align-items-center justify-content-between flex-wrap mb-3">
                <h5 className="mb-0">Types de dépenses</h5>
                {permissions.create && (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                        <i className="ti ti-square-rounded-plus me-2" />
                        Ajouter un type de dépense
                    </button>
                )}
            </div>

            <TableLengthRow search={<SearchInput value={filters.search} onSearch={handleSearch} placeholder="Rechercher" />} />

            <RelatedRecordsTable
                isEmpty={types.data.length === 0}
                emptyTitle="Aucun type de dépense pour le moment"
                emptyIcon="ti ti-receipt-tax"
                head={
                    <tr>
                        <th>Nom</th>
                        <th>Dépenses</th>
                        <th>Statut</th>
                        <th className="text-end">Action</th>
                    </tr>
                }
            >
                {types.data.map((row) => (
                    <tr key={row.id}>
                        <td className="fw-medium">
                            {row.nom}
                            {row.isSystem && (
                                <span className="ms-2">
                                    <StatusBadge label="Système" variant="secondary" dot />
                                </span>
                            )}
                        </td>
                        <td>
                            <span className="badge badge-soft-secondary">{row.depensesCount}</span>
                        </td>
                        <td>
                            <StatusBadge
                                label={row.statut === 'Actif' ? 'Actif' : 'Inactif'}
                                variant={row.statut === 'Actif' ? 'success' : 'secondary'}
                                dot
                            />
                        </td>
                        <td className="text-end">
                            {!row.isSystem && (
                                <RowActions>
                                    {permissions.update && (
                                        <RowActionItem icon="ti-edit" onClick={() => openEdit(row)}>
                                            Modifier
                                        </RowActionItem>
                                    )}
                                    {permissions.delete && (
                                        <RowActionItem
                                            icon="ti-trash"
                                            danger
                                            onClick={() => {
                                                setDeleteTarget(row);
                                                setDeleteError(undefined);
                                            }}
                                        >
                                            Supprimer
                                        </RowActionItem>
                                    )}
                                </RowActions>
                            )}
                        </td>
                    </tr>
                ))}
            </RelatedRecordsTable>
            <Pagination paginator={types} />

            <Modal
                show={showModal}
                title={editingId ? 'Modifier le type de dépense' : 'Ajouter un type de dépense'}
                onClose={closeModal}
                processing={form.processing}
                size="lg"
            >
                <form onSubmit={handleSubmit}>
                    <div className="row">
                        <div className="col-md-6">
                            <FormField
                                id="td-nom"
                                label="Nom"
                                required
                                value={form.data.nom}
                                onChange={(event) => form.setData('nom', event.target.value)}
                                error={form.errors.nom}
                                placeholder="ex : Loyer"
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="td-statut"
                                label="Statut"
                                required
                                options={STATUT_OPTIONS}
                                value={form.data.statut}
                                onChange={(event) => form.setData('statut', event.target.value)}
                                error={form.errors.statut}
                            />
                        </div>
                    </div>
                    <div className="d-flex justify-content-end gap-2 mt-3">
                        <FormActions onCancel={closeModal} processing={form.processing} />
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={deleteTarget !== null}
                title="Supprimer ce type de dépense ?"
                recordLabel={deleteTarget?.nom ?? ''}
                message="Cette action est définitive. Le type sera supprimé s'il n'est plus utilisé par des dépenses."
                error={deleteError}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </div>
    );
}
