<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Caisse;
use App\Models\Employee;

/**
 * Every employee owns exactly one till.
 *
 * The caisse is created automatically with the employee (EmployeeObserver) —
 * there is no manual "add a caisse" screen anywhere. It is named after the
 * employee, lives in their center and opens at 0,00 DH; the balance then moves
 * only through encaissements / depenses / remboursements / validated transfers.
 */
final class CaisseProvisioner
{
    /**
     * Create the employee's till if they don't have one yet.
     * Idempotent: safe to re-run (observer double-fire, retro command, seeders).
     */
    public function provisionFor(Employee $employee): ?Caisse
    {
        if ($employee->caisses()->exists()) {
            return null;
        }

        return Caisse::create([
            'nom' => $this->nameFor($employee),
            'etablissement_id' => $employee->etablissement_id,
            'responsable_employee_id' => $employee->id,
            'solde' => 0,
            'statut' => Caisse::STATUT_ACTIVE,
        ]);
    }

    public function nameFor(Employee $employee): string
    {
        return 'Caisse — '.$employee->nomComplet();
    }
}
