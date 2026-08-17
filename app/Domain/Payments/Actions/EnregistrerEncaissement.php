<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\InscriptionFee;
use Illuminate\Support\Facades\DB;

/**
 * Records a student payment in ONE transaction (schema §10 + §11):
 *  1. creates the encaissement row,
 *  2. increments the till balance (caisses.solde is application-maintained),
 *  3. recomputes the fee's payment statut.
 */
final class EnregistrerEncaissement
{
    /**
     * @param array<string, mixed> $data validated StoreEncaissementRequest data.
     *        May also carry 'legacy_ref'/'legacy_source' (both in
     *        Encaissement::$fillable) — the legacy-import commit path uses
     *        this to tag imported rows without a second write.
     */
    public function handle(array $data, Employee $agent): Encaissement
    {
        return DB::transaction(function () use ($data, $agent): Encaissement {
            $encaissement = Encaissement::create([
                ...$data,
                'reference' => ReferenceGenerator::make('ENC', 'encaissements'),
                'agent_id' => $agent->id,
            ]);

            Caisse::query()->whereKey($data['caisse_id'])->increment('solde', (float) $data['montant']);

            // An avance (no fee attached — see Encaissement::isAvance()) has
            // nothing to recompute here.
            if ($encaissement->fee !== null) {
                $this->recalculerStatutFee($encaissement->fee);
            }

            return $encaissement;
        });
    }

    private function recalculerStatutFee(InscriptionFee $fee): void
    {
        $paye = $fee->montantPaye();

        $fee->update([
            'statut' => match (true) {
                $paye >= (float) $fee->montant => InscriptionFee::STATUT_PAYE,
                $paye > 0 => InscriptionFee::STATUT_PAYE_PARTIELLEMENT,
                default => InscriptionFee::STATUT_NON_PAYE,
            },
        ]);
    }
}
