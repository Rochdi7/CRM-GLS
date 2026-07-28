<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Auth\ForgotPasswordRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

/**
 * "Forgot password" — emails a reset link (backoffice-scoped, `users` broker).
 * Mail driver is `log` in dev, so the link lands in storage/logs/laravel.log.
 */
final class ForgotPasswordController extends Controller
{
    public function show(): View
    {
        return view('backoffice.auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        // Always report success to avoid leaking which emails exist.
        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
