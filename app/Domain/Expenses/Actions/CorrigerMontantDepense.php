<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Actions;

use App\Domain\Finance\Support\CaisseLedger;
use App\Domain\Finance\Support\GardeSoldeCaisse;
use App\Models\Depense;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Corrige le MONTANT d'une dépense — super-admin uniquement
 * (`expenses.update-amount`, superAdminOnly(), 02/10/2026).
 *
 * Le montant n'est pas un libellé : pour une dépense APPROUVÉE il est déjà
 * sorti de la caisse. Le corriger est donc un MOUVEMENT D'ARGENT, jamais une
 * simple écriture de colonne (même principe que RequalifierMethodeEncaissement) :
 *
 *  - « En attente » : rien n'a été débité, seul le montant change ;
 *    l'approbation débitera le nouveau montant.
 *  - « Approuvée » : la DIFFÉRENCE est passée par CaisseLedger sur la caisse
 *    STOCKÉE (`depenses.caisse_id`, jamais re-dérivée) — une hausse est un
 *    débit contrôlé par GardeSoldeCaisse (part du centre, comme une
 *    approbation), une baisse est un crédit. Le journal montre le débit
 *    d'origine puis l'écriture de correction avec solde avant/après.
 *  - « Refusée » / « Annulée » : refusé (déjà bloqué par la policy update).
 *
 * Tout se fait dans UNE transaction, ligne de dépense relue sous verrou.
 */
final class CorrigerMontantDepense
{
    public const MESSAGE_SOLDE_INSUFFISANT = 'Cannot raise this expense: the till only holds :solde DH, the additional amount is :montant DH.';

    public function __construct(
        private readonly CaisseLedger $ledger,
        private readonly GardeSoldeCaisse $garde,
    ) {}

    public function handle(Depense $depense, float $nouveauMontant, ?Employee $corrigePar = null): Depense
    {
        $nouveauMontant = round($nouveauMontant, 2);

        if ($nouveauMontant <= 0) {
            throw ValidationException::withMessages([
                'montant' => __('The amount must be greater than zero.'),
            ]);
        }

        return DB::transaction(function () use ($depense, $nouveauMontant, $corrigePar): Depense {
            /** @var Depense $locked */
            $locked = Depense::query()->whereKey($depense->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isRefusee() || $locked->isAnnulee()) {
                throw ValidationException::withMessages([
                    'montant' => __('The amount of a refused or cancelled expense cannot be changed.'),
                ]);
            }

            $ancien = round((float) $locked->montant, 2);
            $delta = round($nouveauMontant - $ancien, 2);

            if (abs($delta) < 0.005) {
                return $locked;
            }

            if ($locked->isApprouvee()) {
                $centreId = $locked->centreId()
                    ?? Employee::query()->whereKey($locked->agent_id)->value('etablissement_id');
                $centreId = $centreId === null ? null : (int) $centreId;

                $motif = "Correction du montant de la dépense {$locked->reference} : "
                    .number_format($ancien, 2, ',', ' ').' → '
                    .number_format($nouveauMontant, 2, ',', ' ').' DH';

                $extra = [
                    'type_depense_id' => $locked->type_depense_id,
                    'methode' => $locked->methode_paiement,
                    'etablissement_id' => $centreId,
                    'correction' => 'CORRECTION-'.$locked->reference.'-MONTANT',
                    'montant_avant' => $ancien,
                    'montant_apres' => $nouveauMontant,
                    'corrige_par' => $corrigePar?->nomComplet(),
                ];

                if ($delta > 0) {
                    $this->garde->verrouillerEtVerifier(
                        (int) $locked->caisse_id,
                        $delta,
                        self::MESSAGE_SOLDE_INSUFFISANT,
                        'montant',
                        $centreId,
                    );
                    $this->ledger->debit((int) $locked->caisse_id, $delta, $motif, $locked, $extra);
                } else {
                    $this->ledger->credit((int) $locked->caisse_id, -$delta, $motif, $locked, $extra);
                }
            }

            $locked->update(['montant' => $nouveauMontant]);

            return $locked;
        });
    }
}
