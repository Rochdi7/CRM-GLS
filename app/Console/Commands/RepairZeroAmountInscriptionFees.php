<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\InscriptionFee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off data repair: inscriptions created before the Inscriptions edit
 * form's camelCase→snake_case fee-line transform was added saved every fee
 * line with montant_initial=0 (and no frais_id) — the payment form
 * correctly hides a fee with nothing owed, so these registrations appeared
 * to have no billable fees at all.
 *
 * Recovers the correct amount by matching each affected fee's `nom` against
 * its inscription's GROUP catalog assignment (group_frais.montant) — every
 * name matches exactly, no discount was ever recorded on these rows
 * (remise_pct/remise_montant are both null), so montant = montant_initial.
 * frais_id is backfilled from the same match. Idempotent — only touches
 * rows where montant_initial is still 0.
 */
final class RepairZeroAmountInscriptionFees extends Command
{
    protected $signature = 'inscriptions:repair-zero-amount-fees {--dry-run : List what would change without writing}';

    protected $description = 'Repair inscription_fees rows left at montant_initial=0 by the pre-fix Inscriptions edit form';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $fees = InscriptionFee::query()
            ->where('montant_initial', 0)
            ->with('inscription.group.frais')
            ->get();

        if ($fees->isEmpty()) {
            $this->info('No zero-amount fee lines found.');

            return self::SUCCESS;
        }

        $repaired = 0;
        $skipped = 0;

        DB::transaction(function () use ($fees, $dryRun, &$repaired, &$skipped): void {
            foreach ($fees as $fee) {
                $group = $fee->inscription?->group;
                $catalogFee = $group?->frais->firstWhere('nom', $fee->nom);
                $montant = $catalogFee?->pivot->montant;

                if ($group === null || $catalogFee === null || $montant === null) {
                    $skipped++;
                    $this->warn("  skip fee #{$fee->id} ({$fee->nom}) — no matching group catalog fee found");

                    continue;
                }

                $this->line("  fee #{$fee->id} ({$fee->nom}): 0.00 → {$montant}");

                if (! $dryRun) {
                    $fee->update([
                        'frais_id' => $catalogFee->id,
                        'montant_initial' => $montant,
                        'montant' => $montant,
                    ]);
                }

                $repaired++;
            }
        });

        $this->info(($dryRun ? '[dry-run] ' : '')."Repaired: {$repaired} (skipped: {$skipped}).");

        return self::SUCCESS;
    }
}
