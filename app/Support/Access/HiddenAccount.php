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
     * @param  Builder<\App\Models\Caisse>  $query
     */
    public static function hideCaisses(Builder $query): void
    {
        if (! self::hides()) {
            return;
        }

        $query->whereDoesntHave(
            'responsable.user',
            fn ($q) => $q->where('email', self::EMAIL),
        );
    }
}
