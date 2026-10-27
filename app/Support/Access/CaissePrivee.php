<?php

declare(strict_types=1);

namespace App\Support\Access;

use App\Models\Caisse;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * A PRIVATE till — one that only its own responsable may see on the
 * network-wide finance screens (« Caisse globale », « Comptes de caisse »).
 *
 * Asked for on 27/10/2026: the CEO's personal till (Mohammed Rafik,
 * rafik@glszentrum.com) carries the bulk of the imported money and showed on
 * both tabs to every role that can open them (the five management roles read
 * « Comptes de caisse », every `cash.view` holder reads « Caisse globale »).
 * That figure is the owner's business, not a cashier's.
 *
 * ⚠ DISPLAY filter only, same family as HiddenAccount / DormantTill:
 *
 *  - the till's OWNER is the ONLY viewer — he still sees his own row.
 *    ⚠ Unlike every other display filter, NOBODY else is exempt: neither
 *    the maintenance identity (HiddenAccount::EMAIL) nor the GLS-domain
 *    staff account (HiddenAccount::STAFF_EMAIL) — the owner's decision,
 *    27/10/2026;
 *  - the row is removed at the QUERY funnel, so the cards and totals that
 *    sit above the lists drop it too — a total must be computed on the same
 *    rows it chapeaute (CLAUDE.md §11), otherwise the screen contradicts
 *    itself;
 *  - no money rule changes: `caisses.solde`, the ledger, the ventilation,
 *    transfers INTO that till (it remains a valid destination — a hidden
 *    destination could not receive cash, see HiddenAccount::STAFF_EMAIL) and
 *    the journal are untouched. It is never an authorization bypass.
 *
 * Which tills are private is a constant list of LOGIN addresses, matched
 * through `responsable.user.email` like HiddenAccount — never a name (a
 * namesake must stay visible) and never a role (a role decides what one may
 * DO, not whose drawer one may look at).
 */
final class CaissePrivee
{
    /**
     * Logins whose personal till is visible to its owner alone.
     *
     * @var list<string>
     */
    public const EMAILS = ['rafik@glszentrum.com'];

    /**
     * Logins whose personal till is listed for NOBODY on these two tabs —
     * not even its owner (owner's decision, 27/10/2026). The technical
     * account's till never holds a dirham; HiddenAccount already hides it
     * from everyone else, this also drops it from the technical account's
     * own view of « Caisse globale » / « Comptes de caisse ». Display only:
     * HiddenAccount::denies() still lets the account reach its own records.
     *
     * @var list<string>
     */
    public const EMAILS_MASQUEES_POUR_TOUS = [HiddenAccount::EMAIL];

    /**
     * Remove every private till the viewer does not own.
     *
     * ⚠ `withoutGlobalScopes()` on the `responsable` subquery is REQUIRED:
     * Employee carries HiddenAccountScope, which applies inside a nested
     * whereHas too (CLAUDE.md §11, the 30/08/2026 trap).
     *
     * @param  Builder<Caisse>  $query
     */
    public static function hide(Builder $query, ?Authenticatable $viewer = null): void
    {
        $viewer ??= Auth::user();

        $viewerId = $viewer instanceof User ? $viewer->getKey() : null;

        $query->whereDoesntHave(
            'responsable',
            fn ($r) => $r->withoutGlobalScopes()
                ->whereHas('user', fn ($u) => $u->whereIn('email', self::EMAILS_MASQUEES_POUR_TOUS)),
        );

        $query->whereDoesntHave(
            'responsable',
            fn ($r) => $r->withoutGlobalScopes()
                ->whereHas('user', fn ($u) => $u
                    ->whereIn('email', self::EMAILS)
                    ->when($viewerId !== null, fn ($u) => $u->whereKeyNot($viewerId))),
        );
    }
}
