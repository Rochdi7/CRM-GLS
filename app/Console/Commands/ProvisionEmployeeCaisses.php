<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\CaisseProvisioner;
use Illuminate\Console\Command;

/**
 * Backfill: create the missing till of every employee who predates the
 * "one caisse per employee" rule. Idempotent — employees who already own a
 * caisse are skipped, so it is safe to re-run.
 */
final class ProvisionEmployeeCaisses extends Command
{
    protected $signature = 'caisses:provision';

    protected $description = 'Create the missing cash register of every employee that does not have one yet';

    public function handle(CaisseProvisioner $provisioner): int
    {
        $created = 0;
        $skipped = 0;

        Employee::query()->doesntHave('caisses')->chunkById(100, function ($employees) use ($provisioner, &$created, &$skipped): void {
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

        return self::SUCCESS;
    }
}
