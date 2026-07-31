<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Backoffice is the only authenticated area for now: guests hitting
        // protected pages go to the Backoffice login; authenticated users
        // hitting guest-only pages (login) go to the dashboard.
        $middleware->redirectGuestsTo(fn () => route('backoffice.login'));
        $middleware->redirectUsersTo(fn () => route('backoffice.dashboard'));

        // Spatie Permission route middleware (docs/roles-and-permissions.md).
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Inertia + React (docs/inertia-react-migration-plan.md) — shares
        // props on requests Inertia actually serves.
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
