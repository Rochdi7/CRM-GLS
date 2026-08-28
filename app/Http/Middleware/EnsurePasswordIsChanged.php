<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `users.must_change_password` was written everywhere (new login, password
 * regeneration) and read nowhere (audit SEC-06). A login carrying a
 * one-time password is sent to its profile until it picks its own.
 */
final class EnsurePasswordIsChanged
{
    /** Routes that stay reachable while the change is pending. */
    private const ALLOWED = [
        'backoffice.profile',
        'backoffice.profile.update',
        'backoffice.profile.password.update',
        'backoffice.profile.photo.update',
        'backoffice.profile.photo.destroy',
        'backoffice.logout',
        'backoffice.context.update',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->must_change_password) {
            return $next($request);
        }

        $route = $request->route()?->getName();

        if ($route !== null && (in_array($route, self::ALLOWED, true) || ! str_starts_with($route, 'backoffice.'))) {
            return $next($request);
        }

        return redirect()->route('backoffice.profile')
            ->with('warning', __('Please choose a new password before continuing.'));
    }
}
