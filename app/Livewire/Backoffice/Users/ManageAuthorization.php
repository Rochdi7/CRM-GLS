<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Users;

use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\UserAuthorizationService;
use App\Support\Authorization\PermissionRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Per-user authorization: role assignment + (advanced) direct permissions +
 * effective permission overview with provenance (role vs direct).
 * Route: backoffice.users.authorization.edit (permission:users.assign-roles).
 *
 * All mutations flow through UserAuthorizationService (transactions,
 * super-admin invariants, audit log).
 */
final class ManageAuthorization extends Component
{
    use AuthorizesRequests;

    public User $user;

    /** @var list<string> */
    public array $selectedRoles = [];

    /** @var list<string> */
    public array $directPermissions = [];

    public function mount(User $user): void
    {
        $this->authorize('users.assign-roles');

        $this->user = $user->load(['roles', 'permissions', 'employee.etablissement']);
        $this->selectedRoles = $this->user->roles->pluck('name')->all();
        $this->directPermissions = $this->user->permissions->pluck('name')->all();
    }

    public function save(UserAuthorizationService $service): void
    {
        // Re-authorize on the mutation itself, never trust mount() alone.
        $this->authorize('users.assign-roles');

        $this->validate([
            'selectedRoles' => ['array'],
            'selectedRoles.*' => ['string'],
            'directPermissions' => ['array'],
            'directPermissions.*' => ['string'],
        ]);

        // Deep validation (existence, super-admin rules, direct-permission
        // privilege) happens in the service and surfaces as validation errors.
        $service->syncAuthorization(
            auth()->user(),
            $this->user,
            array_values($this->selectedRoles),
            array_values($this->directPermissions),
        );

        session()->flash('status', __('Authorization updated.'));
        $this->redirectRoute('backoffice.users.index');
    }

    /** Toggle every permission of a module group (advanced direct permissions). */
    public function toggleGroup(string $group, bool $on): void
    {
        $names = array_keys(PermissionRegistry::grouped()[$group] ?? []);

        $this->directPermissions = $on
            ? array_values(array_unique([...$this->directPermissions, ...$names]))
            : array_values(array_diff($this->directPermissions, $names));
    }

    public function render(): View
    {
        $this->user->load(['roles.permissions', 'permissions']);

        // Effective permissions = union of what the SELECTED roles grant + the
        // selected direct ones, computed live so the summary reacts to edits.
        $viaSelectedRoles = Role::query()
            ->whereIn('name', $this->selectedRoles)
            ->with('permissions')
            ->get()
            ->flatMap(fn (Role $r) => $r->permissions->pluck('name'))
            ->unique()
            ->values()
            ->all();

        $effective = array_values(array_unique([...$viaSelectedRoles, ...$this->directPermissions]));

        return view('livewire.backoffice.users.manage-authorization', [
            'roles' => Role::query()->withCount('permissions')->orderBy('name')->get(),
            'roleLabels' => PermissionRegistry::roles(),
            'groups' => PermissionRegistry::grouped(),
            'viaRoles' => $viaSelectedRoles,
            'direct' => $this->directPermissions,
            'effective' => $effective,
            'totalPermissions' => count(PermissionRegistry::names()),
            'isSuperAdmin' => in_array(Role::SUPER_ADMIN, $this->selectedRoles, true),
            'canAssignDirect' => auth()->user()->can('users.assign-permissions'),
        ])->layout('components.backoffice.layout.app', ['title' => __('User Authorization')]);
    }
}
