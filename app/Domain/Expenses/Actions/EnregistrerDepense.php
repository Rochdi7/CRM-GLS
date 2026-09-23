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
            // Centre dimension: a « Paiement prof » belongs to its GROUP's
            // centre; an ordinary dépense to the centre the agent is WORKING
            // IN (active context), falling back to their primary centre only
            // on « Tous les centres ».
            //
            // ⚠ Stored on the ROW (18/09/2026), not only stamped on the
            // ledger: the agent's single till is attached to their PRIMARY
            // centre, so resolving the centre through the till filed an
            // expense keyed in GLS Online under that primary centre — and a
            // pending dépense has no ledger entry at all to read it from.
            // Never client input: the payload comes from validated() and the
            // Form Requests do not know this key.
            $etablissementId = ($data['group_id'] ?? null) !== null
                ? Group::query()->whereKey($data['group_id'])->value('etablissement_id')
                : null;
            $etablissementId ??= $this->context->etablissementId() ?? $agent->etablissement_id;

            // L'enseignant PAYÉ (23/09/2026) : celui que le calcul a désigné,
            // sinon le prof ACTUEL du groupe pour une saisie à la main. Figé
            // À LA CRÉATION — un changement de prof ultérieur sur le groupe
            // ne doit jamais réattribuer un paiement déjà versé.
            $enseignantId = $data['enseignant_id'] ?? null;
            if ($enseignantId === null && ($data['group_id'] ?? null) !== null) {
                $enseignantId = Group::query()->whereKey($data['group_id'])->value('enseignant_id');
            }

            if (! $requiresApproval) {
                // Checked BEFORE the row exists: a refused expense must leave
                // no trace at all (no DEP- reference burnt, no Approuvée row
                // that never debited anything).
                $this->garde->verrouillerEtVerifier(
                    (int) $data['caisse_id'],
                    (float) $data['montant'],
                    self::MESSAGE_SOLDE_INSUFFISANT,
                    'montant',
                    // The CENTRE's share of the till, not the whole drawer —
                    // same bound as ApprouverDepense (GardeSoldeCaisse).
                    $etablissementId === null ? null : (int) $etablissementId,
                );
            }

            $depense = Depense::create([
                ...$data,
                'etablissement_id' => $etablissementId,
                'enseignant_id' => $enseignantId,
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
                        // Same centre as the row itself — one value, computed
                        // once above, so the ledger and the list cannot disagree.
                        'etablissement_id' => $etablissementId,
                    ],
                );
            }

            return $depense;
        });
    }
}
