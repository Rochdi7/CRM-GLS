<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Requests\Backoffice\Profile\UpdatePasswordRequest;
use App\Http\Requests\Backoffice\Profile\UpdatePhotoRequest;
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
                'photo_url' => $user->employee->getFirstMediaUrl('photo') ?: null,
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

    /**
     * Replace the signed-in user's avatar. Writes into the linked Employee's
     * single-file "photo" media collection — the very collection the
     * Employees module uses (EmployeeController::storePhoto), so the header
     * avatar (`auth.user.photoUrl`, fed by Employee::avatarUrl()) picks the
     * new picture up with no extra plumbing. A user with no linked Employee
     * record has nowhere to store one, so the upload is refused.
     */
    public function updatePhoto(UpdatePhotoRequest $request): RedirectResponse
    {
        $employee = $request->user()->employee;

        if ($employee === null) {
            return back()->withErrors(['photo' => __('No employee record is linked to your account.')]);
        }

        $photo = $request->file('photo');

        // Clear explicitly rather than relying on singleFile()'s implicit
        // pruning: that prune reads the collection through the model's
        // CACHED `media` relation, and HandleInertiaRequests::share() has
        // usually already loaded it on this very instance (via
        // Employee::avatarUrl()). With a stale cached relation the prune
        // sees the wrong count and the old picture survives, leaving two
        // rows in a single-file collection.
        $employee->clearMediaCollection('photo');

        $employee->addMedia($photo->getRealPath())
            ->usingFileName('photo-'.$employee->id.'.'.$photo->getClientOriginalExtension())
            ->toMediaCollection('photo');

        return back()->with('success', __('Photo updated.'));
    }

    /**
     * Drop the avatar and fall back to the gendered default picture
     * (Employee::avatarUrl()). No-op when nothing was uploaded.
     */
    public function deletePhoto(Request $request): RedirectResponse
    {
        $request->user()->employee?->clearMediaCollection('photo');

        return back()->with('success', __('Photo removed.'));
    }
}
