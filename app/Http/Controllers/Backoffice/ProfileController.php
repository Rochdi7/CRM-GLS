<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Requests\Backoffice\Profile\UpdatePasswordRequest;
use App\Http\Requests\Backoffice\Profile\UpdateProfileRequest;
use App\Models\User;
use App\Support\Phone\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The signed-in user's own profile — no permission gate (any authenticated
 * user manages their own account; route sits behind `auth`). Replaces
 * App\Livewire\Backoffice\Profile\ProfilePage as the active UI; that
 * Livewire component is kept, unused, for rollback (Phase 10 removes it).
 */
final class ProfileController
{
    public function show(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user()->load('employee.etablissement');

        [$telephonePays, $telephone] = Countries::split($user->employee?->telephone);
        [$whatsappPays, $whatsapp] = Countries::split($user->employee?->whatsapp);

        // One shared country selector drives both phone fields, exactly like
        // the Livewire version's WithPhoneCountry trait: the first non-empty
        // stored number's country wins, otherwise the app default (Morocco).
        $phonePays = $user->employee?->telephone
            ? $telephonePays
            : ($user->employee?->whatsapp ? $whatsappPays : Countries::DEFAULT);

        return Inertia::render('Backoffice/Profile/Index', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->map(fn ($role) => $role->displayLabel())->values(),
            ],
            'employee' => $user->employee === null ? null : [
                'reference' => $user->employee->reference,
                'categorie' => $user->employee->categorie,
                'sexe' => $user->employee->sexe,
                'email' => $user->employee->email,
                'date_naissance' => $user->employee->date_naissance?->format('d/m/Y'),
                'date_embauche' => $user->employee->date_embauche?->format('d/m/Y'),
                'centre' => $user->employee->etablissement?->nom_centre,
            ],
            'phonePays' => $phonePays,
            'telephone' => $telephone,
            'whatsapp' => $whatsapp,
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->update(['name' => $data['name'], 'email' => $data['email']]);

        // Keep the linked employee's contact info in sync (no-op if the
        // authenticated user has no linked Employee record).
        $user->employee?->update([
            'telephone' => Countries::join($data['phone_pays'], $data['telephone'] ?? null),
            'whatsapp' => Countries::join($data['phone_pays'], $data['whatsapp'] ?? null),
        ]);

        return back()->with('success', __('Profile updated.'));
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        // No manual Hash::make() — User::casts() already hashes `password`
        // on assignment (matches the Livewire ProfilePage this replaces).
        $user->update([
            'password' => $request->validated('password'),
            'must_change_password' => false,
        ]);

        return back()->with('success', __('Password changed.'));
    }
}
