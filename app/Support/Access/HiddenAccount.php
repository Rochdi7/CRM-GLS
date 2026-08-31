<?php

declare(strict_types=1);

namespace App\Support\Access;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * The maintainer / developer login — a super-admin that the UI never shows.
 *
 * Why it exists: the system's developer needs a full-access account to
 * diagnose problems on the live database, but he is not GLS staff. Leaving
 * him in the Employés / Utilisateurs / Caisses lists makes the roster wrong
 * (28 people where the school has 27) and puts a till in the finance screens
 * that never holds a dirham.
 *
 * ⚠ This is a DISPLAY filter, never a recording or authorization bypass.
 * Three things must stay true, and none of them may be "optimised" away:
 *
 *  1. Everything this account does is still written to the audit journal in
 *     full — same IP, same user-agent, same frozen `causer_label` as anyone
 *     else. An unrecorded privileged login would be a permanent blind spot
 *     on the most powerful account in the system, which is precisely what
 *     the journal exists to prevent (docs/audit-journal.md, CLAUDE.md §11).
 *     The journal page hides it behind the « Inclure le compte technique »
 *     toggle, so a reader can always bring it back.
 *  2. Server-side authorization is unchanged — it holds `super-admin` and
 *     `Gate::before` treats it like any other super-admin, including the
 *     deliberate NON-bypass on cash-transfer validation (CLAUDE.md §11).
 *  3. The account stays visible TO ITSELF (see `hides()`), so the developer's
 *     own profile, context switcher and center resolution keep working. The
 *     rule asked for is "others cannot see me", not "nothing can resolve me"
 *     — and a globally invisible employee row would 500 its own profile page.
 *
 * Single source of truth: `AuditLogRegistry::DEVELOPER_EMAIL` aliases
 * `self::EMAIL`, so the address is written down exactly once.
 */
final class HiddenAccount
{
    /**
     * The maintainer's login address.
     *
     * ⚠ Changing this does NOT retro-hide anything already journalled under
     * the old address, and does not move the role — it only changes which
     * row the UI filters out from here on.
     */
    public const EMAIL = 'rochdi.karouali1234@gmail.com';

    /**
     * Whether the CURRENT viewer should have the account hidden from them.
     *
     * False only for the maintainer himself — he is allowed to see his own
     * row, which is what keeps his profile page and center resolution alive.
     */
    public static function hides(): bool
    {
        return ! self::isViewer();
    }

    /**
     * Is the authenticated user the maintainer?
     */
    public static function isViewer(?Authenticatable $user = null): bool
    {
        $user ??= Auth::user();

        return $user instanceof User && $user->email === self::EMAIL;
    }

    /**
     * Does this ability target one of the maintainer's own records, viewed
     * by somebody else?
     *
     * Called from `Gate::before` ABOVE the super-admin bypass, so it holds
     * for the CEO too. The list queries already filter these rows out, but
     * a hand-typed URL goes straight to a controller's `authorize()` with
     * the model resolved by route-model binding — without this, opening
     * /backoffice/caisses/<his till> rendered his page in full.
     *
     * ⚠ Recognises the maintainer's OWN rows only (his user, his employee
     * record, his till). It is NOT a rule about money: a real staff record
     * that merely happens to reference him stays visible, and no business
     * record of GLS's is ever hidden by this.
     */
    public static function denies(?Authenticatable $viewer, mixed $subject): bool
    {
        if (! $subject instanceof \Illuminate\Database\Eloquent\Model) {
            return false;
        }

        // The maintainer always reaches his own records — otherwise his
        // profile page and context resolution 403 on himself.
        if (self::isViewer($viewer)) {
            return false;
        }

        return match (true) {
            $subject instanceof User => $subject->email === self::EMAIL,
            $subject instanceof \App\Models\Employee => self::isMaintainerEmployee($subject),
            $subject instanceof \App\Models\Caisse => self::isMaintainerCaisse($subject),
            default => false,
        };
    }

    private static function isMaintainerEmployee(\App\Models\Employee $employee): bool
    {
        // Through the `user` relation, not `employees.email`: that column is
        // nullable, and the login address is the single identifying fact.
        // withoutGlobalScopes() because Employee carries HiddenAccountScope —
        // see hideCaisses() for what that blindness costs.
        return User::query()
            ->whereKey($employee->getAttribute('user_id'))
            ->where('email', self::EMAIL)
            ->exists();
    }

    private static function isMaintainerCaisse(\App\Models\Caisse $caisse): bool
    {
        $employeeId = $caisse->getAttribute('responsable_employee_id');

        if ($employeeId === null) {
            return false;
        }

        return \App\Models\Employee::withoutGlobalScopes()
            ->whereKey($employeeId)
            ->whereHas('user', fn ($q) => $q->where('email', self::EMAIL))
            ->exists();
    }
    /**
     * Hide the maintainer from a query over `users`.
     *
     * Matched on the e-mail rather than a memoized id on purpose: no cache to
     * go stale between requests, and it stays correct on a database that was
     * just re-seeded under new ids.
     *
     * @param  Builder<User>  $query
     */
    public static function hideUsers(Builder $query, string $table = 'users'): void
    {
        if (! self::hides()) {
            return;
        }

        $query->where($table.'.email', '!=', self::EMAIL);
    }

    /**
     * Hide the maintainer's staff record from a query over `employees`.
     *
     * Resolved through the `user` relation, NOT `employees.email`: that
     * column is nullable (teachers have no address), and `email != '…'` is
     * NULL — therefore false — for every NULL row, which would silently drop
     * all 63 teachers from every list.
     *
     * @param  Builder<\App\Models\Employee>  $query
     */
    public static function hideEmployees(Builder $query): void
    {
        if (! self::hides()) {
            return;
        }

        $query->whereDoesntHave('user', fn ($q) => $q->where('email', self::EMAIL));
    }

    /**
     * Hide the till auto-provisioned for the maintainer's employee record.
     *
     * The row itself is never deleted (money records and the accounts they
     * hang off are permanent — CLAUDE.md §11); it is only filtered out of
     * the finance screens.
     *
     * ⚠ `withoutGlobalScopes()` on the `responsable` subquery is REQUIRED,
     * not tidying. `Employee` is `#[ScopedBy(HiddenAccountScope::class)]`,
     * and that scope applies inside a nested `whereDoesntHave` too — so the
     * subquery would look for the maintainer's employee row in a set the
     * scope has ALREADY removed him from, find nothing, and report "this
     * caisse has no maintainer responsable" for the one caisse that does.
     * The till then passed the filter and was listed on « Caisse globale » /
     * « Comptes de caisse » with an empty Responsable column (the relation
     * was scoped away at render time too), which is the leak reported on
     * 30/08/2026. Any future filter that reaches the maintainer THROUGH
     * `employees` must drop the scope the same way.
     *
     * @param  Builder<\App\Models\Caisse>  $query
     */
    public static function hideCaisses(Builder $query): void
    {
        if (! self::hides()) {
            return;
        }

        $query->whereDoesntHave(
            'responsable',
            fn ($q) => $q->withoutGlobalScopes()
                ->whereHas('user', fn ($u) => $u->where('email', self::EMAIL)),
        );
    }
}
