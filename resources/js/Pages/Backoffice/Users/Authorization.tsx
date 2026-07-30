import { useForm } from '@inertiajs/react';
import { useMemo, useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import type { LaravelValidationErrors, SyncUserAuthorizationForm, UsersAuthorizationPageProps } from '@/Types';

const SUPER_ADMIN_ROLE = 'super-admin';

/**
 * Replaces App\Livewire\Backoffice\Users\ManageAuthorization — full page (not
 * a modal), matching the original UX exactly: left column = target identity +
 * role checkboxes + Save/Cancel; right column = live "effective permissions"
 * summary (via role vs direct) + a collapsible advanced direct-permissions
 * editor. Save is a genuine Inertia visit that redirects to the users index
 * with a flash status (App\Http\Controllers\Backoffice\Users\UserAuthorizationController::update()),
 * not a modal close.
 */
export default function UsersAuthorization({
    targetUser,
    selectedRoles: initialSelectedRoles,
    directPermissions: initialDirectPermissions,
    roles,
    groups,
    totalPermissions,
    canAssignDirect,
}: UsersAuthorizationPageProps) {
    const form = useForm<SyncUserAuthorizationForm>({
        roles: initialSelectedRoles,
        directPermissions: initialDirectPermissions,
    });

    const [advancedOpen, setAdvancedOpen] = useState(initialDirectPermissions.length > 0);

    const selectedRoles = form.data.roles;
    const directPermissions = form.data.directPermissions;

    const isSuperAdmin = selectedRoles.includes(SUPER_ADMIN_ROLE);

    const selectedRoleDetails = useMemo(
        () => roles.filter((role) => selectedRoles.includes(role.name)),
        [roles, selectedRoles],
    );

    const rolePermissionsTotal = useMemo(
        () => selectedRoleDetails.reduce((sum, role) => sum + role.permissionsCount, 0),
        [selectedRoleDetails],
    );

    // Every permission granted by any currently-selected role — recomputed
    // live as roles are toggled, before saving. Mirrors the Livewire
    // original's server-computed viaSelectedRoles.
    const viaRoles = useMemo(
        () => new Set(selectedRoleDetails.flatMap((role) => role.permissionNames)),
        [selectedRoleDetails],
    );

    const effective = useMemo(
        () => Array.from(new Set([...viaRoles, ...directPermissions])),
        [viaRoles, directPermissions],
    );

    function toggleRole(name: string, checked: boolean) {
        form.setData(
            'roles',
            checked ? [...selectedRoles, name] : selectedRoles.filter((role) => role !== name),
        );
    }

    function toggleDirectPermission(name: string, checked: boolean) {
        form.setData(
            'directPermissions',
            checked ? [...directPermissions, name] : directPermissions.filter((permission) => permission !== name),
        );
    }

    function toggleGroup(group: string, on: boolean) {
        const names = Object.keys(groups[group] ?? {});

        form.setData(
            'directPermissions',
            on
                ? Array.from(new Set([...directPermissions, ...names]))
                : directPermissions.filter((permission) => !names.includes(permission)),
        );
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        // Real Inertia visit — on success the controller redirects to
        // backoffice.users.index with a flash status (not a modal close);
        // on validation failure (super-admin lockout, unknown role name,
        // direct-permission privilege check, unknown permission name)
        // useForm() surfaces errors the standard Inertia way (redirect back
        // with errors) — see rawErrors below for the exact bag keys used by
        // UserAuthorizationService::syncAuthorization().
        form.put(`/backoffice/users/${targetUser.id}/authorization`, {
            preserveScroll: true,
        });
    }

    // UserAuthorizationService::syncAuthorization() throws
    // ValidationException::withMessages(['roles' => ...]) for role-side
    // violations (unknown role, super-admin grant/lockout) — matches
    // `roles`, a real submitted field, so `form.errors.roles` covers it. But
    // the direct-permissions privilege check and "unknown permission name"
    // both use the bag key 'permissions' (NOT 'directPermissions', the
    // actual submitted field name) — see app/Services/Authorization/
    // UserAuthorizationService.php. useForm<SyncUserAuthorizationForm>'s
    // `errors` type only knows the real form keys, so this extra
    // server-only key is read from the raw errors object instead.
    const rawErrors = form.errors as unknown as LaravelValidationErrors;
    const permissionsError = rawErrors.permissions;

    return (
        <BackofficeLayout
            title="Autorisations de l'utilisateur"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Utilisateurs', href: '/backoffice/users' },
                { label: targetUser.name },
            ]}
        >
            <form onSubmit={submit}>
                <div className="row">
                    {/* ============================ LEFT: user + roles ============================ */}
                    <div className="col-xl-4">
                        {/* User identity card */}
                        <Card>
                            <div className="d-flex align-items-center mb-3">
                                <span className="avatar avatar-lg bg-primary-transparent rounded-circle me-3 d-flex align-items-center justify-content-center">
                                    <span className="fs-20 fw-bold text-primary">
                                        {targetUser.name.slice(0, 1).toUpperCase()}
                                    </span>
                                </span>
                                <div className="overflow-hidden">
                                    <h5 className="mb-0 text-truncate">{targetUser.name}</h5>
                                    <p className="text-muted mb-0 text-truncate">{targetUser.email}</p>
                                </div>
                            </div>

                            <div className="border-top pt-3">
                                <p className="text-muted mb-0">
                                    <i className="ti ti-user-off me-1" />
                                    Consultez la fiche employé associée depuis la liste des utilisateurs si besoin.
                                </p>
                            </div>
                        </Card>

                        {/* Roles as selectable cards */}
                        <Card
                            title="Rôles"
                            tools={
                                <span className="badge badge-soft-primary">
                                    {selectedRoles.length} / {roles.length}
                                </span>
                            }
                        >
                            {form.errors.roles && (
                                <div className="alert alert-danger" role="alert">
                                    {form.errors.roles}
                                </div>
                            )}

                            <p className="text-muted fs-13 mb-3">
                                Attribuez un ou plusieurs rôles. Les permissions sont accordées par les rôles sélectionnés
                                ici.
                            </p>

                            <div className="d-flex flex-column gap-2">
                                {roles.map((role) => {
                                    const checked = selectedRoles.includes(role.name);

                                    return (
                                        <label
                                            key={role.name}
                                            htmlFor={`role-${role.name}`}
                                            className={`d-flex align-items-center border rounded p-2 mb-0${
                                                checked ? ' border-primary bg-primary-transparent' : ''
                                            }`}
                                            style={{ cursor: 'pointer' }}
                                        >
                                            <input
                                                className="form-check-input mt-0 me-2"
                                                type="checkbox"
                                                id={`role-${role.name}`}
                                                checked={checked}
                                                onChange={(event) => toggleRole(role.name, event.target.checked)}
                                            />
                                            <span className="flex-fill">
                                                <span className="fw-medium d-block">{role.label}</span>
                                                <span className="text-muted fs-12">
                                                    {role.name === SUPER_ADMIN_ROLE ? (
                                                        <>
                                                            <i className="ti ti-shield-star me-1" />
                                                            Toutes les permissions
                                                        </>
                                                    ) : (
                                                        <>
                                                            {role.permissionsCount}{' '}
                                                            {role.permissionsCount > 1 ? 'permissions' : 'permission'}
                                                        </>
                                                    )}
                                                </span>
                                            </span>
                                        </label>
                                    );
                                })}
                            </div>

                            <div className="d-flex align-items-center gap-2 mt-4">
                                <button type="submit" className="btn btn-primary" disabled={form.processing}>
                                    {form.processing ? (
                                        <>
                                            <span
                                                className="spinner-border spinner-border-sm me-1"
                                                role="status"
                                                aria-hidden="true"
                                            />
                                            Enregistrement…
                                        </>
                                    ) : (
                                        <>
                                            <i className="ti ti-device-floppy me-1" />
                                            Enregistrer
                                        </>
                                    )}
                                </button>
                                <a href="/backoffice/users" className="btn btn-light">
                                    Annuler
                                </a>
                            </div>
                        </Card>
                    </div>

                    {/* ======================= RIGHT: effective + advanced ======================= */}
                    <div className="col-xl-8">
                        {/* Effective permissions summary */}
                        <Card
                            title="Permissions effectives"
                            tools={
                                !isSuperAdmin ? (
                                    <span className="badge badge-soft-success">
                                        {directPermissions.length} directe(s) / {totalPermissions} au total
                                    </span>
                                ) : undefined
                            }
                        >
                            {isSuperAdmin ? (
                                <div className="alert alert-warning d-flex align-items-center mb-0" role="alert">
                                    <i className="ti ti-shield-star fs-20 me-2" />
                                    <div>
                                        <strong>Super administrateur</strong> — cet utilisateur contourne toutes les
                                        vérifications de permissions.
                                    </div>
                                </div>
                            ) : effective.length === 0 && selectedRoleDetails.length === 0 ? (
                                <EmptyState
                                    title="Aucune permission pour le moment"
                                    message="Sélectionnez un rôle à gauche pour accorder des permissions."
                                    icon="ti ti-lock"
                                />
                            ) : (
                                <>
                                    <p className="text-muted fs-13 mb-3">
                                        <span className="badge badge-soft-info me-1">via rôle</span>
                                        accordée par un rôle
                                        <span className="badge badge-soft-warning ms-3 me-1">directe</span>
                                        attribuée directement
                                    </p>

                                    {selectedRoleDetails.length > 0 && (
                                        <div className="border rounded p-3 mb-3">
                                            <h6 className="mb-2">Rôles sélectionnés</h6>
                                            <div className="d-flex flex-wrap gap-1">
                                                {selectedRoleDetails.map((role) => (
                                                    <span className="badge badge-soft-info" key={role.name}>
                                                        {role.label} ({role.permissionsCount})
                                                    </span>
                                                ))}
                                            </div>
                                            <p className="text-muted fs-12 mt-2 mb-0">
                                                {rolePermissionsTotal} permission(s) au total accordées par les rôles
                                                ci-dessus (recouvrements possibles entre rôles).
                                            </p>
                                        </div>
                                    )}

                                    {directPermissions.length > 0 && (
                                        <div className="row g-3">
                                            {Object.entries(groups).map(([group, permissions]) => {
                                                const held = Object.keys(permissions).filter((permission) =>
                                                    directPermissions.includes(permission),
                                                );

                                                if (held.length === 0) {
                                                    return null;
                                                }

                                                return (
                                                    <div className="col-md-6" key={group}>
                                                        <div className="border rounded h-100 p-3">
                                                            <h6 className="mb-2 d-flex align-items-center justify-content-between">
                                                                {group}
                                                                <span className="badge badge-soft-secondary">
                                                                    {held.length}/{Object.keys(permissions).length}
                                                                </span>
                                                            </h6>
                                                            <div className="d-flex flex-wrap gap-1">
                                                                {held.map((permission) => (
                                                                    <span className="badge badge-soft-warning" key={permission}>
                                                                        {permissions[permission]}
                                                                    </span>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    )}
                                </>
                            )}
                        </Card>

                        {/* Advanced: direct permissions (collapsible, hidden by default) */}
                        {canAssignDirect && !isSuperAdmin && (
                            <div className="card">
                                <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                                    <h4 className="mb-3">
                                        <button
                                            type="button"
                                            className="btn btn-link p-0 text-dark text-decoration-none d-flex align-items-center"
                                            onClick={() => setAdvancedOpen((open) => !open)}
                                        >
                                            <i className="ti ti-adjustments-cog me-2" />
                                            Permissions directes (avancé)
                                            <i className={`ti ms-2 ${advancedOpen ? 'ti-chevron-up' : 'ti-chevron-down'}`} />
                                        </button>
                                    </h4>
                                    {directPermissions.length > 0 && (
                                        <div className="d-flex align-items-center flex-wrap mb-3">
                                            <span className="badge badge-soft-warning">
                                                {directPermissions.length} directe(s)
                                            </span>
                                        </div>
                                    )}
                                </div>
                                {advancedOpen && (
                                    <div className="card-body">
                                        <div className="alert alert-warning d-flex align-items-center" role="alert">
                                            <i className="ti ti-alert-triangle me-2" />
                                            <span className="fs-13">
                                                Préférez les rôles. Les permissions directes contournent le modèle de
                                                rôles et sont plus difficiles à auditer.
                                            </span>
                                        </div>
                                        {permissionsError && (
                                            <div className="alert alert-danger" role="alert">
                                                {permissionsError}
                                            </div>
                                        )}

                                        <div className="accordion">
                                            {Object.entries(groups).map(([group, permissions]) => {
                                                const groupKeys = Object.keys(permissions);
                                                const groupHeld = groupKeys.filter((permission) =>
                                                    directPermissions.includes(permission),
                                                ).length;
                                                const slug = group
                                                    .toLowerCase()
                                                    .normalize('NFD')
                                                    .replace(/\p{Diacritic}/gu, '')
                                                    .replace(/[^a-z0-9]+/g, '-')
                                                    .replace(/(^-|-$)/g, '');
                                                const collapseId = `dcol-${slug}`;

                                                return (
                                                    <DirectPermissionGroup
                                                        key={group}
                                                        group={group}
                                                        collapseId={collapseId}
                                                        groupHeld={groupHeld}
                                                        permissions={permissions}
                                                        directPermissions={directPermissions}
                                                        viaRoles={viaRoles}
                                                        onToggleGroup={toggleGroup}
                                                        onTogglePermission={toggleDirectPermission}
                                                    />
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </form>
        </BackofficeLayout>
    );
}

interface DirectPermissionGroupProps {
    group: string;
    collapseId: string;
    groupHeld: number;
    permissions: Record<string, string>;
    directPermissions: string[];
    viaRoles: Set<string>;
    onToggleGroup: (group: string, on: boolean) => void;
    onTogglePermission: (name: string, checked: boolean) => void;
}

/**
 * One collapsible module group inside the "Direct permissions (advanced)"
 * editor — React-owned open/close state (no data-bs-toggle/Bootstrap JS on
 * Inertia pages, see docs/bootstrap-react-integration-decision.md), matching
 * the Livewire original's per-group accordion + "All"/"None" buttons
 * (ManageAuthorization::toggleGroup()).
 */
function DirectPermissionGroup({
    group,
    collapseId,
    groupHeld,
    permissions,
    directPermissions,
    viaRoles,
    onToggleGroup,
    onTogglePermission,
}: DirectPermissionGroupProps) {
    const [open, setOpen] = useState(false);

    return (
        <div className="accordion-item border rounded mb-2">
            <div className="d-flex align-items-center justify-content-between p-3">
                <button
                    className="btn btn-link p-0 text-dark text-decoration-none fw-medium"
                    type="button"
                    onClick={() => setOpen((value) => !value)}
                    aria-expanded={open}
                    aria-controls={collapseId}
                >
                    <i className={`ti me-1 ${open ? 'ti-chevron-down' : 'ti-chevron-right'}`} />
                    {group}
                    {groupHeld > 0 && <span className="badge badge-soft-warning ms-2">{groupHeld}</span>}
                </button>
                <div className="btn-group btn-group-sm">
                    <button type="button" className="btn btn-outline-light" onClick={() => onToggleGroup(group, true)}>
                        Tout
                    </button>
                    <button type="button" className="btn btn-outline-light" onClick={() => onToggleGroup(group, false)}>
                        Aucun
                    </button>
                </div>
            </div>
            {open && (
                <div id={collapseId} className="px-3 pb-3">
                    <div className="row">
                        {Object.entries(permissions).map(([permission, label]) => (
                            <div className="col-md-6" key={permission}>
                                <div className="form-check mb-2">
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        id={`direct-${permission}`}
                                        checked={directPermissions.includes(permission)}
                                        onChange={(event) => onTogglePermission(permission, event.target.checked)}
                                    />
                                    <label className="form-check-label" htmlFor={`direct-${permission}`}>
                                        {label}
                                        {viaRoles.has(permission) && (
                                            <i
                                                className="ti ti-info-circle text-info ms-1"
                                                title="Déjà accordée par un rôle"
                                            />
                                        )}
                                    </label>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
