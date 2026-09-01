<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Remboursement;
use App\Domain\Finance\Support\CaisseLedger;
use App\Services\Context\CurrentContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records a student refund in ONE transaction: creates the remboursement
 * row and decrements the till balance (schema §10 + §14).
 */
final class EnregistrerRemboursement
{
    public function __construct(
        private readonly CaisseLedger $ledger,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @param array<string, mixed> $data validated StoreRemboursementRequest data
     */
    public function handle(array $data, Employee $agent): Remboursement
    {
        return DB::transaction(function () use ($data, $agent): Remboursement {
            $encaissement = null;

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

                // A refund LINKED to an ordinary fee payment can never give
                // back more than that payment brought in, cumulatively:
                // without this, the same 1 000 DH row could be refunded
                // 5 000 DH, twice over, and the till would go out by money it
                // never received. Computed here, inside the transaction on
                // the row already locked above, so two concurrent refunds
                // serialize instead of both seeing the same remaining.
                //
                // ⚠ A refund with NO `encaissement_id` stays deliberately
                // uncapped — that is the documented decision
                // (docs/phase-10-finance-audit.md §2.6 Q1, asserted by
                // test_no_maximum_refund_amount_check_exists): an outflow
                // unrelated to any tracked payment has no amount to cap
                // against. Only the linked case is constrained.
                if (! $encaissement->isAvance()) {
                    $dejaRembourse = (float) $encaissement->remboursements()->sum('montant');
                    $restant = round(max(0.0, (float) $encaissement->montant - $dejaRembourse), 2);

                    if (round((float) $data['montant'], 2) > $restant) {
                        throw ValidationException::withMessages([
                            'montant' => __("The amount cannot exceed the payment's refundable balance."),
                        ]);
                    }
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
                [
                    'beneficiaire_id' => $remboursement->beneficiaire_id,
                    // Centre dimension (01/09/2026): a refund REVERSES a
                    // financial context, so a linked refund carries the
                    // ORIGINAL payment's centre — never the student's
                    // current one (they may have moved centre since). An
                    // unlinked refund has no original context: the active
                    // context centre, falling back to the agent's primary.
                    'etablissement_id' => $encaissement?->etablissement_id
                        ?? $this->context->etablissementId()
                        ?? $agent->etablissement_id,
                ],
            );

            return $remboursement;
        });
    }
}
