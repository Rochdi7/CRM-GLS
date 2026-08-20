<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Remboursement;
use App\Domain\Finance\Support\CaisseLedger;
use Illuminate\Support\Facades\DB;

/**
 * Records a student refund in ONE transaction: creates the remboursement
 * row and decrements the till balance (schema §10 + §14).
 */
final class EnregistrerRemboursement
{
    public function __construct(private readonly CaisseLedger $ledger) {}

    /**
     * @param array<string, mixed> $data validated StoreRemboursementRequest data
     */
    public function handle(array $data, Employee $agent): Remboursement
    {
        return DB::transaction(function () use ($data, $agent): Remboursement {
            $remboursement = Remboursement::create([
                ...$data,
                'reference' => ReferenceGenerator::make('RMB', 'remboursements'),
                'agent_id' => $agent->id,
            ]);

            $this->ledger->debit(
                (int) $data['caisse_id'],
                (float) $data['montant'],
                "Remboursement {$remboursement->reference}",
                $remboursement,
                ['beneficiaire_id' => $remboursement->beneficiaire_id],
            );

            return $remboursement;
        });
    }
}
