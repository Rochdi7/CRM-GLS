import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DataTable from '@/Components/Tables/DataTable';
import EmptyState from '@/Components/Shared/EmptyState';
import StatusBadge from '@/Components/Details/StatusBadge';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
import SearchInput from '@/Components/Tables/SearchInput';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import type { RoleRow, RolesIndexPageProps } from '@/Types';

/**
 * Roles & Permissions list — replaces
 * App\Livewire\Backoffice\Roles\RolesIndex. Search (name/label ILIKE,
 * server-side) + pagination match GetRolesList exactly. "New Role" is a
 * real Inertia navigation to the Create page (no modal, per this module's
 * full-page UX). Protected roles (super-admin) never show Edit/Delete —
 * a "Protégé" badge renders in the action column instead, matching the
 * Blade view's `@if (! $role->isProtected())` guard.
 */
export default function RolesIndex({ roles, search }: RolesIndexPageProps) {
    const isLoading = useInertiaLoading();
    const [deleteTarget, setDeleteTarget] = useState<RoleRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);

    function handleSearch(value: string) {
        router.get(
            '/backoffice/roles',
            { search: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function confirmDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        setDeleteError(undefined);

        // destroy() returns a validation-style `errors.delete` entry (422)
        // when the role is protected or still assigned to users — never a
        // generic failure. Surfaced verbatim in ConfirmDialog below.
        router.delete(`/backoffice/roles/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteTarget(null);
                setDeleting(false);
            },
            onError: (errors) => {
                setDeleteError(errors.delete ?? 'Suppression impossible.');
                setDeleting(false);
            },
        });
    }

    return (
        <BackofficeLayout
            title="Rôles & Permissions"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Rôles & Permissions' },
            ]}
            actions={
                <Link href="/backoffice/roles/create" className="btn btn-primary d-flex align-items-center">
                    <i className="fa fa-square-plus me-2" />
                    Nouveau rôle
                </Link>
            }
        >
            <Card title="Rôles" bodyClassName="p-0 py-3">
                <TableLengthRow search={<SearchInput value={search} onSearch={handleSearch} placeholder="Rechercher" />} />

                {roles.data.length === 0 ? (
                    <EmptyState title="Aucun rôle trouvé" icon="fa fa-shield" />
                ) : (
                    <>
                        <DataTable
                            loading={isLoading}
                            head={
                                <tr>
                                    <th>Libellé</th>
                                    <th>Nom technique</th>
                                    <th>Permissions</th>
                                    <th>Utilisateurs</th>
                                    <th className="text-end">Action</th>
                                </tr>
                            }
                        >
                            {roles.data.map((role) => (
                                <tr key={role.id}>
                                    <td className="fw-medium">{role.label}</td>
                                    <td>
                                        <code>{role.name}</code>
                                    </td>
                                    <td>
                                        {role.isProtected ? (
                                            <span className="badge badge-soft-warning">Toutes les permissions</span>
                                        ) : (
                                            <span className="badge badge-soft-info">{role.permissionsCount}</span>
                                        )}
                                    </td>
                                    <td>
                                        <span className="badge badge-soft-secondary">{role.usersCount}</span>
                                    </td>
                                    <td className="text-end">
                                        {role.isProtected ? (
                                            <StatusBadge label="Protégé" variant="danger" dot />
                                        ) : (
                                            <RowActions>
                                                <RowActionItem icon="fa-pen" href={`/backoffice/roles/${role.id}/edit`}>
                                                    Modifier
                                                </RowActionItem>
                                                <RowActionItem
                                                    icon="fa-trash"
                                                    danger
                                                    onClick={() => setDeleteTarget(role)}
                                                >
                                                    Supprimer
                                                </RowActionItem>
                                            </RowActions>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </DataTable>
                        <Pagination paginator={roles} showJumpToPage />
                    </>
                )}
            </Card>

            <ConfirmDialog
                show={deleteTarget !== null}
                title="Supprimer ce rôle ?"
                recordLabel={deleteTarget?.label ?? ''}
                message="Cette action est définitive."
                error={deleteError}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => {
                    setDeleteTarget(null);
                    setDeleteError(undefined);
                }}
            />
        </BackofficeLayout>
    );
}
