<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Actions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Group;
use App\Domain\Finance\Support\CaisseLedger;
use App\Domain\Finance\Support\GardeSoldeCaisse;
use App\Services\Context\CurrentContext;
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
 *
 * The approval-OFF branch is an approval path like any other — the row is
 * born « Approuvée » and the till is debited on the spot — so it carries the
 * same overspend guard as ApprouverDepense (GardeSoldeCaisse, 09/09/2026):
 * the till is locked and must cover the amount, or nothing is written.
 */
final class EnregistrerDepense
{
    public const MESSAGE_SOLDE_INSUFFISANT = 'Cannot record this expense: the till only holds :solde DH, the requested amount is :montant DH.';

    public function __construct(
        private readonly CaisseLedger $ledger,
        private readonly CurrentContext $context,
        private readonly GardeSoldeCaisse $garde,
    ) {}

    /**
     * @param array<string, mixed> $data validated StoreDepenseRequest data
     */
    public function handle(array $data, Employee $agent): Depense
    {
        $requiresApproval = AppSettings::expenseApprovalEnabled();

        return DB::transaction(function () use ($data, $agent, $requiresApproval): Depense {
            if (! $requiresApproval) {
                // Checked BEFORE the row exists: a refused expense must leave
                // no trace at all (no DEP- reference burnt, no Approuvée row
                // that never debited anything).
                $this->garde->verrouillerEtVerifier(
                    (int) $data['caisse_id'],
                    (float) $data['montant'],
                    self::MESSAGE_SOLDE_INSUFFISANT,
                    'montant',
                );
            }

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
                        // Centre dimension (01/09/2026): a « Paiement prof »
                        // belongs to its GROUP's centre; an ordinary dépense
                        // to the centre the cashier is working in (active
                        // context), falling back to their primary centre.
                        'etablissement_id' => ($depense->group_id !== null
                                ? Group::query()->whereKey($depense->group_id)->value('etablissement_id')
                                : null)
                            ?? $this->context->etablissementId()
                            ?? $agent->etablissement_id,
                    ],
                );
            }

            return $depense;
        });
    }
}
