<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Profile;

use App\Livewire\Backoffice\Concerns\WithPhoneCountry;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

/**
 * The signed-in user's own profile: edit name/email + linked employee phone,
 * and change password. No permission gate — any authenticated user manages
 * their own account (route sits behind `auth`).
 */
final class ProfilePage extends Component
{
    use WithPhoneCountry;

    public string $name = '';

    public string $email = '';

    public string $telephone = '';

    public string $whatsapp = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user()->load('employee');
        $this->name = $user->name;
        $this->email = (string) $user->email;
        $this->resetPhonePays(); // ensure a valid default country even with no stored phones
        $this->fillPhone('telephone', $user->employee?->telephone);
        $this->fillPhone('whatsapp', $user->employee?->whatsapp);
    }

    public function updateProfile(): void
    {
        $user = auth()->user();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'telephone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            ...$this->phonePaysRules(),
        ]);

        $user->update(['name' => $data['name'], 'email' => $data['email']]);

        // Keep the linked employee's contact info in sync.
        $user->employee?->update([
            'telephone' => $this->phoneValue('telephone'),
            'whatsapp' => $this->phoneValue('whatsapp'),
        ]);

        $this->dispatch('toast', type: 'success', message: __('Profile updated.'));
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        /** @var User $user */
        $user = auth()->user();
        $user->update(['password' => $this->password, 'must_change_password' => false]);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->dispatch('toast', type: 'success', message: __('Password changed.'));
    }

    public function render(): View
    {
        return view('livewire.backoffice.profile.profile-page', [
            'user' => auth()->user()->load('employee.etablissement'),
        ])->layout('components.backoffice.layout.app', ['title' => __('My Profile')]);
    }
}
