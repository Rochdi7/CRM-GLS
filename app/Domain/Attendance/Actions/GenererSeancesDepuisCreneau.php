<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Models\Creneau;
use App\Models\Seance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generates/syncs the dated "Prévue" séances a créneau (weekly recurring
 * slot) stands for — from today (or the group's own start date, whichever
 * is later) through the end of the group's academic year. Only séances
 * still "Prévue" AND still linked to this créneau are touched on
 * update/delete: once attendance has been taken (Effectuée) or a séance was
 * detached/edited by hand, it is left alone (gls-crm CLAUDE.md-style
 * invariant — generation must never silently overwrite real activity).
 */
final class GenererSeancesDepuisCreneau
{
    public function generer(Creneau $creneau): void
    {
        $creneau->loadMissing('group');
        $group = $creneau->group;

        $anneeFin = $group->anneeScolaire?->date_fin;

        if ($anneeFin === null) {
            return;
        }

        // A créneau closed by a teacher changeover never generates again —
        // the incoming teacher gets their OWN emploi du temps instead
        // (Domain\Groups\Actions\ChangerEnseignantGroupe).
        if ($creneau->date_fin !== null) {
            return;
        }

        $debut = Carbon::today();
        if ($group->date_debut_formation !== null && $group->date_debut_formation->gt($debut)) {
            $debut = $group->date_debut_formation->copy();
        }

        // A créneau that only starts on a later date (typically the day a new
        // teacher took over) must not back-fill séances before that date.
        if ($creneau->date_debut !== null && $creneau->date_debut->gt($debut)) {
            $debut = $creneau->date_debut->copy();
        }

        $fin = Carbon::parse($anneeFin);

        if ($debut->gt($fin)) {
            return;
        }

        DB::transaction(function () use ($creneau, $group, $debut, $fin): void {
            // Existing future "Prévue" séances from THIS créneau are the
            // sync target — anything the user turned "Effectuée"/"Annulée"
            // or detached is left untouched, matching by date so a re-save
            // doesn't create duplicates for dates already generated.
            $existingDates = $creneau->seances()
                ->where('statut', Seance::STATUT_PREVUE)
                ->where('date_seance', '>=', $debut->toDateString())
                ->pluck('date_seance')
                ->map(fn ($date) => $date->toDateString())
                ->all();

            for ($date = $debut->copy(); $date->lte($fin); $date->addDay()) {
                if ($date->isoWeekday() !== $creneau->jour_semaine) {
                    continue;
                }

                if (in_array($date->toDateString(), $existingDates, true)) {
                    continue;
                }

                Seance::create([
                    'group_id' => $group->id,
                    'creneau_id' => $creneau->id,
                    'date_seance' => $date->toDateString(),
                    'heure_debut' => $creneau->heure_debut,
                    'heure_fin' => $creneau->heure_fin,
                    'enseignant_id' => $creneau->enseignant_id,
                    'etablissement_id' => $group->etablissement_id,
                    'annee_scolaire_id' => $group->annee_scolaire_id,
                    'statut' => Seance::STATUT_PREVUE,
                ]);
            }
        });
    }

    /**
     * Applies an edit (time/teacher/room) to future untouched séances —
     * same "Prévue + still linked to this créneau + not yet past" guard as
     * generer(). Called after the créneau's own fields are saved.
     */
    public function resynchroniser(Creneau $creneau): void
    {
        if ($creneau->date_fin !== null) {
            return;
        }

        $creneau->seances()
            ->where('statut', Seance::STATUT_PREVUE)
            ->where('date_seance', '>=', Carbon::today()->toDateString())
            ->update([
                'heure_debut' => $creneau->heure_debut,
                'heure_fin' => $creneau->heure_fin,
                'enseignant_id' => $creneau->enseignant_id,
            ]);

        $this->generer($creneau);
    }

    /**
     * Removes future untouched séances when their créneau is deleted — past
     * séances and anything no longer "Prévue" (attendance taken, cancelled,
     * manually edited off-schedule) survive; their creneau_id merely goes
     * null via the FK's nullOnDelete.
     */
    public function supprimerFuturs(Creneau $creneau): void
    {
        $creneau->seances()
            ->where('statut', Seance::STATUT_PREVUE)
            ->where('date_seance', '>=', Carbon::today()->toDateString())
            ->delete();
    }
}
