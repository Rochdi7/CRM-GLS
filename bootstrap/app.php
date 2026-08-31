<?php

use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Support\Errors\ErrorReference;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Auto-generates the day's (and upcoming) séances from every active
        // group's emploi du temps — see app/Console/Commands/GenerateSeances.php.
        $schedule->command('seances:generate')->dailyAt('08:00');
    })
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
            EnsureUserIsActive::class,
            EnsurePasswordIsChanged::class,
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Every error page shows a support reference (resources/views/errors/500)
        // and the SAME id is written into the log context, so "ça ne marche pas"
        // becomes a string the maintainer can grep for in laravel.log.
        $exceptions->context(fn (): array => ['error_id' => ErrorReference::current()]);

        // Reported 31/08/2026: a bare "500 Erreur serveur" made users report the
        // whole server as DOWN. The branded pages under resources/views/errors/
        // say instead that ONE action failed and the app is still running — but
        // Laravel only reaches them for classic page loads. The backoffice is
        // Inertia (§5), where an unhandled error arrives as an XHR whose HTML
        // body Inertia cannot render, leaving the user on a dead screen. So for
        // Inertia requests we answer with a real Inertia response, which the
        // client renders as a normal page inside the app shell.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if (app()->hasDebugModeEnabled() || ! $request->header('X-Inertia')) {
                return $response;
            }

            $status = $response->getStatusCode();

            // 419 has to bounce back rather than render: the token is stale, so
            // the only useful outcome is a fresh page that carries a fresh one.
            if ($status === 419) {
                return back()->with('error', __('Your session has expired. Please sign in again.'));
            }

            if (! in_array($status, [403, 404, 429, 500, 503], true)) {
                return $response;
            }

            // The React page renders inside BackofficeLayout, which needs the
            // authenticated shared props (auth.user, context). With no signed-in
            // user those are null and the shell would throw — turning the error
            // page into a second error. Guests keep the standalone Blade page,
            // which depends on nothing.
            if ($request->user() === null) {
                return $response;
            }

            return Inertia::render('Error', [
                'status' => $status,
                'errorId' => $status === 500 ? ErrorReference::current() : null,
            ])->toResponse($request)->setStatusCode($status);
        });
    })->create();
