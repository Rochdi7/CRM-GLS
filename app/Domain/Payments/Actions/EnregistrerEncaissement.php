<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Finance\Support\CaisseLedger;
use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\InscriptionFee;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records a student payment in ONE transaction (schema §10 + §11):
 *  1. creates the encaissement row,
 *  2. increments the till balance (caisses.solde is application-maintained),
 *  3. recomputes the fee's payment statut.
 */
final class EnregistrerEncaissement
{
    public function __construct(private readonly CaisseLedger $ledger) {}

    /**
     * @param array<string, mixed> $data validated StoreEncaissementRequest data.
     *        May also carry 'legacy_ref'/'legacy_source' (both in
     *        Encaissement::$fillable) — the legacy-import commit path uses
     *        this to tag imported rows without a second write.
     */
    public function handle(array $data, Employee $agent): Encaissement
    {
        return DB::transaction(function () use ($data, $agent): Encaissement {
            // Last line of defense for EVERY caller (form, prompt, import):
            // money never lands on a hidden fee line (audit R-01).
            if (! empty($data['inscription_fee_id'])) {
                $fee = InscriptionFee::query()->whereKey((int) $data['inscription_fee_id'])->lockForUpdate()->first();

                if ($fee !== null && $fee->estMasque()) {
                    throw ValidationException::withMessages([
                        'payment_lines' => __('This fee is no longer active.'),
                    ]);
                }
            }

            $encaissement = Encaissement::create([
                ...$data,
                // Dedupe scope for legacy refs (unique per centre). The
                // importer passes its batch centre; a normal payment takes
                // the student's.
                'etablissement_id' => $data['etablissement_id']
                    ?? Student::query()->whereKey($data['student_id'])->value('etablissement_id'),
                'reference' => ReferenceGenerator::make('ENC', 'encaissements'),
                'agent_id' => $agent->id,
            ]);

            // Through the ledger, never increment(): a raw SQL bump fires no
            // model events and would leave the balance change unaudited.
            $this->ledger->credit(
                (int) $data['caisse_id'],
                (float) $data['montant'],
                "Encaissement {$encaissement->reference}",
                $encaissement,
                [
                    'methode' => $encaissement->methode,
                    'etudiant_id' => $encaissement->student_id,
                    'agent' => $agent->nomComplet(),
                    // Centre dimension of the movement (01/09/2026): the
                    // payment's own centre, stamped at write time so a
                    // multi-centre cashier's single till can be broken down
                    // per centre from the ledger alone. Historical entries
                    // lack the key — read-time fallback, never a backfill.
                    'etablissement_id' => $encaissement->etablissement_id,
                ],
            );

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
