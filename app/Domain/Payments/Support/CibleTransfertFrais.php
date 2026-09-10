<?php

declare(strict_types=1);

namespace App\Domain\Payments\Support;

use App\Models\Inscription;
use App\Models\InscriptionFee;

/**
 * « Sur quelle ligne de l'inscription cible ce paiement peut-il se poser ? »
 * — LA définition unique de l'éligibilité d'un transfert de frais entre
 * étudiants (10/09/2026).
 *
 * Deux consommateurs, une seule règle :
 *  - TransfererFraisVersAutreEtudiant l'applique SOUS VERROU au moment
 *    d'écrire et transforme un refus en ValidationException ;
 *  - le dropdown « Inscription » du modal (EncaissementController::
 *    transferTargets) l'applique en lecture pour n'offrir QUE les dossiers
 *    qui passeront — un frais déjà soldé chez la sœur n'est pas proposé.
 *
 * Si la liste et l'action avaient chacune leur copie, elles finiraient par
 * diverger, et l'écran promettrait un transfert que le serveur rejette
 * (CLAUDE.md §5 : un read-model ne redérive jamais une règle métier).
 *
 * La règle, dans l'ordre :
 *  1. même entrée du catalogue (`frais_id`, repli sur le `nom` pour les
 *     lignes legacy sans catalogue) — un frais ne se transforme jamais en un
 *     autre en changeant de dossier ;
 *  2. ligne VISIBLE — jamais un frais masqué (audit R-01) ;
 *  3. reste dû ≥ montant — le paiement n'est pas fractionné.
 */
final class CibleTransfertFrais
{
    public const AUCUNE_LIGNE = 'aucune_ligne';

    public const LIGNE_MASQUEE = 'ligne_masquee';

    public const RESTE_INSUFFISANT = 'reste_insuffisant';

    /**
     * @return array{frais: InscriptionFee|null, raison: string|null, reste: float}
     *   `frais` est la ligne retenue (raison null), sinon `raison` dit
     *   pourquoi et `reste` porte le plus grand reste dû rencontré (0 quand
     *   aucune ligne visible) pour que le message puisse le citer.
     */
    public function resoudre(InscriptionFee $source, Inscription $cible, float $montant, bool $verrouiller): array
    {
        $candidates = $cible->fees()
            ->when(
                $source->frais_id !== null,
                fn ($q) => $q->where('frais_id', $source->frais_id),
                fn ($q) => $q->where('nom', $source->nom),
            )
            ->orderBy('date_echeance')
            ->orderBy('id')
            ->when($verrouiller, fn ($q) => $q->lockForUpdate())
            ->get();

        if ($candidates->isEmpty()) {
            return ['frais' => null, 'raison' => self::AUCUNE_LIGNE, 'reste' => 0.0];
        }

        $visibles = $candidates->reject(fn (InscriptionFee $fee): bool => $fee->estMasque());

        if ($visibles->isEmpty()) {
            return ['frais' => null, 'raison' => self::LIGNE_MASQUEE, 'reste' => 0.0];
        }

        $montant = round($montant, 2);
        $resteMax = 0.0;

        foreach ($visibles as $fee) {
            $reste = round((float) $fee->montant - $fee->montantPaye(), 2);
            $resteMax = max($resteMax, $reste);

            if ($montant <= $reste) {
                return ['frais' => $fee, 'raison' => null, 'reste' => $reste];
            }
        }

        return ['frais' => null, 'raison' => self::RESTE_INSUFFISANT, 'reste' => $resteMax];
    }

    /** Le message d'un refus, prêt pour une ValidationException. */
    public function message(string $raison, InscriptionFee $source, float $montant, float $reste): string
    {
        return match ($raison) {
            self::AUCUNE_LIGNE => __('The target registration has no « :frais » line: the same fee must exist on both registrations.', [
                'frais' => $source->nom,
            ]),
            self::LIGNE_MASQUEE => __('The « :frais » line of the target registration is hidden: money never lands on a fee that is no longer due.', [
                'frais' => $source->nom,
            ]),
            default => __('The payment (:montant MAD) exceeds what the « :frais » line of the target registration still owes (:reste MAD).', [
                'montant' => number_format(round($montant, 2), 2, '.', ' '),
                'frais' => $source->nom,
                'reste' => number_format(max(0.0, $reste), 2, '.', ' '),
            ]),
        };
    }
}
