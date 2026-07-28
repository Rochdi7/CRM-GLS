<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Users;

use App\Livewire\Backoffice\Concerns\WithCenterContext;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Users list + edit modal. Users are NEVER created here (they come from
 * EmployeeObserver) — only edited: name/email/username, active status toggle,
 * and password regeneration. Role assignment stays on the authorization page.
 */
final class UsersIndex extends Component
{
    use AuthorizesRequests;
    use WithCenterContext;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $username = '';

    public bool $is_active = true;

    public ?string $regeneratedPassword = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($this->editingId)],
            'is_active' => ['boolean'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('users.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $this->authorize('users.assign-roles'); // reuse the user-management permission

        $u = User::findOrFail($id);
        $this->editingId = $u->id;
        $this->name = $u->name;
        $this->email = (string) $u->email;
        $this->username = (string) $u->username;
        $this->is_active = (bool) $u->is_active;
        $this->regeneratedPassword = null;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('users.assign-roles');

        $data = $this->validate();

        User::findOrFail($this->editingId)->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $this->username ?: null,
            'is_active' => $this->is_active,
        ]);

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: __('User updated.'));
    }

    /** Generate a fresh one-time password and force a change on next login. */
    public function regeneratePassword(): void
    {
        $this->authorize('users.assign-roles');

        $user = User::findOrFail($this->editingId);
        $plain = Str::password(12);
        $user->update(['password' => $plain, 'must_change_password' => true]);

        activity('authorization')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log('password regenerated');

        $this->regeneratedPassword = $plain;
    }

    public function closeModal(): void
    {
        $this->reset(['showModal', 'editingId', 'name', 'email', 'username', 'is_active', 'regeneratedPassword']);
        $this->resetValidation();
    }

    public function render(): View
    {
        $users = User::query()
            ->with(['roles', 'employee.etablissement'])
            // Follow the active center via the employee profile; accounts
            // without an employee (pure admin logins) stay visible everywhere.
            ->when(app(CurrentContext::class)->etablissementId(), function ($query, int $centreId): void {
                $query->where(function ($q) use ($centreId): void {
                    $q->whereDoesntHave('employee')
                        ->orWhereHas('employee', fn ($e) => $e->where('etablissement_id', $centreId));
                });
            })
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($q): void {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('username', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.backoffice.users.users-index', ['users' => $users])
            ->layout('components.backoffice.layout.app', ['title' => __('Users')]);
    }
}
