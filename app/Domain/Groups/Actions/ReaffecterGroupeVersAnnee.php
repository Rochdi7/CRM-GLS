<?php

declare(strict_types=1);

namespace App\Domain\Groups\Actions;

use App\Models\AnneeScolaire;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Seance;
use Illuminate\Support\Facades\DB;

/**
 * Import-flow repair for the "half in one year, half in another" split
 * (24/08/2026): moves ONE group — and everything that inherits its year,
 * its inscriptions and its séances — to the import's selected année, in a
 * single transaction. Called by the import group-mapping step when the
 * operator maps a file's group onto an existing group from ANOTHER year:
 * the mapping is an explicit "this is the same group" statement, so the
 * group follows the selected year instead of leaving the old rows behind
 * as skipped duplicates in the wrong year.
 *
 * Encaissements never carry a year (they follow fee.inscription) and no
 * montant/caisse is ever touched. Saves go model-by-model — NOT a mass
 * update — so Auditable journals every change.
 */
final class ReaffecterGroupeVersAnnee
{
    public function handle(Group $group, int $anneeScolaireId, bool $force = false): void
    {
        if ((int) $group->annee_scolaire_id === $anneeScolaireId) {
            return;
        }

        // ⚠ A group still holding an ACTIVE inscription is a cohort that has
        // NOT finished (B1/B2 mid-course), so it belongs to the most recent
        // année — pulling it back into a closed year hides live students.
        //
        // The old CRM exports one running group across three files (Active /
        // Annulé / Archive) imported into different années, and this action
        // fires on every mapping, so the LAST file imported used to win:
        // loading Annulé after Active dragged a live cohort backwards
        // (6 Marrakech groups, 287 inscriptions, 26/08/2026).
        //
        // Callers that legitimately repair a mis-imported year pass
        // $force = true. The import group-mapping step does NOT: there the
        // année comes from whichever file happens to be uploaded last, which
        // is not a statement about where the cohort belongs.
        if (! $force && $this->holdsActiveInscription($group) && $this->isEarlier($anneeScolaireId, (int) $group->annee_scolaire_id)) {
            return;
        }

        DB::transaction(function () use ($group, $anneeScolaireId): void {
            $group->annee_scolaire_id = $anneeScolaireId;
            $group->save();

            foreach (Inscription::query()->where('group_id', $group->id)->cursor() as $inscription) {
                $inscription->annee_scolaire_id = $anneeScolaireId;
                $inscription->save();
            }

            foreach (Seance::query()->where('group_id', $group->id)->cursor() as $seance) {
                $seance->annee_scolaire_id = $anneeScolaireId;
                $seance->save();
            }
        });
    }

    private function holdsActiveInscription(Group $group): bool
    {
        return Inscription::query()
            ->where('group_id', $group->id)
            ->where('statut', Inscription::STATUT_ACTIVE)
            ->exists();
    }

    /**
     * Compares années by date_debut, never by id — ids are insertion order
     * and say nothing about which school year comes first.
     */
    private function isEarlier(int $candidateId, int $referenceId): bool
    {
        $dates = AnneeScolaire::query()
            ->whereIn('id', [$candidateId, $referenceId])
            ->pluck('date_debut', 'id');

        $candidate = $dates[$candidateId] ?? null;
        $reference = $dates[$referenceId] ?? null;

        return $candidate !== null && $reference !== null && $candidate < $reference;
    }
}
