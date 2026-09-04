<?php

namespace App\Providers;

use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Remboursement;
use App\Models\Role;
use App\Models\User;
use App\Observers\EmployeeObserver;
use App\Observers\EtablissementObserver;
use App\Services\Context\CurrentContext;
use App\Support\Access\HiddenAccount;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
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
        // Annuler un remboursement recredite la caisse. La policy refuse une
        // ligne DEJA annulee — sans cette exclusion, Gate::before accorde
        // tout au super-admin et ce garde-fou devient injoignable : deux
        // clics recreditent la caisse deux fois pour une seule sortie
        // (03/09/2026). La permission reste super-admin ; c'est l'etat de la
        // ligne, pas le role, que la policy verifie ici.
        'cancel' => Remboursement::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Hard-refuses `migrate:fresh`, `migrate:refresh`, `migrate:reset`
        // and `db:wipe` in production — no interactive prompt to click
        // through. On 21/08/2026 a `migrate:fresh --seed` on the VPS dropped
        // all 41 production tables past the "APPLICATION IN PRODUCTION"
        // confirmation; the data survived only because of the 02:30 pg_dump.
        // Production migrations are `php artisan migrate --force` ONLY
        // (CLAUDE.md §17).
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Auto-generates login credentials when an Employee is created
        // (gls-crm-laravel-structure.md §5).
        Employee::observe(EmployeeObserver::class);

        // A centre owns one account per non-cash payment method (TPE /
        // Chèque / Virement), provisioned with it — CLAUDE.md §11.
        Etablissement::observe(EtablissementObserver::class);

        // Super-admin bypasses every gate/policy/permission check
        // (Spatie-recommended pattern — never hasRole() checks in code),
        // EXCEPT the abilities listed below.
        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            // The maintainer's own records are invisible to everyone but
            // himself (App\Support\Access\HiddenAccount) — and that has to
            // be decided HERE, above the bypass below, or a super-admin sails
            // straight past it. The list queries filter him out, but a
            // hand-typed URL (/backoffice/caisses/<his till>) reaches the
            // controller's authorize() directly and would otherwise render
            // his page. Returning false denies outright rather than deferring
            // to the policy, which cannot see the rule.
            if (HiddenAccount::denies($user, $arguments[0] ?? null)) {
                return false;
            }

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
