<?php

declare(strict_types=1);

namespace App\Domain\Groups\Actions;

use App\Models\Employee;
use App\Models\Group;
use App\Models\GroupEnseignant;
use App\Models\Seance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Changes (or clears) the teacher currently running a group — the ONLY
 * correct way to touch groups.enseignant_id.
 *
 * Everything below happens in ONE transaction:
 *
 *  1. the current Actif assignment is closed (date_fin = changeover date,
 *     statut = Archivé) — never deleted, it is the payroll trail;
 *  2. a new Actif assignment opens for the incoming teacher (date_debut =
 *     changeover date, date_fin NULL);
 *  3. groups.enseignant_id is refreshed to mirror the new active row, so
 *     every existing query (séances/créneaux filters, groups list…) keeps
 *     working untouched;
 *  4. the group's emploi du temps STOPS: each créneau is closed with
 *     date_fin = the changeover date and its future "Prévue" séances are
 *     deleted.
 *
 * Step 4 is deliberate and is the point of the whole flow: the incoming
 * teacher gets a NEW emploi du temps rather than inheriting the previous
 * one, so each teacher's séances stay cleanly separated and per-teacher
 * payroll can read a real date_debut/date_fin. Past séances and anything
 * already Effectuée/Annulée are never touched — they record who actually
 * taught.
 */
final class ChangerEnseignantGroupe
{
    /**
     * @return array{assignment: ?GroupEnseignant, creneauxFermes: int, seancesSupprimees: int, changed: bool}
     */
    public function __invoke(
        Group $group,
        ?int $enseignantId,
        ?string $dateDebut = null,
        ?string $motif = null,
        ?Employee $par = null,
    ): array {
        $courant = $this->assignmentActif($group);

        // Same teacher already active (and no change at all requested) —
        // nothing to archive, and above all no emploi du temps to wipe.
        if ($courant?->enseignant_id === $enseignantId && $group->enseignant_id === $enseignantId) {
            return ['assignment' => $courant, 'creneauxFermes' => 0, 'seancesSupprimees' => 0, 'changed' => false];
        }

        $date = $dateDebut !== null && $dateDebut !== ''
            ? Carbon::parse($dateDebut)
            : Carbon::today();

        return DB::transaction(function () use ($group, $enseignantId, $date, $motif, $par, $courant): array {
            if ($courant !== null) {
                $courant->update([
                    // An assignment can never end before it began (a
                    // back-dated changeover would otherwise produce a
                    // negative period and break payroll totals).
                    'date_fin' => $date->lt($courant->date_debut) ? $courant->date_debut : $date,
                    'statut' => GroupEnseignant::STATUT_ARCHIVE,
                ]);
            }

            $nouvelle = null;
            if ($enseignantId !== null) {
                $nouvelle = GroupEnseignant::create([
                    'group_id' => $group->id,
                    'enseignant_id' => $enseignantId,
                    'date_debut' => $date->toDateString(),
                    'statut' => GroupEnseignant::STATUT_ACTIF,
                    'motif' => $motif,
                    'created_by' => $par?->id,
                ]);
            }

            $group->update(['enseignant_id' => $enseignantId]);

            [$creneauxFermes, $seancesSupprimees] = $this->arreterEmploiDuTemps($group, $date);

            return [
                'assignment' => $nouvelle,
                'creneauxFermes' => $creneauxFermes,
                'seancesSupprimees' => $seancesSupprimees,
                'changed' => true,
            ];
        });
    }

    /**
     * Opens the very first assignment of a freshly created group — no
     * previous row to archive, no emploi du temps to stop yet.
     */
    public function ouvrirInitiale(Group $group, ?int $enseignantId, ?Employee $par = null): ?GroupEnseignant
    {
        if ($enseignantId === null) {
            return null;
        }

        return GroupEnseignant::create([
            'group_id' => $group->id,
            'enseignant_id' => $enseignantId,
            'date_debut' => ($group->date_debut_formation ?? Carbon::today())->toDateString(),
            'statut' => GroupEnseignant::STATUT_ACTIF,
            'created_by' => $par?->id,
        ]);
    }

    public function assignmentActif(Group $group): ?GroupEnseignant
    {
        return GroupEnseignant::query()
            ->where('group_id', $group->id)
            ->where('statut', GroupEnseignant::STATUT_ACTIF)
            ->first();
    }

    /**
     * Closes every still-running créneau of the group and clears the future
     * "Prévue" séances they had generated. Returns [créneaux closed,
     * séances deleted] so the caller can tell the user exactly what stopped.
     *
     * @return array{0: int, 1: int}
     */
    private function arreterEmploiDuTemps(Group $group, Carbon $date): array
    {
        $creneauIds = $group->creneaux()->whereNull('date_fin')->pluck('id');

        if ($creneauIds->isEmpty()) {
            return [0, 0];
        }

        // Future untouched séances only — a séance already Effectuée or
        // Annulée, or dated before the changeover, records real activity of
        // the OUTGOING teacher and must survive for their payroll.
        $seancesSupprimees = Seance::query()
            ->whereIn('creneau_id', $creneauIds)
            ->where('statut', Seance::STATUT_PREVUE)
            ->whereDate('date_seance', '>=', $date->toDateString())
            ->delete();

        $creneauxFermes = $group->creneaux()
            ->whereNull('date_fin')
            ->update(['date_fin' => $date->toDateString()]);

        return [$creneauxFermes, $seancesSupprimees];
    }
}
