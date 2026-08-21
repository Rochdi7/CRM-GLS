<?php

namespace App\Providers;

use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Observers\EmployeeObserver;
use App\Services\Context\CurrentContext;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
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
     * [ability => model class] pairs the super-admin bypass must NOT grant.
     *
     * CaisseTransfer@validate is recipient-only by design: the employee whose
     * till is being credited is the only one who may confirm they received
     * the money. If Gate::before short-circuited it, a super-admin could move
     * cash between two other people's tills with nobody agreeing — exactly
     * the fraud the two-step transfer prevents. A super-admin can still see,
     * request, edit and cancel transfers.
     *
     * ⚠ Keyed by MODEL, not by bare ability name: SeancePolicy also has a
     * `validate` method, and super-admins must keep bypassing that one.
     *
     * @var array<string, string>
     */
    private const NO_SUPER_ADMIN_BYPASS = [
        'validate' => CaisseTransfer::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Auto-generates login credentials when an Employee is created
        // (gls-crm-laravel-structure.md §5).
        Employee::observe(EmployeeObserver::class);

        // Super-admin bypasses every gate/policy/permission check
        // (Spatie-recommended pattern — never hasRole() checks in code),
        // EXCEPT the abilities listed below.
        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            $excludedModel = self::NO_SUPER_ADMIN_BYPASS[$ability] ?? null;

            if ($excludedModel !== null) {
                $subject = $arguments[0] ?? null;
                $subjectClass = is_object($subject) ? $subject::class : (is_string($subject) ? $subject : null);

                if ($subjectClass !== null && is_a($subjectClass, $excludedModel, true)) {
                    // Returning null defers to the policy instead of granting —
                    // the policy still runs and decides on its own terms.
                    return null;
                }
            }

            return $user->hasRole(Role::SUPER_ADMIN) ? true : null;
        });

        // Point the password-reset email at the Backoffice reset page.
        ResetPassword::createUrlUsing(
            fn (User $user, string $token): string => route('backoffice.password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]),
        );
    }
}
