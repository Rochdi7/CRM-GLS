<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Services\CaisseProvisioner;
use Illuminate\Console\Command;

/**
 * Backfill: create the missing till of every employee who predates the
 * "one caisse per employee" rule, and the missing method accounts
 * (TPE / Chèque / Virement) of every centre that predates the observer
 * (28/08/2026: Casablanca, Kénitra, Agadir, Online had none in production).
 * Idempotent — existing rows are skipped, so it is safe to re-run.
 */
final class ProvisionEmployeeCaisses extends Command
{
    protected $signature = 'caisses:provision';

    protected $description = 'Create the missing cash register of every employee AND the missing TPE/Chèque/Virement account of every centre';

    public function handle(CaisseProvisioner $provisioner): int
    {
        $created = 0;
        $skipped = 0;

        // withoutGlobalScopes() : c'est une commande de réparation, elle doit
        // voir TOUTE la table — le compte technique (HiddenAccountScope) est
        // masqué de l'interface, pas de la maintenance. Le sauter laisserait
        // un employé sans caisse et ferait échouer toute action l'impliquant.
        Employee::query()->withoutGlobalScopes()->doesntHave('caisses')->chunkById(100, function ($employees) use ($provisioner, &$created, &$skipped): void {
            foreach ($employees as $employee) {
                if ($provisioner->provisionFor($employee) !== null) {
                    $created++;
                    $this->line("  + {$provisioner->nameFor($employee)}");
                } else {
                    $skipped++;
                }
            }
        });

        $this->info("Cash registers created: {$created} (skipped: {$skipped}).");

        $comptes = 0;
        foreach (Etablissement::query()->orderBy('id')->get() as $etablissement) {
            $before = $etablissement->caisses()->count();
            $provisioner->provisionComptesMethodeFor($etablissement);
            $added = $etablissement->caisses()->count() - $before;
            $comptes += $added;
            if ($added > 0) {
                $this->line("  + {$etablissement->nom_centre}: {$added} compte(s) de méthode");
            }
        }

        $this->info("Method accounts created: {$comptes}.");

        return self::SUCCESS;
    }
}
