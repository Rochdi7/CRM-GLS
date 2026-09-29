<?php

declare(strict_types=1);

namespace App\Domain\Employees\Actions;

use App\Models\Caisse;
use App\Models\Employee;
use App\Services\CaisseProvisioner;

/**
 * An employee's name lives in ONE place — `employees.prenom`/`nom` — and two
 * rows carry a COPY of it written at creation time: the login
 * (`users.name`, EmployeeCredentialService) and the physical till
 * (`caisses.nom`, CaisseProvisioner). Renaming the employee without
 * re-copying left the till named after the old name on « Caisse globale »,
 * « Comptes de caisse », the transfer dropdowns and the journal, while the
 * Agent column of every payment (read live through `agent_id`) already
 * showed the new one — two names for the same person on the same screen.
 *
 * Only the NAME moves: no encaissement, dépense, remboursement or transfer
 * is touched (they point at the employee/caisse by id and read the name at
 * render time), `caisses.solde` is neither read nor written. Each copy goes
 * through save() so Auditable journals the rename. Only the « Caissière »
 * till is renamed — an « Externe » safe the employee is merely responsable
 * of carries its own name. Past audit entries keep the name written then
 * (the journal is append-only, §11).
 */
final class SynchroniserNomEmploye
{
    public function __construct(private readonly CaisseProvisioner $provisioner) {}

    public function handle(Employee $employee): void
    {
        $nom = $this->provisioner->nameFor($employee);

        if ($nom === '') {
            return;
        }

        $till = $employee->till()->first();

        if ($till instanceof Caisse && $till->nom !== $nom) {
            $till->nom = mb_substr($nom, 0, 100);
            $till->save();
        }

        $user = $employee->user()->first();

        if ($user !== null && $user->name !== $nom) {
            $user->name = $nom;
            $user->save();
        }
    }
}
