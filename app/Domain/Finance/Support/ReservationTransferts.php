<?php

declare(strict_types=1);

namespace App\Domain\Finance\Support;

use App\Models\Caisse;
use App\Models\CaisseTransfer;
use Illuminate\Database\Eloquent\Builder;

/**
 * L'argent qu'un transfert « En attente » a déjà PROMIS (11/09/2026).
 *
 * Une demande de transfert ne bouge pas `caisses.solde` — seule la validation
 * par le destinataire le fait (§11). Entre les deux, le tiroir « vit » : une
 * dépense, un remboursement ou une SECONDE demande pouvaient sortir l'argent
 * déjà promis, et la validation tombait ensuite sur un solde insuffisant
 * (TRF-021 : 103 900 DH demandés, tiroir vidé entre-temps, validation
 * refusée — ou pire, solde négatif si personne ne contrôle). La règle :
 * **un transfert en attente RÉSERVE son montant** — tout ce qui fait sortir
 * de l'argent du tiroir se contrôle contre `solde − réservé`.
 *
 * Une seule source pour les trois lecteurs (GardeSoldeCaisse,
 * VentilationCentre::plafondTransfert, DemanderTransfertCaisse) : deux
 * calculs séparés finiraient par proposer ce que le serveur refuse.
 *
 * Centre d'une réservation = celui de la jambe SORTANTE, comme
 * `VentilationCentre::centreDeLaJambe()` : la colonne du transfert, repli sur
 * le centre de la caisse source.
 */
final class ReservationTransferts
{
    /**
     * Σ des transferts « En attente » qui SORTENT de cette caisse — tous
     * centres, ou seulement ceux imputés à `$centreId`.
     */
    public function enAttente(int $caisseId, ?int $centreId = null): float
    {
        return round((float) $this->query($caisseId, $centreId)->sum('montant'), 2);
    }

    /**
     * Références des transferts qui réservent — pour que le message dise
     * LEQUEL annuler, au lieu d'un refus muet.
     *
     * @return list<string>
     */
    public function references(int $caisseId): array
    {
        return $this->query($caisseId, null)->orderBy('id')->pluck('reference')->all();
    }

    /** @return Builder<CaisseTransfer> */
    private function query(int $caisseId, ?int $centreId): Builder
    {
        $query = CaisseTransfer::query()
            ->where('caisse_source_id', $caisseId)
            ->where('statut', CaisseTransfer::STATUT_EN_ATTENTE);

        if ($centreId === null) {
            return $query;
        }

        $centreDeLaCaisse = Caisse::query()->whereKey($caisseId)->value('etablissement_id');

        return $query->where(fn ($w) => $w
            ->where('etablissement_id', $centreId)
            ->when((int) $centreDeLaCaisse === $centreId, fn ($q) => $q->orWhereNull('etablissement_id')));
    }
}
