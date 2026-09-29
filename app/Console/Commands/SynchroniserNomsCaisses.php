<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Employees\Actions\SynchroniserNomEmploye;
use App\Models\Employee;
use App\Services\CaisseProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Catch-up for SynchroniserNomEmploye: employees renamed BEFORE the observer
 * re-copied the name still have a till (`caisses.nom`) and/or a login
 * (`users.name`) carrying the old one. The employee's prénom + nom is the
 * source; both copies are realigned on it.
 *
 * Touches NAMES only — no encaissement, dépense, remboursement, transfer or
 * `caisses.solde`. Each rename goes through save(), so Auditable journals it.
 *
 * ⚠ A person who renamed themselves in Profil before the fix changed ONLY
 * `users.name`: the employee still carries the old name, and --apply would
 * copy that old name back onto the login. Read the dry-run: for such a row,
 * fix the prénom/nom on the Employees screen (or in Profil) instead — the
 * observer then syncs both copies by itself.
 *
 * DRY-RUN BY DEFAULT — pass --apply to execute. Idempotent.
 */
final class SynchroniserNomsCaisses extends Command
{
    protected $signature = 'caisses:synchroniser-noms {--apply : Execute (default is a dry-run that changes nothing)}';

    protected $description = 'Realign caisse and login names on the employee\'s prénom + nom (dry-run by default)';

    public function handle(SynchroniserNomEmploye $sync, CaisseProvisioner $provisioner): int
    {
        $rows = [];
        $drift = [];

        // withoutGlobalScopes(): the hidden maintainer account has a till too.
        Employee::query()->withoutGlobalScopes()->with(['till', 'user'])->orderBy('id')
            ->each(function (Employee $employee) use ($provisioner, &$rows, &$drift): void {
                $nom = $provisioner->nameFor($employee);
                $caisse = $employee->till;
                $user = $employee->user;

                $caisseDiffers = $caisse !== null && $caisse->nom !== $nom;
                $userDiffers = $user !== null && $user->name !== $nom;

                if ($nom === '' || (! $caisseDiffers && ! $userDiffers)) {
                    return;
                }

                $drift[] = $employee;
                $rows[] = [
                    $employee->reference,
                    $nom,
                    $caisseDiffers ? "{$caisse->nom} → {$nom}" : '=',
                    $userDiffers ? "{$user->name} → {$nom}" : '=',
                ];
            });

        if ($rows === []) {
            $this->info('Tous les noms de caisse et de compte sont synchronisés.');

            return self::SUCCESS;
        }

        $this->table(['Employé', 'Nom (source)', 'Caisse', 'Compte (users.name)'], $rows);

        if (! $this->option('apply')) {
            $this->warn(count($rows).' employé(s) à synchroniser. Simulation : rien n\'a été modifié (relancer avec --apply).');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($drift, $sync): void {
            foreach ($drift as $employee) {
                $sync->handle($employee);
            }
        });

        $this->info(count($drift).' employé(s) synchronisé(s).');

        return self::SUCCESS;
    }
}
