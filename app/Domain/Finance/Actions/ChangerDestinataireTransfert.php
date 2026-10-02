<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Change le DESTINATAIRE d'un transfert encore « En attente » (02/10/2026).
 *
 * Cas réel : TRF-193, 65 393,30 DH envoyés par Mustapha Ben Lmekki à Amine
 * Rafik au lieu de Mohamed Rafik. Avant, la seule issue était d'annuler puis
 * de redemander — ou une commande en production. Le demandeur corrige
 * désormais lui-même son erreur, quel que soit son rôle.
 *
 * ⚠ AUCUN argent ne bouge, et c'est ce qui rend le geste sûr : une demande
 * n'a encore débité ni crédité aucune caisse (DemanderTransfertCaisse ne
 * touche pas `caisses.solde`). La réservation est portée par la caisse
 * SOURCE (ReservationTransferts), qui ne change pas, et le montant non plus
 * — le plafond déjà vérifié à la demande reste donc vrai. Seul change celui
 * qui pourra ACCEPTER la réception : le nouveau destinataire, puisque
 * ValiderTransfertCaisse relit la caisse de destination sur la ligne.
 *
 * Bornes, vérifiées SOUS VERROU dans la transaction (§11 : un contrôle lu
 * avant `DB::transaction` laisse passer une validation concurrente) :
 *   1. statut « En attente » — un transfert validé a déjà déplacé l'argent,
 *      un transfert annulé est clos ;
 *   2. seul le DEMANDEUR (ou le compte de maintenance) — le destinataire
 *      actuel ne peut pas se faire remplacer, un tiers non plus ;
 *   3. la nouvelle caisse est une caisse ESPÈCES (même règle que
 *      DemanderTransfertCaisse), différente de la source et de l'actuelle.
 * L'accessibilité de la caisse (centre de service) est contrôlée par la Form
 * Request avec la MÊME règle que la création (CaisseDeServiceAccessible).
 *
 * Le changement est écrit dans la NOTE du transfert (ajouté, jamais écrasé —
 * même convention que l'annulation par le mainteneur) en plus du journal :
 * c'est la colonne que lisent la liste et la fiche.
 */
final class ChangerDestinataireTransfert
{
    public function handle(
        CaisseTransfer $transfert,
        int $nouvelleCaisseId,
        Employee $par,
        bool $mainteneur = false,
    ): CaisseTransfer {
        return DB::transaction(function () use ($transfert, $nouvelleCaisseId, $par, $mainteneur): CaisseTransfer {
            $verrouille = CaisseTransfer::query()
                ->whereKey($transfert->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($verrouille->statut !== CaisseTransfer::STATUT_EN_ATTENTE) {
                throw ValidationException::withMessages([
                    'caisse_destination_id' => __('Only a pending transfer can change its recipient.'),
                ]);
            }

            if ((int) $verrouille->requested_by !== $par->id && ! $mainteneur) {
                throw ValidationException::withMessages([
                    'caisse_destination_id' => __('Only the employee who requested the transfer can change its recipient.'),
                ]);
            }

            $nouvelle = Caisse::query()->find($nouvelleCaisseId);

            if ($nouvelle === null || ! $nouvelle->isEspeces()) {
                throw ValidationException::withMessages([
                    'caisse_destination_id' => __('A till transfer can only move cash between cash accounts.'),
                ]);
            }

            if ($nouvelle->id === (int) $verrouille->caisse_source_id) {
                throw ValidationException::withMessages([
                    'caisse_destination_id' => __('The destination till must be different from your own till.'),
                ]);
            }

            if ($nouvelle->id === (int) $verrouille->caisse_destination_id) {
                throw ValidationException::withMessages([
                    'caisse_destination_id' => __('This till is already the recipient of the transfer.'),
                ]);
            }

            $ancienne = Caisse::query()->find($verrouille->caisse_destination_id);
            $ancienNom = $ancienne?->responsable?->nomComplet() ?? $ancienne?->nom ?? '-';
            $nouveauNom = $nouvelle->responsable?->nomComplet() ?? $nouvelle->nom;

            $trace = __('Recipient changed on :date by :who: :from → :to', [
                'date' => now()->format('d/m/Y'),
                'who' => $par->nomComplet(),
                'from' => $ancienNom,
                'to' => $nouveauNom,
            ]);

            $verrouille->update([
                'caisse_destination_id' => $nouvelle->id,
                // Le cliché « solde avant » décrit la caisse qui recevra :
                // il suit le destinataire, comme à la création.
                'solde_dest_avant' => $nouvelle->solde,
                'note' => trim(implode(' - ', array_filter([$verrouille->note, $trace]))),
            ]);

            activity('caisse_transfer')
                ->performedOn($verrouille)
                ->event('transfert_destinataire_modifie')
                ->withProperties([
                    'reference' => $verrouille->reference,
                    'montant' => number_format((float) $verrouille->montant, 2, '.', ''),
                    'ancienne_caisse_id' => $ancienne?->id,
                    'ancien_destinataire' => $ancienNom,
                    'nouvelle_caisse_id' => $nouvelle->id,
                    'nouveau_destinataire' => $nouveauNom,
                    'par_mainteneur' => $mainteneur,
                ])
                ->log("Transfert {$verrouille->reference} : destinataire {$ancienNom} → {$nouveauNom}");

            return $verrouille;
        });
    }
}
