<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Actions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Domain\Finance\Support\CaisseLedger;
use Illuminate\Support\Facades\DB;

/**
 * Records an expense in ONE transaction: creates the depense row and
 * decrements the till balance (caisses.solde is application-maintained,
 * schema §10).
 */
final class EnregistrerDepense
{
    public function __construct(private readonly CaisseLedger $ledger) {}

    /**
     * @param array<string, mixed> $data validated StoreDepenseRequest data
     */
    public function handle(array $data, Employee $agent): Depense
    {
        return DB::transaction(function () use ($data, $agent): Depense {
            $depense = Depense::create([
                ...$data,
                'reference' => ReferenceGenerator::make('DEP', 'depenses'),
                'agent_id' => $agent->id,
            ]);

            $this->ledger->debit(
                (int) $data['caisse_id'],
                (float) $data['montant'],
                "Dépense {$depense->reference}",
                $depense,
                [
                    'type_depense_id' => $depense->type_depense_id,
                    'methode' => $depense->methode_paiement,
                ],
            );

            return $depense;
        });
    }
}
