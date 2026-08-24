<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Etablissement;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

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
 *
 * Both creations are idempotent AND race-safe: the two PostgreSQL partial
 * unique indexes (`caisses_une_caissiere_par_employe`,
 * `caisses_methode_par_centre_unique`) make a racing double-create fail, and
 * the INSERT runs in its own savepoint (nested DB::transaction) so a caller
 * that is already inside a transaction — EncaissementController@store wraps
 * the whole submit in one — keeps a usable transaction after the violation:
 * without the savepoint PostgreSQL would abort the outer transaction and the
 * "re-read the winner" SELECT below would itself fail.
 */
final class CaisseProvisioner
{
    /**
     * Create the employee's physical till if they don't have one yet.
     * Idempotent: safe to re-run (observer double-fire, retro command, seeders).
     *
     * Checks the Caissière till specifically — not "any account they are
     * responsable of": an employee assigned an « Externe » safe must still
     * get their own till.
     */
    public function provisionFor(Employee $employee): ?Caisse
    {
        if ($employee->till()->exists()) {
            return null;
        }

        try {
            return DB::transaction(fn (): Caisse => Caisse::create([
                'nom' => $this->nameFor($employee),
                'type' => Caisse::TYPE_CAISSIERE,
                'etablissement_id' => $employee->etablissement_id,
                'responsable_employee_id' => $employee->id,
                'solde' => 0,
                'statut' => Caisse::STATUT_ACTIVE,
            ]));
        } catch (UniqueConstraintViolationException) {
            // A concurrent request provisioned it first — theirs is the till.
            return null;
        }
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
            return DB::transaction(fn (): Caisse => Caisse::create([
                'nom' => $this->compteMethodeName($etablissement, $methode),
                'type' => $methode,
                'etablissement_id' => $etablissement->id,
                'responsable_employee_id' => null,
                'solde' => 0,
                'statut' => Caisse::STATUT_ACTIVE,
            ]));
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
