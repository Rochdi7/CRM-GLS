<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Remboursement;
use App\Domain\Finance\Support\CaisseLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            if (! empty($data['encaissement_id'])) {
                // Locked: the same row's remaining balance is what
                // AppliquerAvance spends, so a refund and an application
                // racing each other must serialize on it.
                $encaissement = Encaissement::query()
                    ->whereKey((int) $data['encaissement_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($encaissement->student_id !== (int) $data['beneficiaire_id']) {
                    throw ValidationException::withMessages([
                        'encaissement_id' => __('This payment does not belong to the selected student.'),
                    ]);
                }

                // An avance can only be refunded up to what is still
                // unallocated — the applied part has already settled fees
                // (and the refund itself then counts as "used", so the
                // same money cannot be applied afterwards either).
                if ($encaissement->isAvance() && round((float) $data['montant'], 2) > $encaissement->montantRestant()) {
                    throw ValidationException::withMessages([
                        'montant' => __("The amount cannot exceed the advance's remaining balance."),
                    ]);
                }
            }

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
