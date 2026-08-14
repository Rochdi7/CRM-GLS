<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Users;

use App\Domain\Employees\Queries\GetUsersList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Users\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Users list + edit (name/email/username/is_active) + one-time password
 * regeneration. Replaces App\Livewire\Backoffice\Users\UsersIndex as the
 * active UI; that Livewire component is kept, unused, for rollback.
 *
 * Users are NEVER created here — only produced by EmployeeObserver when an
 * Employee is created — so there is deliberately no store()/create() action.
 *
 * Everything is gated on `users.assign-roles` (there is no separate
 * `users.update` permission — intentional, see docs/roles-and-permissions.md)
 * except the index itself, which only needs `users.view` (matches the
 * Livewire component's mount()).
 */
final class UserController extends Controller
{
    public function index(Request $request, GetUsersList $getUsersList): Response
    {
        $this->authorize('users.view');

        return Inertia::render('Backoffice/Users/Index', [
            'users' => $getUsersList(
                search: (string) $request->string('search'),
                perPage: $request->integer('perPage', GetUsersList::DEFAULT_PER_PAGE),
            ),
            'filters' => [
                'search' => (string) $request->string('search'),
                'perPage' => $request->integer('perPage', GetUsersList::DEFAULT_PER_PAGE),
            ],
            'perPageOptions' => GetUsersList::PER_PAGE_OPTIONS,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        // Second layer of defense in depth — the `permission:users.assign-roles`
        // route middleware is the first (matches the Livewire component's
        // authorize() call in both mount() and every mutation method).
        $this->authorize('users.assign-roles');

        $data = $request->validated();

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $data['username'] ?? null,
            'is_active' => $data['is_active'] ?? $user->is_active,
        ]);

        return back()->with('success', __('Utilisateur mis à jour.'));
    }

    /**
     * Generate a fresh one-time password and force a change on next login.
     * The plaintext password is returned ONLY in this response (flashed,
     * never persisted anywhere else, never written to any log) so the
     * frontend can display it once.
     */
    public function regeneratePassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('users.assign-roles');

        $plain = Str::password(12);
        $user->update(['password' => $plain, 'must_change_password' => true]);

        activity('authorization')
            ->causedBy($request->user())
            ->performedOn($user)
            ->log('password regenerated');

        return back()
            ->with('success', __('Mot de passe régénéré.'))
            ->with('regeneratedPassword', $plain);
    }
}
