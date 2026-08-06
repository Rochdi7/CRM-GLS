import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
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
import type { CrudPermissions, PaginatedData, SelectOption, SharedProps, TypeDepenseForm, TypeDepenseRow } from '@/Types';

interface TypesDepensesIndexProps {
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
 * Replaces the Livewire TypesDepensesIndex component (previously the 3rd tab
 * of Gestion des dépenses; now its own page — docs/phase-6-simple-crud-
 * inventory.md §Q2). is_system rows never show edit/delete controls
 * (visual-only — the backend guard is authoritative, see
 * TypeDepenseController).
 */
export default function TypesDepensesIndex({ types, filters, permissions }: TypesDepensesIndexProps) {
    // Sibling-page tab links, UI-gated like the sidebar (server enforces).
    const { auth } = usePage<SharedProps>().props;
    const canViewDepenses = auth.isSuperAdmin || auth.permissions.includes('expenses.view');
    const canViewRemboursements = auth.isSuperAdmin || auth.permissions.includes('refunds.view');
    const [showModal, setShowModal] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<TypeDepenseRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);

    const form = useForm<TypeDepenseForm>(EMPTY_FORM);

    function handleSearch(search: string) {
        router.get('/backoffice/types-depenses', { search }, { preserveState: true, preserveScroll: true, replace: true });
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
        const options = { onSuccess: () => closeModal() };

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
        <BackofficeLayout
            title="Types de dépenses"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Types de dépenses' },
            ]}
            actions={
                permissions.create && (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                        <i className="ti ti-square-rounded-plus me-2" />
                        Ajouter un type de dépense
                    </button>
                )
            }
        >
            {/* wimschool-style page tabs — mirrors the Gestion des dépenses
                page's bar with this page as the active tab. */}
            <ul className="nav nav-tabs mb-4" role="tablist">
                {canViewDepenses && (
                    <li className="nav-item" role="presentation">
                        <Link href="/backoffice/depenses" className="nav-link d-inline-flex align-items-center">
                            <i className="ti ti-receipt me-2" aria-hidden="true" />
                            Dépenses
                        </Link>
                    </li>
                )}
                {canViewRemboursements && (
                    <li className="nav-item" role="presentation">
                        <Link href="/backoffice/depenses?tab=remboursements" className="nav-link d-inline-flex align-items-center">
                            <i className="ti ti-arrow-back-up me-2" aria-hidden="true" />
                            Remboursements
                        </Link>
                    </li>
                )}
                <li className="nav-item" role="presentation">
                    <span className="nav-link active d-inline-flex align-items-center" aria-current="page">
                        <i className="ti ti-receipt-tax me-2" aria-hidden="true" />
                        Types de dépenses
                    </span>
                </li>
            </ul>

            <Card title="Types de dépenses" bodyClassName="p-0 py-3">
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
                <Pagination paginator={types} showJumpToPage />
            </Card>

            <Modal
                show={showModal}
                title={editingId ? 'Modifier le type de dépense' : 'Ajouter un type de dépense'}
                onClose={closeModal}
                processing={form.processing}
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
        </BackofficeLayout>
    );
}
