<?php

declare(strict_types=1);

namespace App\Domain\Groups\Actions;

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
    public function handle(Group $group, int $anneeScolaireId): void
    {
        if ((int) $group->annee_scolaire_id === $anneeScolaireId) {
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
}
