<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Actions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Depense;
use App\Models\Employee;
use App\Domain\Finance\Support\CaisseLedger;
use App\Support\Settings\AppSettings;
use Illuminate\Support\Facades\DB;

/**
 * Records an expense in ONE transaction.
 *
 * Two modes, switched by Paramètres → Système « Validation des dépenses »
 * (AppSettings::EXPENSE_APPROVAL, ON by default):
 *
 *  - approval ON  → the expense is created "En attente" and the till is NOT
 *    touched. The money is on hold until a super-admin approves it
 *    (ApprouverDepense debits then) or refuses it (nothing ever moves).
 *  - approval OFF → legacy behavior: created "Approuvée" and the till is
 *    debited immediately, in the same transaction.
 *
 * Either way caisses.solde only ever moves through CaisseLedger
 * (CLAUDE.md §11), so every movement stays in the audit journal.
 */
final class EnregistrerDepense
{
    public function __construct(private readonly CaisseLedger $ledger) {}

    /**
     * @param array<string, mixed> $data validated StoreDepenseRequest data
     */
    public function handle(array $data, Employee $agent): Depense
    {
        $requiresApproval = AppSettings::expenseApprovalEnabled();

        return DB::transaction(function () use ($data, $agent, $requiresApproval): Depense {
            $depense = Depense::create([
                ...$data,
                'reference' => ReferenceGenerator::make('DEP', 'depenses'),
                'agent_id' => $agent->id,
                'statut' => $requiresApproval
                    ? Depense::STATUT_EN_ATTENTE
                    : Depense::STATUT_APPROUVEE,
                // Auto-approved expenses carry no approver: nobody decided,
                // the switch was simply off (keeps "approved_by" meaning
                // "a human approved this", never a synthetic value).
                'approved_at' => $requiresApproval ? null : now(),
            ]);

            if (! $requiresApproval) {
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
            }

            return $depense;
        });
    }
}
