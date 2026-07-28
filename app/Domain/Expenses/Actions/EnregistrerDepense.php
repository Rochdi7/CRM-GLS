<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Actions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

/**
 * Records an expense in ONE transaction: creates the depense row and
 * decrements the till balance (caisses.solde is application-maintained,
 * schema §10).
 */
final class EnregistrerDepense
{
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

            Caisse::query()->whereKey($data['caisse_id'])->decrement('solde', (float) $data['montant']);

            return $depense;
        });
    }
}
