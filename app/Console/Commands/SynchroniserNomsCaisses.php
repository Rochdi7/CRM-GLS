<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Employees\Actions\SynchroniserNomEmploye;
use App\Models\Caisse;
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
 * Also normalises the em dash still stored in account names created before
 * 25/09/2026 (« TPE — GLS Marrakech ») to the plain hyphen
 * CaisseProvisioner::compteMethodeName() writes today. Only the dash is
 * replaced — a name an admin typed keeps its wording.
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

        $tirets = Caisse::query()->where('nom', 'like', '%—%')->orderBy('id')->get();

        if ($rows === [] && $tirets->isEmpty()) {
            $this->info('Tous les noms de caisse et de compte sont synchronisés.');

            return self::SUCCESS;
        }

        if ($rows !== []) {
            $this->table(['Employé', 'Nom (source)', 'Caisse', 'Compte (users.name)'], $rows);
        }

        if ($tirets->isNotEmpty()) {
            $this->table(['Caisse', 'Nom'], $tirets->map(fn (Caisse $c): array => [
                $c->id,
                $c->nom.' → '.self::sansTiretLong($c->nom),
            ])->all());
        }

        if (! $this->option('apply')) {
            $this->warn(count($rows).' employé(s) et '.$tirets->count().' nom(s) de caisse à corriger. Simulation : rien n\'a été modifié (relancer avec --apply).');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($drift, $sync, $tirets): void {
            foreach ($drift as $employee) {
                $sync->handle($employee);
            }

            // save(), not a mass update(): Auditable journals each rename.
            foreach ($tirets as $caisse) {
                $caisse->nom = self::sansTiretLong($caisse->nom);
                $caisse->save();
            }
        });

        $this->info(count($drift).' employé(s) synchronisé(s), '.$tirets->count().' nom(s) de caisse corrigé(s).');

        return self::SUCCESS;
    }

    private static function sansTiretLong(string $nom): string
    {
        return str_replace('—', '-', $nom);
    }
}
