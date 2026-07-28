<?php

namespace App\Providers;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Observers\EmployeeObserver;
use App\Services\Context\CurrentContext;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Active working context (selected year + center) — one instance
        // per request, session-backed.
        $this->app->singleton(CurrentContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auto-generates login credentials when an Employee is created
        // (gls-crm-laravel-structure.md §5).
        Employee::observe(EmployeeObserver::class);

        // Super-admin bypasses every gate/policy/permission check
        // (Spatie-recommended pattern — never hasRole() checks in code).
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole(Role::SUPER_ADMIN) ? true : null;
        });

        // Point the password-reset email at the Backoffice reset page.
        ResetPassword::createUrlUsing(
            fn (User $user, string $token): string => route('backoffice.password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]),
        );

        // Expose the active context to every view as $context. Resolved lazily
        // per-render via a view composer so the session is always available.
        View::composer('*', function ($view): void {
            $view->with('context', app(CurrentContext::class));
        });
    }
}
