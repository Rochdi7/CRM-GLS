<?php

declare(strict_types=1);

namespace App\Support\Access;

use App\Models\Caisse;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;

/**
 * An EMPTY personal till whose owner has no business cashing in — a teacher
 * (Enseignant never handles the school's money) or an employee who is no
 * longer Actif.
 *
 * Why: every employee gets a « Caissière » till provisioned automatically
 * (CaisseProvisioner, EmployeeObserver), so the finance screens fill up with
 * dozens of permanently-0.00 DH rows belonging to people who will never use
 * one, burying the handful of tills that actually move money.
 *
 * ⚠ The zero-balance condition is NOT cosmetic and may never be dropped.
 * Hiding a till that still holds money would erase that money from the
 * on-screen totals while `caisses.solde` still carries it — a discrepancy
 * between what the screen says and what the ledger holds is exactly the
 * class of bug the Finance invariants exist to prevent (CLAUDE.md §11). A
 * dormant till with a balance therefore stays listed until it is emptied
 * through a transfer.
 *
 * Like HiddenAccount, this is a DISPLAY filter only: the rows still exist,
 * still hold their solde, still journal every movement, and are still
 * reachable by reference (Employee::till(), CaisseResolver::tillOf()) — so
 * an inactive employee's till keeps working the moment they are reactivated.
 *
 * Single source of truth for both finance screens that list tills:
 * GetComptesCaisse (« Comptes de caisse ») and GetCaisseGlobale
 * (« Caisse globale »).
 */
final class DormantTill
{
    /**
     * Exclude dormant tills from a Caisse query.
     *
     * Kept as SQL (not a post-fetch reject) so a paginated screen counts and
     * pages the same rows it displays.
     *
     * @param  Builder<Caisse>  $query
     * @return Builder<Caisse>
     */
    public static function hide(Builder $query): Builder
    {
        return $query->whereNot(fn (Builder $q) => $q
            ->where('type', Caisse::TYPE_CAISSIERE)
            ->where('solde', 0)
            ->whereHas('responsable', fn ($r) => $r
                ->where(fn ($w) => $w
                    ->where('categorie', Employee::CATEGORIE_ENSEIGNANT)
                    ->orWhere('statut', '!=', Employee::STATUT_ACTIF))));
    }

    /** Whether this single loaded account is a dormant till. */
    public static function is(Caisse $caisse): bool
    {
        if ($caisse->type !== Caisse::TYPE_CAISSIERE || (float) $caisse->solde !== 0.0) {
            return false;
        }

        $responsable = $caisse->responsable;

        if ($responsable === null) {
            return false;
        }

        return $responsable->categorie === Employee::CATEGORIE_ENSEIGNANT
            || $responsable->statut !== Employee::STATUT_ACTIF;
    }
}
