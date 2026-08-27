<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A deactivated login (`users.is_active = false`) is thrown out on its very
 * next request, not only at its next login — LoginRequest alone left an
 * already-open session free to keep entering payments (audit SEC-02).
 */
final class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('backoffice.login')
                ->withErrors(['login' => __('Ce compte est désactivé.')]);
        }

        return $next($request);
    }
}
