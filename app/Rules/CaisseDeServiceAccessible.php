<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Caisse;
use App\Services\Authorization\CenterAccessService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

/**
 * Transfer destinations only: the submitted till must be held by somebody
 * ASSIGNED to a centre the acting user works in.
 *
 * The server-side twin of GetCaisseTransfersList::caisseOptions(), and it
 * must stay in step with it — a destination the screen offers has to be one
 * the request accepts, or the user is refused a till the page had just
 * listed (reported 04/09/2026).
 *
 * Why not AccessibleCaisse (which refunds still use): that rule matches
 * `caisses.etablissement_id`, i.e. where the till is FILED. A transfer is a
 * hand-over between PEOPLE, so what matters is where the recipient works —
 * « Centres affectés » (CLAUDE.md §16), never their primary centre and never
 * the filing column. Mohammed Rafik's primary centre is Rabat and his till
 * is filed in Marrakech while he is assigned to all seven: a cashier in
 * Marrakech may hand him cash there.
 *
 * This is not the flow's real control, only its first sieve: balances move
 * exclusively when the employee owning the destination till accepts the
 * transfer, super-admins included (ValiderTransfertCaisse, §11).
 */
final class CaisseDeServiceAccessible implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();
        $caisse = is_scalar($value) ? Caisse::query()->find($value) : null;

        if ($user === null || $caisse === null) {
            $fail(__('The selected till is invalid.'));

            return;
        }

        // A centre-less account (an Externe safe) has no holder and is global,
        // as on every other finance screen.
        if ($caisse->etablissement_id === null && $caisse->responsable_employee_id === null) {
            return;
        }

        $centerAccess = app(CenterAccessService::class);

        if ($centerAccess->hasGlobalAccess($user)) {
            return;
        }

        $reachable = $centerAccess->accessibleCenterIds($user);

        // withoutGlobalScopes(): Employee is #[ScopedBy(HiddenAccountScope)]
        // and a global scope applies inside a nested relation query too
        // (§11), which would shrink the set this check is meant to allow.
        $servesReachableCentre = $caisse->responsable()
            ->withoutGlobalScopes()
            ->whereHas('etablissements', fn ($e) => $e->whereIn('etablissements.id', $reachable))
            ->exists();

        if (! $servesReachableCentre) {
            $fail(__('The selected till is not accessible from your center.'));
        }
    }
}
