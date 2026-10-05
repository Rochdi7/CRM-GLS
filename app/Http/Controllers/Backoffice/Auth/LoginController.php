<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class LoginController extends Controller
{
    /**
     * Show the Backoffice login page. `status` (e.g. after a password reset)
     * reaches the page via the shared `flash.status` prop — see
     * HandleInertiaRequests.
     */
    public function show(): Response
    {
        return Inertia::render('Backoffice/Auth/Login');
    }

    /**
     * Handle a Backoffice login attempt.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Welcome clip, shown once by the backoffice shell. `put()`, not
        // `flash()`: an intended/canonical redirect between here and the
        // first Inertia render would drop a flash. Consumed by `pull()` in
        // HandleInertiaRequests. Only employees working in Casablanca or
        // Kénitra get it (05/10/2026).
        if ($this->joueLeClipDeBienvenue($request->user())) {
            $request->session()->put('bienvenue', true);
        }

        return redirect()->intended(route('backoffice.dashboard'));
    }

    /**
     * True when the user's employee is assigned (pivot or primary column)
     * to a Casablanca or Kénitra centre. `k_nitra` matches the city with or
     * without its accent.
     */
    private function joueLeClipDeBienvenue(?User $user): bool
    {
        $employee = $user?->employee;

        if ($employee === null) {
            return false;
        }

        $villes = fn ($q) => $q->where('ville', 'ilike', 'casablanca')
            ->orWhere('ville', 'ilike', 'k_nitra');

        return $employee->etablissements()->where($villes)->exists()
            || $employee->etablissement()->where($villes)->exists();
    }
}
