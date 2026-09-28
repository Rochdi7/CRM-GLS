import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import RolePermissionsForm from '@/Components/Roles/RolePermissionsForm';
import type { RoleEditPageProps, RoleFormPayload } from '@/Types';

/**
 * Edit Role — full page (no modal), replaces
 * App\Livewire\Backoffice\Roles\RoleForm's update branch. Machine name is
 * immutable: the input stays disabled and its value is never sent to
 * UpdateRoleRequest (which doesn't even accept a `name` field — the
 * controller only ever writes `label` + permissions, see
 * RoleController@update). Reaching this page for a protected
 * (super-admin) role is already hard-blocked server-side
 * (RoleController@edit's `abort_if($role->isProtected(), 403, ...)`), so no
 * redundant client-side re-check is added here.
 */
export default function RoleEdit({ role, selectedPermissions, permissionGroups, lockedPermissions }: RoleEditPageProps) {
    // A super-admin-only grant still stored on the role is never sent back —
    // UpdateRoleRequest refuses it, which made the whole save fail. It is
    // named in a warning instead, and the save removes it from the role.
    const staleLocked = selectedPermissions.filter((permission) => lockedPermissions.includes(permission));
    const form = useForm<RoleFormPayload>({
        label: role.label,
        name: role.name,
        permissions: selectedPermissions.filter((permission) => !lockedPermissions.includes(permission)),
    });

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.put(`/backoffice/roles/${role.id}`);
    }

    function handleCancel() {
        router.visit('/backoffice/roles');
    }

    return (
        <BackofficeLayout
            title="Modifier le rôle"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Rôles & Permissions', href: '/backoffice/roles' },
                { label: 'Modifier le rôle' },
            ]}
        >
            <RolePermissionsForm
                editing
                label={form.data.label}
                onLabelChange={(value) => form.setData('label', value)}
                name={form.data.name}
                onNameChange={() => undefined}
                selected={form.data.permissions}
                onSelectedChange={(next) => form.setData('permissions', next)}
                permissionGroups={permissionGroups}
                lockedPermissions={lockedPermissions}
                staleLockedPermissions={staleLocked}
                errors={form.errors}
                processing={form.processing}
                onSubmit={handleSubmit}
                onCancel={handleCancel}
            />
        </BackofficeLayout>
    );
}
