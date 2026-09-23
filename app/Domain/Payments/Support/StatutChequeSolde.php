<?php

declare(strict_types=1);

namespace App\Domain\Payments\Support;

use App\Models\Cheque;

/**
 * Un chèque « À déposer » entièrement consommé passe « Encaissé »
 * (23/09/2026, demande métier) : quand son reste tombe à 0,00 DH, tout son
 * montant a financé des paiements, il n'y a plus rien à suivre sur la ligne.
 *
 * Seule définition de la règle, appelée par les deux écritures qui peuvent
 * faire tomber le reste à zéro (EncaissementController@store,
 * ChequeController@update) et par le rattrapage
 * `cheques:marquer-encaisses`. Trois bornes :
 *  - « Garantie (À encaisser) » n'est JAMAIS concerné : c'est une caution ;
 *  - un chèque « Rejeté » ne remonte JAMAIS en « Encaissé » (chèque refusé
 *    pour signature : c'est précisément le cas qu'on doit continuer à voir) ;
 *  - aucun argent ne bouge — seul `statut` change, comme au bouton
 *    « Marquer encaissé ». Un rejet ultérieur de la banque reste possible
 *    (Encaissé → Rejeté, ChequeController@updateStatut).
 *
 * L'appelant passe une ligne verrouillée FOR UPDATE dans sa transaction.
 */
final class StatutChequeSolde
{
    public static function doitPasserEncaisse(Cheque $cheque): bool
    {
        return $cheque->type === Cheque::TYPE_A_DEPOSER
            && in_array($cheque->statut, [Cheque::STATUT_EN_POSSESSION, Cheque::STATUT_DEPOSE], true)
            && $cheque->montantUtilise() > 0
            && $cheque->montantRestant() <= 0.0;
    }

    /** @return bool true si le statut a changé */
    public static function synchroniser(Cheque $cheque): bool
    {
        if (! self::doitPasserEncaisse($cheque)) {
            return false;
        }

        $ancien = $cheque->statut;
        $cheque->statut = Cheque::STATUT_ENCAISSE;
        $cheque->save();

        activity('cheque')
            ->performedOn($cheque)
            ->event('cheque_statut')
            ->withProperties([
                'reference' => $cheque->reference,
                'statut_avant' => $ancien,
                'statut_apres' => Cheque::STATUT_ENCAISSE,
                'montant' => number_format((float) $cheque->montant, 2, '.', ''),
                'numero_cheque' => $cheque->numero_cheque,
                'banque' => $cheque->banque,
                'automatique' => true,
                'motif' => 'Reste du chèque à 0,00 DH',
            ])
            ->log("Chèque {$cheque->reference} : {$ancien} → ".Cheque::STATUT_ENCAISSE.' (reste 0,00 DH)');

        return true;
    }
}
