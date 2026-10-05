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
 * Gated by UserPolicy@manageAuthorization (audit 27/08/2026 SEC-03/04).
 */
final class UserAuthorizationController extends Controller
{
    public function edit(User $user, UserAuthorizationService $service): Response
    {
        $this->authorize('manageAuthorization', $user);

        $user->load(['roles', 'permissions']);

        // superAdminOnly() abilities can be handed out directly ONLY by a
        // super-admin (UserAuthorizationService::guardPermissionNames). For
        // anyone else the picker shows them locked, « Réservé au
        // super-admin », exactly as the Rôles form does — minus the ones the
        // target already holds, which a later edit may keep.
        $lockedPermissions = $service->canGrantReservedPermissions(auth()->user())
            ? []
            : array_values(array_diff(PermissionRegistry::superAdminOnly(), $user->permissions->pluck('name')->all()));

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
            'groups' => PermissionRegistry::groupedGrantable(),
            'totalPermissions' => count(PermissionRegistry::names()),
            'isSuperAdmin' => $user->hasRole(Role::SUPER_ADMIN),
            'canAssignDirect' => auth()->user()->can('users.assign-permissions'),
            'lockedPermissions' => $lockedPermissions,
        ]);
    }

    public function update(
        SyncUserAuthorizationRequest $request,
        User $user,
        UserAuthorizationService $service,
    ): RedirectResponse {
        // Re-authorize on the mutation itself, never trust the `edit` action
        // alone. UserPolicy: permission + centre reach + no self-edit + a
        // super-admin target only for a super-admin actor.
        $this->authorize('manageAuthorization', $user);

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
