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
 * is later) through the group's date_fin_formation, or the end of its
 * academic year when the group declares no end date. A group whose
 * date_fin_formation is already past generates nothing at all: its
 * date_fin_formation must be extended first. Only séances
 * still "Prévue" AND still linked to this créneau are touched on
 * update/delete: once attendance has been taken (Effectuée) or a séance was
 * detached/edited by hand, it is left alone (gls-crm CLAUDE.md-style
 * invariant — generation must never silently overwrite real activity).
 */
final class GenererSeancesDepuisCreneau
{
    /**
     * True when the last generer() call produced nothing because the group's
     * date_fin_formation is already past — the caller surfaces this so the
     * user is told to extend the group's end date rather than being left
     * wondering why an emploi du temps stayed empty.
     */
    public bool $bloqueParFinFormation = false;

    public function generer(Creneau $creneau): void
    {
        $this->bloqueParFinFormation = false;

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

        // The group's own end of formation caps generation: a group that
        // finished in March must not keep producing séances until the end of
        // the academic year. If date_fin_formation has already passed, this
        // leaves $fin behind $debut and NOTHING is generated — extending the
        // group's date_fin_formation is what re-opens generation, which is
        // deliberate: the schedule follows the group's declared period rather
        // than silently outliving it.
        if ($group->date_fin_formation !== null && $group->date_fin_formation->lt($fin)) {
            $fin = $group->date_fin_formation->copy();
        }

        // A créneau closed mid-period (teacher changeover) stops there too.
        if ($debut->gt($fin)) {
            $this->bloqueParFinFormation = $group->date_fin_formation !== null
                && $group->date_fin_formation->lt(Carbon::today());

            return;
        }

        // Séances previously generated beyond the group's (possibly shortened)
        // end of formation are removed — same "Prévue + still linked + future"
        // guard as everywhere else, so attendance already taken survives.
        $this->purgerHorsPeriode($creneau, $fin);

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
     * Drops untouched future séances that fall after the group's end of
     * formation — e.g. after an admin shortens date_fin_formation.
     */
    private function purgerHorsPeriode(Creneau $creneau, Carbon $fin): void
    {
        $creneau->seances()
            ->where('statut', Seance::STATUT_PREVUE)
            ->where('date_seance', '>=', Carbon::today()->toDateString())
            ->where('date_seance', '>', $fin->toDateString())
            ->delete();
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
