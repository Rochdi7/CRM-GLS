<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Remboursement;
use App\Models\Student;
use App\Domain\Finance\Support\CaisseLedger;
use App\Domain\Finance\Support\GardeSoldeCaisse;
use App\Services\Context\CurrentContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Records a student refund in ONE transaction: creates the remboursement
 * row and decrements the till balance (schema §10 + §14).
 */
final class EnregistrerRemboursement
{
    /**
     * ⚠ Un remboursement ne sort jamais plus que ce que la caisse contient
     * (10/09/2026). Une dépense ne le pouvait déjà plus (GardeSoldeCaisse,
     * 09/09/2026) — mais un remboursement débite EXACTEMENT la même caisse
     * physique, sans aucun contrôle : rendre 5 000 DH depuis un tiroir qui en
     * contenait 300 laissait la caisse à -4 700,00 DH. Une caisse physique ne
     * peut pas contenir un montant négatif ; le solde négatif est toujours
     * soit une saisie en double, soit de l'argent sorti hors journal.
     * Deux écrans du même argent avec deux règles opposées : le trou se
     * déplace simplement vers celui qui ne contrôle rien.
     */
    public const MESSAGE_SOLDE_INSUFFISANT = 'Cannot record this refund: the till only holds :solde DH, the requested amount is :montant DH.';

    public function __construct(
        private readonly CaisseLedger $ledger,
        private readonly CurrentContext $context,
        private readonly GardeSoldeCaisse $garde,
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
                // ⚠ A refund with NO `encaissement_id` is still capped by no
                // PAYMENT — that half of the documented decision stands
                // (docs/rapports/finance/phase-10-finance-audit.md §2.6 Q1):
                // an outflow unrelated to any tracked payment has no amount
                // to cap against. What no longer holds is the other half —
                // « the till may go negative » (10/09/2026): the balance
                // guard below bounds EVERY refund by what the till actually
                // holds, linked or not. Only the per-payment cap is what this
                // block decides.
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

            // The centre the refund BELONGS to, resolved ONCE and stored on
            // the row so the list can filter on it directly instead of
            // inferring it from the till (03/09/2026 bug: a centre-4 student
            // refunded from a centre-1 till was invisible on both centres,
            // and was refunded twice as a result). Identical precedence to
            // the ledger stamp below — they must never disagree.
            $etablissementId = $encaissement?->etablissement_id
                ?? Student::query()->whereKey((int) $data['beneficiaire_id'])->value('etablissement_id')
                ?? $this->context->etablissementId()
                ?? $agent->etablissement_id;

            // Le solde est contrôlé DANS la transaction, sur la ligne
            // `caisses` verrouillée FOR UPDATE — le même verrou que
            // CaisseLedger prend juste après pour écrire le mouvement. Deux
            // remboursements simultanés sur la même caisse sont donc
            // sérialisés : le second relit le solde déjà diminué (CLAUDE.md
            // §11). La caisse contrôlée est celle qui sera DÉBITÉE, jamais
            // une caisse re-dérivée. Borne `montant <= solde` : vider un
            // tiroir jusqu'à 0,00 reste légitime.
            //
            // ⚠ Le contrôle ne vaut que pour une caisse PHYSIQUE (Caissière /
            // Externe). Un compte de MÉTHODE (TPE / Chèque / Virement) n'est
            // pas un tiroir : c'est le compte du centre pour cette méthode, et
            // le seul remboursement qui l'atteint est la contrepassation d'un
            // chèque REJETÉ (CaisseResolver::forRemboursement). Cet argent
            // n'a jamais existé — la banque l'a refusé — donc le compte peut
            // légitimement ne rien contenir au moment où on l'annule. Y
            // appliquer la borne du tiroir bloquerait le remède même du
            // chèque en bois, ce qui est l'inverse du but.
            $caisseADebiter = Caisse::query()->whereKey((int) $data['caisse_id'])->firstOrFail();

            if ($caisseADebiter->isEspeces()) {
                $this->garde->verrouillerEtVerifier(
                    (int) $data['caisse_id'],
                    (float) $data['montant'],
                    self::MESSAGE_SOLDE_INSUFFISANT,
                );
            }

            $remboursement = Remboursement::create([
                ...$data,
                'etablissement_id' => $etablissementId,
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
                    'etablissement_id' => $etablissementId,
                ],
            );

            return $remboursement;
        });
    }
}
