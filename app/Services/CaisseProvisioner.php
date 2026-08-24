<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Etablissement;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Every employee owns exactly one PHYSICAL till, and every centre owns
 * exactly one account per non-cash payment method (TPE / Chèque / Virement).
 *
 * The employee till is created automatically with the employee
 * (EmployeeObserver) — there is no manual "add a caisse" screen anywhere. It
 * is named after the employee, lives in their PRIMARY centre and opens at
 * 0,00 DH. The method accounts are created with the centre
 * (EtablissementObserver) and self-healed by CaisseResolver the first time a
 * payment needs one. Balances then move only through CaisseLedger.
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
            'type' => Caisse::TYPE_CAISSIERE,
            'etablissement_id' => $employee->etablissement_id,
            'responsable_employee_id' => $employee->id,
            'solde' => 0,
            'statut' => Caisse::STATUT_ACTIVE,
        ]);
    }

    public function nameFor(Employee $employee): string
    {
        return $employee->nomComplet();
    }

    /**
     * The centre's account for a non-cash method, created on first use.
     *
     * Idempotent under concurrency: the partial unique index on
     * (etablissement_id, type) makes a racing double-create fail, in which
     * case the winner's row is simply re-read.
     */
    public function compteMethodeFor(int $etablissementId, string $methode): Caisse
    {
        if (! in_array($methode, Caisse::TYPES_METHODE, true)) {
            throw new \InvalidArgumentException("« {$methode} » n'est pas une méthode de paiement à compte dédié.");
        }

        $existing = Caisse::query()
            ->where('etablissement_id', $etablissementId)
            ->where('type', $methode)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $etablissement = Etablissement::query()->findOrFail($etablissementId);

        try {
            return Caisse::create([
                'nom' => $this->compteMethodeName($etablissement, $methode),
                'type' => $methode,
                'etablissement_id' => $etablissement->id,
                'responsable_employee_id' => null,
                'solde' => 0,
                'statut' => Caisse::STATUT_ACTIVE,
            ]);
        } catch (UniqueConstraintViolationException) {
            return Caisse::query()
                ->where('etablissement_id', $etablissementId)
                ->where('type', $methode)
                ->firstOrFail();
        }
    }

    /**
     * Provision the three method accounts of a centre (observer + retro).
     *
     * @return list<Caisse> the accounts (existing or created)
     */
    public function provisionComptesMethodeFor(Etablissement $etablissement): array
    {
        return array_map(
            fn (string $methode): Caisse => $this->compteMethodeFor($etablissement->id, $methode),
            Caisse::TYPES_METHODE,
        );
    }

    public function compteMethodeName(Etablissement $etablissement, string $methode): string
    {
        return "{$methode} — {$etablissement->nom_centre}";
    }
}
