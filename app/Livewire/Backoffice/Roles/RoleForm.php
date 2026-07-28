<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Roles;

use App\Models\Role;
use App\Support\Authorization\PermissionRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\PermissionRegistrar;

/**
 * Create/edit a role with permissions grouped by module.
 * Routes: backoffice.roles.create (permission:roles.create)
 *         backoffice.roles.edit   (permission:roles.update)
 *
 * Machine names are immutable once created (referenced by seeder/policies);
 * only the French label and the permission set are editable.
 * The super-admin role is not editable here at all.
 */
final class RoleForm extends Component
{
    use AuthorizesRequests;

    public ?Role $role = null;

    public string $name = '';

    public string $label = '';

    /** @var list<string> */
    public array $selected = [];

    public string $permissionSearch = '';

    public function mount(?Role $role = null): void
    {
        $this->role = $role?->exists ? $role : null;

        if ($this->role !== null) {
            $this->authorize('roles.update');
            abort_if($this->role->isProtected(), 403, __('This role is protected.'));

            $this->name = $this->role->name;
            $this->label = (string) ($this->role->label ?? '');
            $this->selected = $this->role->permissions()->pluck('name')->all();
        } else {
            $this->authorize('roles.create');
        }
    }

    /** Select every permission of a module group. */
    public function selectGroup(string $group): void
    {
        $names = array_keys(PermissionRegistry::grouped()[$group] ?? []);
        $this->selected = array_values(array_unique([...$this->selected, ...$names]));
    }

    /** Clear every permission of a module group. */
    public function clearGroup(string $group): void
    {
        $names = array_keys(PermissionRegistry::grouped()[$group] ?? []);
        $this->selected = array_values(array_diff($this->selected, $names));
    }

    public function save(): void
    {
        $editing = $this->role !== null;

        // Re-authorize on the mutation itself, never trust mount() alone.
        $this->authorize($editing ? 'roles.update' : 'roles.create');

        if ($editing && $this->role->isProtected()) {
            abort(403, __('This role is protected.'));
        }

        if (! $editing) {
            $this->name = Str::slug($this->name);
        }

        $validated = $this->validate([
            'label' => ['required', 'string', 'max:100'],
            'name' => [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                Rule::unique('roles', 'name')->ignore($this->role?->id),
            ],
            'selected' => ['array'],
            'selected.*' => ['string', Rule::in(PermissionRegistry::names())],
        ]);

        DB::transaction(function () use ($validated, $editing): void {
            if ($editing) {
                // Machine name stays immutable — only label + permissions move.
                $this->role->update(['label' => $validated['label']]);
            } else {
                $this->role = Role::create([
                    'name' => $validated['name'],
                    'label' => $validated['label'],
                    'guard_name' => 'web',
                ]);
            }

            $this->role->syncPermissions($validated['selected']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        activity('authorization')
            ->causedBy(auth()->user())
            ->withProperties(['role' => $this->role->name, 'permissions' => $validated['selected']])
            ->log($editing ? 'role updated' : 'role created');

        session()->flash('status', $editing ? __('Role updated.') : __('Role created.'));
        $this->redirectRoute('backoffice.roles.index');
    }

    public function render(): View
    {
        $groups = PermissionRegistry::grouped();

        if ($this->permissionSearch !== '') {
            $needle = Str::lower($this->permissionSearch);
            $groups = array_filter(array_map(
                fn (array $perms): array => array_filter(
                    $perms,
                    fn (string $label, string $perm): bool => str_contains(Str::lower($label), $needle)
                        || str_contains(Str::lower($perm), $needle),
                    ARRAY_FILTER_USE_BOTH,
                ),
                $groups,
            ));
        }

        return view('livewire.backoffice.roles.role-form', ['groups' => $groups])
            ->layout('components.backoffice.layout.app', [
                'title' => $this->role !== null ? __('Edit Role') : __('New Role'),
            ]);
    }
}
