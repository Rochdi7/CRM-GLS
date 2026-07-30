import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import RolePermissionsForm from '@/Components/Roles/RolePermissionsForm';
import type { RoleCreatePageProps, RoleFormPayload } from '@/Types';

/**
 * New Role — full page (no modal), replaces
 * App\Livewire\Backoffice\Roles\RoleForm's create branch. `name` is the raw
 * machine name the admin types; StoreRoleRequest::prepareForValidation()
 * slugifies it server-side via Str::slug() before validating, exactly like
 * the Livewire component did just before ->validate() — so this page
 * deliberately does not pre-slugify client-side, it just submits whatever
 * was typed and displays back the `name` validation error verbatim if the
 * slugified result collides with an existing role.
 */
export default function RoleCreate({ permissionGroups }: RoleCreatePageProps) {
    const form = useForm<RoleFormPayload>({
        label: '',
        name: '',
        permissions: [],
    });

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post('/backoffice/roles');
    }

    function handleCancel() {
        router.visit('/backoffice/roles');
    }

    return (
        <BackofficeLayout
            title="Nouveau rôle"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Rôles & Permissions', href: '/backoffice/roles' },
                { label: 'Nouveau rôle' },
            ]}
        >
            <RolePermissionsForm
                label={form.data.label}
                onLabelChange={(value) => form.setData('label', value)}
                name={form.data.name}
                onNameChange={(value) => form.setData('name', value)}
                selected={form.data.permissions}
                onSelectedChange={(next) => form.setData('permissions', next)}
                permissionGroups={permissionGroups}
                errors={form.errors}
                processing={form.processing}
                onSubmit={handleSubmit}
                onCancel={handleCancel}
            />
        </BackofficeLayout>
    );
}
