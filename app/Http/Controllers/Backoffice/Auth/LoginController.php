<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Auth\LoginRequest;
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

        return redirect()->intended(route('backoffice.dashboard'));
    }
}
