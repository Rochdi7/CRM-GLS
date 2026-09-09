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
 *  3. Each account still RESOLVES ITSELF, so its own profile, context
 *     switcher and center resolution keep working — via `denies()` for
 *     record access and `User::employee()` (which drops the global scope)
 *     for identity. The rule asked for is "others cannot see me", not
 *     "nothing can resolve me": a globally invisible employee row would
 *     leave its own profile page blank.
 *
 * ⚠ SEEING the hidden rows and BEING one of them are two different
 * questions, and conflating them is the leak reported on 09/09/2026 (the
 * Encaissements « Caisse » filter listed both « Rochdi Karouali » tills to
 * whichever of the two accounts was signed in). `isMaintainer()` — `EMAIL`
 * ALONE — answers "may bypass the display filter", and drives `hides()`.
 * `isHidden()` answers "is one of the hidden logins", and only ever grants
 * an account access to its OWN records. Never key `hides()` on `emails()`.
 *
 * TWO logins belong to him and both are hidden (`self::emails()`): the
 * technical account `EMAIL` and the GLS-domain staff account `STAFF_EMAIL`,
 * once seeded as a real « Responsable de système ». Every DISPLAY filter
 * matches the LIST; `EMAIL` alone stays the audit-journal identity
 * (`AuditLogRegistry::DEVELOPER_EMAIL` aliases it) and the address
 * `MaintainerUserSeeder` provisions — never widen that constant instead.
 *
 * ⚠ Both accounts hold `super-admin`. Hiding them is display-only, so GLS
 * keeps at least one VISIBLE super-admin of its own (the CEO,
 * rafik@glszentrum.com) — never hide the last visible one, or the
 * Autorisations screen shows nobody who can grant anything.
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
     * The GLS-domain login of the same person.
     *
     * Historically this was seeded as ordinary staff (« Responsable de
     * système », EMP-004) on the assumption that the maintainer's technical
     * account and his staff account were two different people. They are not:
     * both belong to the developer of the system, so both are filtered from
     * the interface (decided 07/09/2026, after « Caisse globale » listed two
     * ROCHDI KAROUALI tills side by side).
     *
     * ⚠ It is deliberately NOT `EMAIL`. That constant is the maintainer's
     * identity for the AUDIT JOURNAL (`AuditLogRegistry::DEVELOPER_EMAIL`
     * aliases it, and « Inclure le compte technique » toggles exactly that
     * one account) and for `MaintainerUserSeeder`, which provisions it.
     * Widening `EMAIL` would silently fold a second account into both.
     */
    public const STAFF_EMAIL = 'rochdi.karouali@glszentrum.com';

    /**
     * Every login the interface must not show.
     *
     * This — not `EMAIL` — is what every DISPLAY filter matches on. Adding a
     * further address here hides it everywhere at once, because all the
     * filters below and `HiddenAccountScope` funnel through this list.
     *
     * @return list<string>
     */
    public static function emails(): array
    {
        return [self::EMAIL, self::STAFF_EMAIL];
    }

    /**
     * Whether the CURRENT viewer should have the accounts hidden from them.
     *
     * False for the MAINTENANCE IDENTITY alone (`EMAIL`) — the one account
     * allowed to see both hidden rows, which is what keeps its own profile
     * page and center resolution alive.
     *
     * ⚠ `EMAIL`, never `emails()`. Those are two different questions and
     * conflating them is the leak reported on 09/09/2026: signed in as the
     * GLS-domain staff account, the « Caisse » filter of Encaissements
     * listed BOTH « Rochdi Karouali » tills, because being *in* the hidden
     * list was read as permission to *see* the hidden list. `STAFF_EMAIL` is
     * a staff login of the same person, not the maintenance identity — the
     * same distinction `GroupPolicy@updateClosed`,
     * `MAINTAINER_ONLY_ABILITIES` and `AuditLogRegistry::DEVELOPER_EMAIL`
     * already draw. A hidden account that is not the maintenance identity is
     * hidden from ITSELF too, and reaches its own records through
     * `seesOwnRecords()` below rather than by switching the filter off.
     */
    public static function hides(): bool
    {
        return ! self::isMaintainer();
    }

    /**
     * Is the authenticated user the MAINTENANCE IDENTITY — the single
     * account that may see the hidden rows?
     *
     * This is the "may bypass the display filter" question. For "is this
     * account one of the hidden ones" use `isHidden()`.
     */
    public static function isMaintainer(?Authenticatable $user = null): bool
    {
        $user ??= Auth::user();

        return $user instanceof User && $user->email === self::EMAIL;
    }

    /**
     * Is this account one of the hidden logins?
     *
     * The membership question — used by `denies()` so a hidden account still
     * reaches its OWN records (profile, context resolution) without being
     * granted sight of the other one.
     */
    public static function isHidden(?Authenticatable $user = null): bool
    {
        $user ??= Auth::user();

        return $user instanceof User && in_array($user->email, self::emails(), true);
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

        // The MAINTENANCE IDENTITY reaches every hidden record.
        if (self::isMaintainer($viewer)) {
            return false;
        }

        // A hidden account that is NOT the maintenance identity still
        // reaches its OWN records — otherwise its profile page and context
        // resolution 403 on itself. It does NOT thereby reach the other
        // hidden account's records: `isHidden()` answers membership,
        // `isMaintainer()` answers sight (09/09/2026).
        if (self::isHidden($viewer) && self::belongsTo($subject, $viewer)) {
            return false;
        }

        return match (true) {
            $subject instanceof User => in_array($subject->email, self::emails(), true),
            $subject instanceof \App\Models\Employee => self::isMaintainerEmployee($subject),
            $subject instanceof \App\Models\Caisse => self::isMaintainerCaisse($subject),
            default => false,
        };
    }

    /**
     * Is this record the VIEWER's own — their user row, their employee row,
     * or a caisse they are responsable of?
     *
     * Scoped to the viewer specifically, never to "any hidden account", so
     * one hidden login can never see the other's till.
     */
    private static function belongsTo(mixed $subject, ?Authenticatable $viewer): bool
    {
        if (! $viewer instanceof User) {
            return false;
        }

        $employeeId = \App\Models\Employee::withoutGlobalScopes()
            ->where('user_id', $viewer->getKey())
            ->value('id');

        return match (true) {
            $subject instanceof User => $subject->getKey() === $viewer->getKey(),
            $subject instanceof \App\Models\Employee => $employeeId !== null
                && $subject->getKey() === $employeeId,
            $subject instanceof \App\Models\Caisse => $employeeId !== null
                && $subject->getAttribute('responsable_employee_id') === $employeeId,
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
            ->whereIn('email', self::emails())
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
            ->whereHas('user', fn ($q) => $q->whereIn('email', self::emails()))
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
        // ⚠ OPT-IN, AND IT CANNOT BE A GLOBAL SCOPE. `Employee` hides
        // itself automatically (HiddenAccountScope), so a page written next
        // month is safe by default. `User` cannot do the same: Laravel's
        // EloquentUserProvider builds its credential lookup with
        // `newQuery()`, so a global scope there would apply DURING
        // authentication and lock the maintainer out of his own login.
        //
        // Therefore EVERY query that lists, counts or exposes `users` must
        // call this by hand — GetUsersList and GetActivityLogList already
        // do. When adding a screen that surfaces a user (a picker, an
        // export, a stat card), call it or the account reappears.
        //
        // ⚠ The same applies to `caisses` via hideCaisses(). A global scope
        // was TRIED on Caisse (08/09/2026) and REVERTED: hides() is true
        // whenever nobody is authenticated, so in console context the
        // maintainer's tills vanished — `caisse:verifier-coherence` then
        // reported them as « aucune caisse (caisses:provision la créera) »,
        // and following that advice would have attempted duplicate tills
        // against `caisses_une_caissiere_par_employe`. A finance job that
        // silently skips two real tills is worse than the display leak it
        // would have closed. Keep hideCaisses() opt-in.

        if (! self::hides()) {
            return;
        }

        $query->whereNotIn($table.'.email', self::emails());
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

        $query->whereDoesntHave('user', fn ($q) => $q->whereIn('email', self::emails()));
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
                ->whereHas('user', fn ($u) => $u->whereIn('email', self::emails())),
        );
    }
}
