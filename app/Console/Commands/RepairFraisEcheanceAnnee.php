<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Settings\Support\FraisEcheanceResolver;
use App\Models\Group;
use App\Models\InscriptionFee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off data repair (27/08/2026): monthly fee due dates stamped with the
 * WRONG calendar year.
 *
 * `FraisEcheanceResolver::defaultFor()` used to take the year from
 * `now()`, so every month of a group landed in the same calendar year. A
 * school year straddles two: a group starting in September 2025 owes
 * Septembre–Décembre in 2025 and Janvier–Août in 2026. With one year for
 * all twelve, « Frais de Septembre » sorted nine months AFTER « Frais de
 * Janvier », and every screen that orders fees by due date — the group fee
 * table, the inscription form, « Statistique de groupe » — opened the
 * school year on Janvier instead of on the group's real first month.
 *
 * The resolver now anchors the year on the group's `date_debut_formation`.
 * This command re-derives the stored dates the same way, in both places
 * they were copied to:
 *
 *   group_frais.date_echeance      — the group's own fee assignment
 *   inscription_fees.date_echeance — the copy taken at enrollment
 *
 * Only rows whose fee NAME carries a month are touched (an inscription or
 * exam fee has no derivable date and was always typed by hand), and only
 * when the stored day-and-month already match what the resolver derives —
 * i.e. the value is the untouched default and only its YEAR is wrong. A due
 * date somebody edited by hand is left alone. Idempotent: a second run
 * finds nothing.
 *
 * Dry-run by default, like every other repair command here; pass --apply to
 * write.
 */
final class RepairFraisEcheanceAnnee extends Command
{
    protected $signature = 'frais:repair-echeance-annee
        {--apply : Write the corrected due dates (otherwise dry-run)}';

    protected $description = "Re-derive monthly fee due dates whose year was taken from today instead of the group's school year";

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $groups = Group::query()->with(['frais', 'anneeScolaire'])->get();

        $planGroupe = [];
        $planInscription = [];

        foreach ($groups as $group) {
            $debut = $group->date_debut_formation?->toDateString();
            $debutAnnee = $group->anneeScolaire?->date_debut?->toDateString();

            if ($debut === null && $debutAnnee === null) {
                // Nothing to anchor the school year on — the resolver would
                // fall back to today's year, which is what we are repairing.
                continue;
            }

            foreach ($group->frais as $frais) {
                $stocke = $frais->pivot->date_echeance;
                $attendu = FraisEcheanceResolver::defaultFor($frais->nom, $debut, $debutAnnee);

                if ($stocke === null || $attendu === null) {
                    continue;
                }

                $stocke = substr((string) $stocke, 0, 10);

                if ($stocke === $attendu) {
                    continue;
                }

                // Same month AND same day, different year = the old default
                // carrying the wrong year. A due date somebody moved to
                // another day or another month was set by hand — leave it.
                if (substr($stocke, 4) !== substr($attendu, 4)) {
                    continue;
                }

                $planGroupe[] = [
                    'group' => $group,
                    'frais' => $frais,
                    'de' => $stocke,
                    'vers' => $attendu,
                ];

                $planInscription[] = [
                    'group_id' => $group->id,
                    'frais_id' => $frais->id,
                    'nom' => $frais->nom,
                    'de' => $stocke,
                    'vers' => $attendu,
                ];
            }
        }

        if ($planGroupe === []) {
            $this->info('Aucune échéance à corriger.');

            return self::SUCCESS;
        }

        foreach ($planGroupe as $ligne) {
            $this->line(sprintf(
                '  %s / %s : %s → %s',
                $ligne['group']->nom,
                $ligne['frais']->nom,
                $ligne['de'],
                $ligne['vers'],
            ));
        }

        $lignesInscription = $this->countInscriptionFees($planInscription);

        $this->newLine();
        $this->info(sprintf(
            '%d échéance(s) de groupe et %d ligne(s) d\'inscription à corriger.',
            count($planGroupe),
            $lignesInscription,
        ));

        if (! $apply) {
            $this->warn('Dry-run — relancer avec --apply pour écrire.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($planGroupe, $planInscription): void {
            foreach ($planGroupe as $ligne) {
                DB::table('group_frais')
                    ->where('group_id', $ligne['group']->id)
                    ->where('frais_id', $ligne['frais']->id)
                    ->update(['date_echeance' => $ligne['vers']]);
            }

            foreach ($planInscription as $ligne) {
                $this->inscriptionFeeQuery($ligne)->update(['date_echeance' => $ligne['vers']]);
            }
        });

        $this->info('Échéances corrigées.');

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>  $plan
     */
    private function countInscriptionFees(array $plan): int
    {
        $total = 0;

        foreach ($plan as $ligne) {
            $total += $this->inscriptionFeeQuery($ligne)->count();
        }

        return $total;
    }

    /**
     * The enrollment copies of one group fee still holding the wrong year.
     *
     * Matched on frais_id when the line has one and on the fee NAME when it
     * does not (hand-added lines predate frais_id being filled), always
     * scoped to the group's own inscriptions and to the exact wrong date, so
     * a line whose échéance was edited by hand is never rewritten.
     *
     * @param  array<string, mixed>  $ligne
     */
    private function inscriptionFeeQuery(array $ligne): \Illuminate\Database\Eloquent\Builder
    {
        return InscriptionFee::query()
            ->whereHas('inscription', fn ($q) => $q->where('group_id', $ligne['group_id']))
            ->whereDate('date_echeance', $ligne['de'])
            ->where(fn ($q) => $q
                ->where('frais_id', $ligne['frais_id'])
                ->orWhere(fn ($q2) => $q2->whereNull('frais_id')->where('nom', $ligne['nom'])));
    }
}
