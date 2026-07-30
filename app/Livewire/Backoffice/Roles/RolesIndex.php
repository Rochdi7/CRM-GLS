<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Roles;

use App\Livewire\Backoffice\Concerns\WithPerPage;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles list — search, pagination, permission/user counts.
 * Route: backoffice.roles.index (middleware permission:roles.view).
 */
final class RolesIndex extends Component
{
    use AuthorizesRequests;
    use WithPagination;
    use WithPerPage;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('roles.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function deleteRole(int $roleId): void
    {
        // mount() does NOT protect later Livewire requests — re-authorize.
        $this->authorize('roles.delete');

        /** @var Role $role */
        $role = Role::query()->withCount('users')->findOrFail($roleId);

        if ($role->isProtected()) {
            $this->addError('delete', __('This role is protected and cannot be deleted.'));

            return;
        }

        if ($role->users_count > 0) {
            $this->addError('delete', __('This role is still assigned to users and cannot be deleted.'));

            return;
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        activity('authorization')
            ->causedBy(auth()->user())
            ->withProperties(['role' => $role->name])
            ->log('role deleted');

        session()->flash('status', __('Role deleted.'));
        $this->redirectRoute('backoffice.roles.index');
    }

    public function render(): View
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($q): void {
                    $q->where('name', 'ilike', "%{$this->search}%")
                        ->orWhere('label', 'ilike', "%{$this->search}%");
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.backoffice.roles.roles-index', ['roles' => $roles])
            ->layout('components.backoffice.layout.app', ['title' => __('Roles & Permissions')]);
    }
}
