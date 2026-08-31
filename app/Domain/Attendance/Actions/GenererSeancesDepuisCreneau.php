<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Actions;

use App\Models\Creneau;
use App\Models\Group;
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
 *
 * ⚠ Séances are created DAY BY DAY, never a whole period at once
 * (31/08/2026). A single `generer()` run writes at most the CURRENT day's
 * séance; the 08:00 scheduled job (`seances:generate`, bootstrap/app.php)
 * then walks the calendar one morning at a time — each day it creates that
 * day's séances for every active group. The bounds come from the GROUP:
 * nothing is created before its `date_debut_formation` (the job simply
 * produces nothing until that day arrives) and nothing after its
 * `date_fin_formation` (falling back to the académic year's `date_fin`) —
 * once that end date is behind, the job stops producing anything for the
 * group until the date is extended. Generation is idempotent — a day whose
 * séance already exists for this créneau (whatever its statut: flipped
 * Effectuée or Annulée counts as existing) is skipped — which is what makes
 * re-running it any number of times in a day safe.
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

    /**
     * True when the last generer() call produced nothing because the group
     * declares NO date_debut_formation: with no start date there is nothing
     * to anchor "start creating from the group's date début" on, so rather
     * than silently generating from today (séances possibly before the real
     * start of the class), generation refuses until the date is filled in.
     */
    public bool $bloqueParDateDebutManquante = false;

    public function generer(Creneau $creneau): void
    {
        $this->bloqueParFinFormation = false;
        $this->bloqueParDateDebutManquante = false;

        $creneau->loadMissing('group');
        $group = $creneau->group;

        // A finished or cancelled group generates NOTHING, whatever path
        // called us (the 08:00 job already filters on statut, but a créneau
        // saved/edited on an archived group must not mint séances either).
        if (in_array($group->statut, Group::STATUTS_HISTORIQUE, true)) {
            return;
        }

        // No declared start date = no generation (see the flag's doc above).
        if ($group->date_debut_formation === null) {
            $this->bloqueParDateDebutManquante = true;

            return;
        }

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
        if ($group->date_debut_formation->gt($debut)) {
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

        // A créneau closed mid-period (teacher changeover) stops there too,
        // and a group whose date_fin_formation is already past generates
        // nothing until that date is extended.
        if ($debut->gt($fin)) {
            $this->bloqueParFinFormation = $group->date_fin_formation !== null
                && $group->date_fin_formation->lt(Carbon::today());

            return;
        }

        // Séances previously generated beyond the group's (possibly shortened)
        // end of formation are removed — same "Prévue + still linked + future"
        // guard as everywhere else, so attendance already taken survives.
        // Bounded by the group's PERIOD, independent of what this run creates.
        $this->purgerHorsPeriode($creneau, $fin);

        // Day-by-day: this run creates the CURRENT day's séance and nothing
        // else. When the group (or this créneau) only starts later, today is
        // before $debut and nothing is created — the 08:00 job produces the
        // first séance on the morning date_debut_formation is reached, then
        // one day at a time through date_fin_formation (class doc above).
        $aujourdhui = Carbon::today();

        if ($aujourdhui->lt($debut) || $aujourdhui->isoWeekday() !== $creneau->jour_semaine) {
            return;
        }

        DB::transaction(function () use ($creneau, $group, $aujourdhui): void {
            // Whatever its statut: a séance already flipped Effectuée or
            // Annulée today, or still Prévue, means the day exists — re-runs
            // (créneau edit after attendance, the job firing twice) must
            // neither duplicate nor resurrect it.
            $dejaGeneree = $creneau->seances()
                ->whereDate('date_seance', $aujourdhui->toDateString())
                ->exists();

            if ($dejaGeneree) {
                return;
            }

            Seance::create([
                'group_id' => $group->id,
                'creneau_id' => $creneau->id,
                'date_seance' => $aujourdhui->toDateString(),
                'heure_debut' => $creneau->heure_debut,
                'heure_fin' => $creneau->heure_fin,
                'enseignant_id' => $creneau->enseignant_id,
                'etablissement_id' => $group->etablissement_id,
                'annee_scolaire_id' => $group->annee_scolaire_id,
                'statut' => Seance::STATUT_PREVUE,
            ]);
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
            // A séance still "Prévue" can already carry présences
            // (EnregistrerPresences never flips the statut) — deleting it
            // would cascade-delete the roll call. Leave it alone.
            ->whereDoesntHave('presences')
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
            // Same présences guard as purgerHorsPeriode() — a roll call
            // already taken must never be cascade-deleted.
            ->whereDoesntHave('presences')
            ->where('date_seance', '>=', Carbon::today()->toDateString())
            ->delete();
    }
}
