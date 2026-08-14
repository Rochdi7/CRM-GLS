<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Users\SyncUserAuthorizationRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\UserAuthorizationService;
use App\Support\Authorization\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Full-page role/direct-permission assignment for ONE target user. Replaces
 * App\Livewire\Backoffice\Users\ManageAuthorization as the active UI; that
 * Livewire component is kept, unused, for rollback.
 *
 * All business rules (role/permission name validation, super-admin
 * grant/remove invariants, last-super-admin lockout, direct-permissions
 * privilege check, transaction, cache reset, activity log) live exclusively
 * in UserAuthorizationService::syncAuthorization() — this controller is a
 * thin wrapper and reimplements none of it.
 *
 * Permission-string based only ($this->authorize('users.assign-roles')) —
 * deliberately no UserPolicy/RolePolicy, per docs/roles-and-permissions.md.
 */
final class UserAuthorizationController extends Controller
{
    public function edit(User $user): Response
    {
        $this->authorize('users.assign-roles');

        $user->load(['roles', 'permissions']);

        return Inertia::render('Backoffice/Users/Authorization', [
            'targetUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'selectedRoles' => $user->roles->pluck('name')->values()->all(),
            'directPermissions' => $user->permissions->pluck('name')->values()->all(),
            'roles' => Role::query()->with('permissions')->withCount('permissions')->orderBy('name')->get()
                ->map(fn (Role $role): array => [
                    'name' => $role->name,
                    'label' => $role->displayLabel(),
                    'permissionsCount' => $role->permissions_count,
                    // Lets the frontend compute "granted via role X" live as the
                    // admin checks/unchecks roles, before saving — mirrors the
                    // Livewire original's server-computed viaSelectedRoles.
                    'permissionNames' => $role->permissions->pluck('name')->values()->all(),
                ])->values(),
            'roleLabels' => PermissionRegistry::roles(),
            'groups' => PermissionRegistry::grouped(),
            'totalPermissions' => count(PermissionRegistry::names()),
            'isSuperAdmin' => $user->hasRole(Role::SUPER_ADMIN),
            'canAssignDirect' => auth()->user()->can('users.assign-permissions'),
        ]);
    }

    public function update(
        SyncUserAuthorizationRequest $request,
        User $user,
        UserAuthorizationService $service,
    ): RedirectResponse {
        // Re-authorize on the mutation itself, never trust the `edit` action
        // alone (matches the Livewire component's mount()+save() pattern).
        $this->authorize('users.assign-roles');

        $data = $request->validated();

        // Deep validation (existence, super-admin rules, direct-permission
        // privilege) happens inside the service and throws ValidationException
        // on violation — Inertia's useForm().post() handles the resulting
        // 422/redirect-back-with-errors the standard way.
        $service->syncAuthorization(
            auth()->user(),
            $user,
            array_values($data['roles'] ?? []),
            array_values($data['directPermissions'] ?? []),
        );

        return redirect()->route('backoffice.users.index')
            ->with('success', __('Autorisations mises à jour.'));
    }
}
