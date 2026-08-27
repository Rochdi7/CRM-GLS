<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Context\CurrentContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Shares only safe, minimal data with every Inertia page (migration plan
 * docs/inertia-react-migration-plan.md §4). No full Eloquent models and no
 * sensitive fields are shared globally — pages that need more request it
 * explicitly through their own controller props.
 */
final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'photoUrl' => $user->employee?->avatarUrl() ?? asset('assets/images/avatar/defaultman.webp'),
                ],
                'permissions' => $user === null ? [] : $user->getAllPermissions()->pluck('name')->values(),
                // super-admin bypasses every permission check via Gate::before
                // and therefore holds no permissions directly (see CLAUDE.md
                // §16) — the frontend nav needs this explicit flag to show
                // every gated item, the same way Blade's @can already
                // resolves true for this role server-side.
                'isSuperAdmin' => $user !== null && $user->hasRole('super-admin'),
            ],
            // Lazy: only resolved when an Inertia page/partial-reload actually
            // asks for it, and never at all for guests — CurrentContext's
            // year/center/available-list queries are real DB round-trips,
            // not free (migration plan §"Shared Inertia props").
            'context' => $user === null ? null : fn () => (function (): array {
                $context = app(CurrentContext::class);

                return [
                    'anneeScolaireId' => $context->anneeScolaireId(),
                    'etablissementId' => $context->etablissementId(),
                    'isAllCenters' => $context->isAllCenters(),
                    'canSwitchCenter' => $context->canSwitchCenter(),
                    'canPickAllCenters' => $context->canPickAllCenters(),
                    'currentCenter' => $context->etablissement() === null ? null : [
                        'id' => $context->etablissement()->id,
                        'name' => $context->etablissement()->nom_centre,
                    ],
                    'currentAcademicYear' => $context->anneeScolaire() === null ? null : [
                        'id' => $context->anneeScolaire()->id,
                        'name' => $context->anneeScolaire()->nom,
                    ],
                    'availableCenters' => $context->availableCentres()
                        ->map(fn ($centre) => ['id' => $centre->id, 'name' => $centre->nom_centre])
                        ->values(),
                    'availableAcademicYears' => $context->availableAnnees()
                        ->map(fn ($annee) => ['id' => $annee->id, 'name' => $annee->nom])
                        ->values(),
                ];
            })(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                // Laravel's password-broker convention ($status = Password::sendResetLink(...))
                // flashes a translated string under this key — ForgotPasswordController and
                // ResetPasswordController already do `->with('status', ...)` unchanged.
                'status' => fn () => $request->session()->get('status'),
                // Set once by GroupController@changerEnseignant: the group's
                // emploi du temps was stopped by a teacher changeover and a
                // new one must be created. `pull()` for the same reason as
                // newEmployeeCredentials below — the banner is a one-time
                // notice, not a state that should reappear on every later
                // visit to the group page.
                'emploiDuTempsArrete' => fn () => $request->session()->pull('emploiDuTempsArrete'),
                // One-time login credentials for a just-created employee
                // (Backoffice\Employees\EmployeeController::store() →
                // EmployeeObserver → EmployeeCredentialService). Shown once by
                // the React modal, never persisted anywhere else, never logged.
                //
                // `pull()`, not `get()` — Laravel's flash data otherwise
                // survives for the ENTIRE next request, not just "the next
                // render": any subsequent Inertia visit in that window (a
                // search/filter/pagination reload, a plain back/refresh)
                // would still see it and the modal would reopen with a
                // secret the admin already dismissed. `pull()` reads AND
                // forgets it atomically, so it can only ever render once,
                // matching the Livewire original's component-instance-scoped
                // (never session-rebroadcast) equivalent.
                'newEmployeeCredentials' => function () use ($request): ?array {
                    $username = $request->session()->pull('new_employee_username');
                    $password = $request->session()->pull('new_employee_password');

                    return $username === null && $password === null
                        ? null
                        : ['username' => $username, 'password' => $password];
                },
                // One-time regenerated password for an existing user
                // (Backoffice\Users\UserController::regeneratePassword()).
                // Shown once by the React modal, never persisted anywhere
                // else, never logged in plaintext (only the audit-log entry
                // "password regenerated" is written, with no password value).
                // `pull()` for the same one-render-only reason as above.
                'regeneratedPassword' => fn () => $request->session()->pull('regeneratedPassword'),
                // The registration just created by InscriptionController@store,
                // handed to the Inscriptions list page so it can offer
                // « Voulez-vous ajouter un paiement ? » and open the payment
                // modal already scoped to it. `pull()` for the same
                // one-render-only reason as newEmployeeCredentials above: the
                // prompt must not reappear on the next search/pagination
                // reload of the same page.
                'nouvelleInscription' => fn () => $request->session()->pull('nouvelleInscription'),
            ],
            'locale' => app()->getLocale(),
        ];
    }
}
