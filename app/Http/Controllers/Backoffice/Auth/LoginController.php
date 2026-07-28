<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Auth\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class LoginController extends Controller
{
    /**
     * Show the Backoffice login page.
     */
    public function show(): View
    {
        return view('backoffice.auth.login');
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
