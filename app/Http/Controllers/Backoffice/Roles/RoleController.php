<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Roles;

use App\Domain\Settings\Queries\GetRolesList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Roles\StoreRoleRequest;
use App\Http\Requests\Backoffice\Roles\UpdateRoleRequest;
use App\Models\Role;
use App\Support\Authorization\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles & Permissions — Inertia/React migration (Phase 7,
 * docs/inertia-react-migration-plan.md). Real HTTP endpoints replacing
 * App\Livewire\Backoffice\Roles\{RolesIndex,RoleForm}, which are kept,
 * unused, for rollback (see those files' own docblocks).
 *
 * Unlike Employees/Users, this module has no modal: create and edit are
 * separate full pages, matching the current UX exactly (routes
 * `backoffice.roles.create` / `backoffice.roles.edit` already existed as
 * distinct full-page Livewire routes before this migration).
 *
 * No dedicated RolePolicy exists (docs/authorization-architecture.md +
 * docs/roles-and-permissions.md): every action here gates purely on
 * permission strings, exactly like the Livewire components did
 * (`$this->authorize('roles.view'|'roles.create'|'roles.update'|'roles.delete')`).
 */
final class RoleController extends Controller
{
    public function index(Request $request, GetRolesList $getRolesList): Response
    {
        $this->authorize('roles.view');

        $search = (string) $request->string('search');
        $perPage = (int) $request->integer('per_page', 10);

        $roles = $getRolesList($search, $perPage);
        $roles->through(fn (Role $role): array => GetRolesList::present($role));

        return Inertia::render('Backoffice/Roles/Index', [
            'roles' => $roles,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('roles.create');

        return Inertia::render('Backoffice/Roles/Create', [
            'permissionGroups' => PermissionRegistry::groupedGrantable(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        // Re-authorize on the mutation itself — never trust the page load alone.
        $this->authorize('roles.create');

        $validated = $request->validated();
        $permissions = $validated['permissions'] ?? [];

        $role = DB::transaction(function () use ($validated, $permissions): Role {
            $role = Role::create([
                'name' => $validated['name'],
                'label' => $validated['label'],
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissions);

            return $role;
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        activity('authorization')
            ->causedBy($request->user())
            ->withProperties(['role' => $role->name, 'permissions' => $permissions])
            ->log('role created');

        return redirect()->route('backoffice.roles.index')
            ->with('success', __('Role created.'));
    }

    public function edit(Role $role): Response
    {
        $this->authorize('roles.update');

        // Hard 403 for the super-admin role — do not even allow loading the
        // edit page/form for it, matching RoleForm::mount()'s
        // `abort_if($this->role->isProtected(), 403, ...)` exactly.
        abort_if($role->isProtected(), 403, __('This role is protected.'));

        return Inertia::render('Backoffice/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $role->displayLabel(),
            ],
            'selectedPermissions' => $role->permissions()->pluck('name')->all(),
            'permissionGroups' => PermissionRegistry::groupedGrantable(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        // Re-authorize on the mutation itself, never trust edit()'s gate alone.
        $this->authorize('roles.update');

        // Re-check protection in case the target somehow still resolves to
        // super-admin (e.g. a stale form submission) — hard 403, not a
        // soft validation error, matching the Livewire component's
        // `abort(403, ...)` inside save().
        if ($role->isProtected()) {
            abort(403, __('This role is protected.'));
        }

        $validated = $request->validated();
        $permissions = $validated['permissions'] ?? [];

        DB::transaction(function () use ($role, $validated, $permissions): void {
            // Machine name stays immutable — only the label + permission set
            // move. `name` is never read from $validated (UpdateRoleRequest
            // does not even accept it as a validated field).
            $role->update(['label' => $validated['label']]);
            $role->syncPermissions($permissions);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        activity('authorization')
            ->causedBy($request->user())
            ->withProperties(['role' => $role->name, 'permissions' => $permissions])
            ->log('role updated');

        return redirect()->route('backoffice.roles.index')
            ->with('success', __('Role updated.'));
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        // Re-authorize on the mutation itself — never trust index()'s gate alone.
        $this->authorize('roles.delete');

        $role->loadCount('users');

        // Validation-style errors (not hard exceptions), exactly like
        // RolesIndex::deleteRole()'s $this->addError('delete', ...) calls —
        // surfaced to Inertia as a redirect-back with an `errors.delete` bag
        // entry rather than a 403/500.
        if ($role->isProtected()) {
            throw ValidationException::withMessages([
                'delete' => __('This role is protected and cannot be deleted.'),
            ]);
        }

        if ($role->users_count > 0) {
            throw ValidationException::withMessages([
                'delete' => __('This role is still assigned to users and cannot be deleted.'),
            ]);
        }

        $roleName = $role->name;
        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        activity('authorization')
            ->causedBy($request->user())
            ->withProperties(['role' => $roleName])
            ->log('role deleted');

        return redirect()->route('backoffice.roles.index')
            ->with('success', __('Role deleted.'));
    }
}
