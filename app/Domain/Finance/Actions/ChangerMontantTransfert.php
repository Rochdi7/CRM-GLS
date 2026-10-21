<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Support\GardeSoldeCaisse;
use App\Domain\Finance\Support\ReservationTransferts;
use App\Domain\Finance\Support\VentilationCentre;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Services\Context\CurrentContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Change le MONTANT d'un transfert encore « En attente » (21/10/2026).
 *
 * Même famille que ChangerDestinataireTransfert : le demandeur corrige
 * lui-même une demande mal saisie, quel que soit son rôle, au lieu d'annuler
 * puis de redemander. ⚠ AUCUN argent ne bouge — une demande n'a encore
 * débité ni crédité aucune caisse (DemanderTransfertCaisse ne touche pas
 * `caisses.solde`) ; seul change ce que le destinataire devra confirmer
 * avoir reçu. C'est aussi ce qui en fait une « création », pas une édition
 * de document monétaire : le front office n'a que `cash-transfers.create`
 * (§16), et c'est ce droit que porte la route.
 *
 * Bornes, vérifiées SOUS VERROU dans la transaction (§11) :
 *   1. statut « En attente » — un transfert validé a déjà déplacé l'argent,
 *      un transfert annulé est clos ;
 *   2. seul le DEMANDEUR (ou le compte de maintenance) — le destinataire
 *      ne décide pas combien on lui envoie, un tiers non plus ;
 *   3. le nouveau montant est > 0 et différent de l'actuel ;
 *   4. **le PLAFOND de la demande est rejoué** — exactement celui de
 *      DemanderTransfertCaisse (`VentilationCentre::plafondTransfert`,
 *      part du centre + argent non revendiqué, jamais au-delà de
 *      `solde − réservé`), la demande elle-même étant ÉCARTÉE de la réserve
 *      (`$saufTransfertId`) pour être contrôlée comme une demande neuve.
 *      Sans cela, augmenter une demande engagerait de l'argent que le tiroir
 *      n'a pas, et la validation tomberait sur un solde insuffisant — le
 *      trou que la réservation du 11/09/2026 a fermé.
 *      Le centre contrôlé est celui dont l'argent SORT, lu sur la ligne
 *      (`VentilationCentre::centreDeLaJambe`) — un transfert ne change jamais
 *      de centre ; sur « Tous les centres » rien n'est ventilé et le tiroir
 *      entier reste la borne, comme à la demande.
 *
 * `solde_source_avant` / `solde_dest_avant` restent les clichés pris à la
 * demande : ils décrivent les caisses, pas le montant. Le changement est
 * écrit dans la NOTE du transfert (ajouté, jamais écrasé — même convention
 * que le changement de destinataire) en plus du journal.
 */
final class ChangerMontantTransfert
{
    public function __construct(
        private readonly CurrentContext $context,
        private readonly VentilationCentre $ventilation,
        private readonly ReservationTransferts $reservations,
    ) {}

    public function handle(
        CaisseTransfer $transfert,
        float $nouveauMontant,
        Employee $par,
        bool $mainteneur = false,
    ): CaisseTransfer {
        $nouveauMontant = round($nouveauMontant, 2);

        if ($nouveauMontant <= 0) {
            throw ValidationException::withMessages([
                'montant' => __('The amount must be greater than zero.'),
            ]);
        }

        return DB::transaction(function () use ($transfert, $nouveauMontant, $par, $mainteneur): CaisseTransfer {
            $verrouille = CaisseTransfer::query()
                ->whereKey($transfert->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($verrouille->statut !== CaisseTransfer::STATUT_EN_ATTENTE) {
                throw ValidationException::withMessages([
                    'montant' => __('Only a pending transfer can change its amount.'),
                ]);
            }

            if ((int) $verrouille->requested_by !== $par->id && ! $mainteneur) {
                throw ValidationException::withMessages([
                    'montant' => __('Only the employee who requested the transfer can change its amount.'),
                ]);
            }

            $ancien = round((float) $verrouille->montant, 2);

            if (abs($nouveauMontant - $ancien) < 0.005) {
                throw ValidationException::withMessages([
                    'montant' => __('The amount is unchanged.'),
                ]);
            }

            // Le tiroir est verrouillé AVANT de lire le plafond : une dépense
            // ou une autre demande concurrente sérialise sur cette ligne.
            $source = Caisse::query()->whereKey((int) $verrouille->caisse_source_id)->lockForUpdate()->firstOrFail();

            $centreActif = $this->context->etablissementId() === null
                ? null
                : $this->ventilation->centreDeLaJambe($verrouille, $source);

            $disponible = $this->ventilation->plafondTransfert($source, $centreActif, $verrouille->id);

            if ($nouveauMontant > $disponible) {
                $message = $centreActif !== null
                    ? __(
                        'This center only holds :solde MAD in this till - the rest belongs to other centers.',
                        ['solde' => number_format(max(0.0, $disponible), 2, ',', ' ')],
                    )
                    : __(
                        'The till only has :solde MAD available for a transfer.',
                        ['solde' => number_format(max(0.0, $disponible), 2, ',', ' ')],
                    );

                $reserve = $this->reservations->enAttente($source->id, null, $verrouille->id);

                if ($reserve > 0) {
                    $message .= ' '.GardeSoldeCaisse::messageReserve(
                        (float) $source->solde,
                        $reserve,
                        $this->reservations->references($source->id, $verrouille->id),
                    );
                }

                throw ValidationException::withMessages(['montant' => $message]);
            }

            $trace = __('Amount changed on :date by :who: :from → :to', [
                'date' => now()->format('d/m/Y'),
                'who' => $par->nomComplet(),
                'from' => number_format($ancien, 2, ',', ' ').' DH',
                'to' => number_format($nouveauMontant, 2, ',', ' ').' DH',
            ]);

            $verrouille->update([
                'montant' => number_format($nouveauMontant, 2, '.', ''),
                'note' => trim(implode(' - ', array_filter([$verrouille->note, $trace]))),
            ]);

            activity('caisse_transfer')
                ->performedOn($verrouille)
                ->event('transfert_montant_modifie')
                ->withProperties([
                    'reference' => $verrouille->reference,
                    'ancien_montant' => number_format($ancien, 2, '.', ''),
                    'nouveau_montant' => number_format($nouveauMontant, 2, '.', ''),
                    'caisse_source_id' => $source->id,
                    'etablissement_id' => $centreActif,
                    'par_mainteneur' => $mainteneur,
                ])
                ->log("Transfert {$verrouille->reference} : montant {$ancien} → {$nouveauMontant}");

            return $verrouille;
        });
    }
}
