<?php

declare(strict_types=1);

namespace App\Domain\Finance\Support;

use App\Models\Caisse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * « Une caisse ne se débite jamais au-delà de ce qu'elle contient » — le
 * contrôle de solde partagé par TOUS les chemins qui font sortir l'argent
 * d'une dépense (09/09/2026).
 *
 * POURQUOI
 * --------
 * La caisse d'El Mehdi Bakhach a été approuvée à -7 250,00 DH : cinq
 * dépenses (22 500 DH) validées d'un coup sur une caisse qui n'avait reçu que
 * 15 250 DH. Rien ne comparait le montant au solde — ApprouverDepense
 * verrouillait la ligne de la dépense (contre le double clic) mais pas la
 * caisse, et débitait quoi qu'il arrive. Une caisse physique ne peut pas
 * contenir un montant négatif ; un solde négatif est toujours soit une
 * saisie en double, soit de l'argent sorti qui n'a jamais été journalisé.
 *
 * COMMENT
 * -------
 * Le contrôle s'exécute DANS la transaction de l'appelant, sur la ligne
 * `caisses` verrouillée FOR UPDATE — le même verrou que CaisseLedger prend
 * ensuite pour écrire le mouvement. Deux approbations simultanées sur la même
 * caisse sont donc sérialisées par PostgreSQL : la seconde attend que la
 * première ait commité, relit le solde DÉJÀ diminué, et est refusée si le
 * reste ne couvre plus son montant. Un contrôle fait avant `DB::transaction`
 * sur le modèle en mémoire laisserait les deux passer (CLAUDE.md §11, « every
 * read-a-balance-then-write check runs INSIDE the transaction on a
 * lockForUpdate() row »).
 *
 * La règle est `montant <= solde − réservé` : vider la caisse jusqu'à 0,00
 * est légitime (même borne que ValiderTransfertCaisse). « Réservé » = les
 * transferts « En attente » qui SORTENT de cette caisse (11/09/2026,
 * ReservationTransferts) : une demande ne bouge pas le solde, mais elle a
 * promis l'argent, et une dépense saisie avant la réception l'aurait
 * dépensé deux fois. Le message nomme les transferts qui réservent.
 *
 * La caisse contrôlée est TOUJOURS `depenses.caisse_id`, la ligne stockée —
 * jamais une caisse re-dérivée du contexte actif ou de l'approbateur. C'est
 * précisément celle que CaisseLedger débitera dans le même appel, donc le
 * contrôle et le débit ne peuvent pas viser deux comptes différents
 * (isolation par centre : une caisse est rattachée à UN centre).
 *
 * ⚠ Doit être appelé DANS `DB::transaction` — hors transaction, le verrou est
 * relâché immédiatement et la garantie disparaît.
 */
final class GardeSoldeCaisse
{
    public function __construct(
        private readonly ReservationTransferts $reservations,
    ) {}

    /**
     * Verrouille la caisse et vérifie qu'elle couvre le montant.
     *
     * @param  int     $caisseId    la caisse stockée sur la ligne à débiter
     * @param  float   $montant     montant à sortir (strictement positif)
     * @param  string  $messageKey  clé lang (placeholders :solde et :montant)
     * @param  string  $errorKey    champ porteur de l'erreur de validation
     * @return Caisse la ligne verrouillée, relue en base
     *
     * @throws ValidationException quand le solde est insuffisant
     */
    public function verrouillerEtVerifier(
        int $caisseId,
        float $montant,
        string $messageKey,
        string $errorKey = 'montant',
    ): Caisse {
        // Hors transaction, `lockForUpdate()` relâche son verrou aussitôt et
        // la garantie anti-double-approbation disparaît en silence — un bug
        // qui ne se voit qu'en production, sous deux clics simultanés. Un
        // futur appelant qui oublie la transaction échoue ici, tout de suite.
        //
        // ⚠ Ce garde-fou n'est PAS couvert par un test : `RefreshDatabase`
        // ouvre lui-même une transaction autour de chaque test, donc
        // transactionLevel() n'y vaut jamais 0. C'est un filet d'exécution,
        // pas un comportement vérifié — ne pas l'inverser en croyant qu'un
        // test rouge le rattraperait.
        if (DB::transactionLevel() === 0) {
            throw new \LogicException(
                "GardeSoldeCaisse doit être appelée à l'intérieur d'une transaction : "
                .'hors transaction le verrou FOR UPDATE ne protège rien.',
            );
        }

        /** @var Caisse $caisse */
        $caisse = Caisse::query()->whereKey($caisseId)->lockForUpdate()->firstOrFail();

        // L'argent promis par un transfert en attente n'est plus disponible :
        // lu sous le même verrou, donc une demande concurrente est sérialisée.
        $reserve = $this->reservations->enAttente($caisseId);
        $disponible = round((float) $caisse->solde - $reserve, 2);

        // Comparaison en centimes entiers : deux décimales stockées, aucun
        // arrondi flottant ne doit transformer 7 650,00 <= 7 650,00 en refus.
        $disponibleCentimes = (int) round($disponible * 100);
        $montantCentimes = (int) round($montant * 100);

        if ($montantCentimes > $disponibleCentimes) {
            $message = __($messageKey, [
                'solde' => number_format($disponible, 2, ',', ' '),
                'montant' => number_format($montant, 2, ',', ' '),
            ]);

            if ($reserve > 0) {
                $message .= ' '.self::messageReserve((float) $caisse->solde, $reserve, $this->reservations->references($caisseId));
            }

            throw ValidationException::withMessages([$errorKey => $message]);
        }

        return $caisse;
    }

    /**
     * La phrase qui explique un refus dû à une réservation — partagée avec
     * DemanderTransfertCaisse pour que les deux écrans disent la même chose.
     *
     * @param  list<string>  $references
     */
    public static function messageReserve(float $soldeTiroir, float $reserve, array $references): string
    {
        return __('The till holds :tiroir DH, of which :reserve DH are reserved by pending transfers (:refs).', [
            'tiroir' => number_format($soldeTiroir, 2, ',', ' '),
            'reserve' => number_format($reserve, 2, ',', ' '),
            'refs' => implode(', ', $references),
        ]);
    }
}
