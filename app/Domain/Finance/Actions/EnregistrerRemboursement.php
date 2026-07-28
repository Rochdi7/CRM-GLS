<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Remboursement;
use Illuminate\Support\Facades\DB;

/**
 * Records a student refund in ONE transaction: creates the remboursement
 * row and decrements the till balance (schema §10 + §14).
 */
final class EnregistrerRemboursement
{
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

            Caisse::query()->whereKey($data['caisse_id'])->decrement('solde', (float) $data['montant']);

            return $remboursement;
        });
    }
}
