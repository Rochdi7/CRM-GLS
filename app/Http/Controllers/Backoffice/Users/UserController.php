<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Users;

use App\Domain\Employees\Queries\GetUsersList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Users\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
 * Mutations are gated by UserPolicy (`users.assign-roles` + target/centre
 * rules — there is no separate `users.update` permission, intentional, see
 * docs/roles-and-permissions.md); the index only needs `users.view`.
 */
final class UserController extends Controller
{
    public function index(Request $request, GetUsersList $getUsersList, CurrentContext $context): Response
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
            // Hides the redundant Centre column once the context switcher is
            // on a single center (CLAUDE.md §5 centre-filter rule).
            'centerLocked' => ! $context->isAllCenters(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        // UserPolicy: permission + target not super-admin (unless actor is)
        // + not self + centre reach (audit SEC-01/03/04).
        $this->authorize('update', $user);

        $data = $request->validated();
        $isActive = (bool) ($data['is_active'] ?? $user->is_active);
        $deactivating = $user->is_active && ! $isActive;

        if ($deactivating) {
            $this->guardDeactivation($user);
        }

        DB::transaction(function () use ($user, $data, $isActive, $deactivating): void {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => $data['username'] ?? null,
                'is_active' => $isActive,
            ]);

            if ($deactivating) {
                $this->revokeSessions($user);
            }
        });

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
        $this->authorize('update', $user);

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

    /**
     * Deactivating must never lock the system: never yourself (the policy
     * already refuses self for everyone but a super-admin, whom Gate::before
     * lets through) and never the last active super-admin.
     */
    private function guardDeactivation(User $user): void
    {
        if ($user->is(auth()->user())) {
            throw ValidationException::withMessages([
                'is_active' => __('Vous ne pouvez pas désactiver votre propre compte.'),
            ]);
        }

        if ($user->hasRole(Role::SUPER_ADMIN)
            && User::query()->role(Role::SUPER_ADMIN)->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages([
                'is_active' => __('Impossible de désactiver le dernier super administrateur actif.'),
            ]);
        }
    }

    /**
     * A deactivated login is out immediately: its "remember me" cookie
     * stops matching and, with the database session driver, its open
     * sessions are dropped. EnsureUserIsActive covers every driver on the
     * next request.
     */
    private function revokeSessions(User $user): void
    {
        $user->setRememberToken(Str::random(60));
        $user->save();

        if (config('session.driver') === 'database') {
            DB::table((string) config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }
    }
}
