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
import type { CrudPermissions, PaginatedData, SelectOption, SharedProps, StockTypeForm, StockTypeRow } from '@/Types';

interface StockTypesIndexProps {
    types: PaginatedData<StockTypeRow>;
    filters: { search: string };
    permissions: CrudPermissions;
}

const STATUT_OPTIONS: SelectOption[] = [
    { value: 'Actif', label: 'Actif' },
    { value: 'Inactif', label: 'Inactif' },
];

const EMPTY_FORM: StockTypeForm = { nom: '', statut: 'Actif' };

/**
 * Types de stock CRUD — replaces StockArticle's old hardcoded CATEGORIES
 * array so new product types (books, supplies, equipment, …) can be added
 * without a code change. Mirrors TypesDepenses/Index.tsx one-for-one.
 * is_system rows (the original 6 categories) never show edit/delete
 * controls (visual-only — the backend guard is authoritative, see
 * StockTypeController).
 */
export default function StockTypesIndex({ types, filters, permissions }: StockTypesIndexProps) {
    const { auth } = usePage<SharedProps>().props;
    const canViewStock = auth.isSuperAdmin || auth.permissions.includes('stock.view');
    const [showModal, setShowModal] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<StockTypeRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);

    const form = useForm<StockTypeForm>(EMPTY_FORM);

    function handleSearch(search: string) {
        router.get('/backoffice/stock-types', { search }, { preserveState: true, preserveScroll: true, replace: true });
    }

    function openCreate() {
        form.reset();
        form.clearErrors();
        setEditingId(null);
        setShowModal(true);
    }

    function openEdit(row: StockTypeRow) {
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
            form.put(`/backoffice/stock-types/${editingId}`, options);
        } else {
            form.post('/backoffice/stock-types', options);
        }
    }

    function confirmDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        setDeleteError(undefined);

        form.transform(() => ({}));
        form.delete(`/backoffice/stock-types/${deleteTarget.id}`, {
            preserveScroll: true,
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
            title="Types de stock"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Types de stock' },
            ]}
            actions={
                permissions.create && (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                        <i className="ti ti-square-rounded-plus me-2" />
                        Ajouter un type de stock
                    </button>
                )
            }
        >
            {/* wimschool-style page tabs — mirrors the Gestion du stock page's bar with this page as the active tab. */}
            <ul className="nav nav-tabs p-0 border-bottom rounded-0 mb-4" role="tablist">
                {canViewStock && (
                    <li className="nav-item" role="presentation">
                        <Link href="/backoffice/stock" className="nav-link d-inline-flex align-items-center">
                            <i className="ti ti-package me-2" aria-hidden="true" />
                            Stock
                        </Link>
                    </li>
                )}
                <li className="nav-item" role="presentation">
                    <span className="nav-link active d-inline-flex align-items-center" aria-current="page">
                        <i className="ti ti-category me-2" aria-hidden="true" />
                        Types de stock
                    </span>
                </li>
            </ul>

            <Card title="Types de stock" bodyClassName="p-0 py-3">
                <TableLengthRow search={<SearchInput value={filters.search} onSearch={handleSearch} placeholder="Rechercher" />} />

                <RelatedRecordsTable
                    isEmpty={types.data.length === 0}
                    emptyTitle="Aucun type de stock pour le moment"
                    emptyIcon="ti ti-category"
                    head={
                        <tr>
                            <th>Nom</th>
                            <th>Articles</th>
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
                                <span className="badge badge-soft-secondary">{row.articlesCount}</span>
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
                title={editingId ? 'Modifier le type de stock' : 'Ajouter un type de stock'}
                onClose={closeModal}
                processing={form.processing}
                size="lg"
            >
                <form onSubmit={handleSubmit}>
                    <div className="row">
                        <div className="col-md-6">
                            <FormField
                                id="st-nom"
                                label="Nom"
                                required
                                value={form.data.nom}
                                onChange={(event) => form.setData('nom', event.target.value)}
                                error={form.errors.nom}
                                placeholder="ex : Livre"
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="st-statut"
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
                title="Supprimer ce type de stock ?"
                recordLabel={deleteTarget?.nom ?? ''}
                message="Cette action est définitive. Le type sera supprimé s'il n'est plus utilisé par des articles."
                error={deleteError}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </BackofficeLayout>
    );
}
