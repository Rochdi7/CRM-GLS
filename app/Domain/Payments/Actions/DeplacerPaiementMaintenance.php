<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Registrations\Actions\PurgerPresencesFantomes;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * « Déplacer un paiement » — l'orchestrateur de l'outil de maintenance
 * (/backoffice/move-payment, 21–23/09/2026).
 *
 * Il ne réinvente aucune règle : il choisit l'action métier qui correspond
 * à la ligne, et enchaîne dans UNE transaction ce qui se faisait jusque-là
 * en trois scripts PuTTY :
 *
 *   - ligne rattachée à un FRAIS → `TransfererFraisVersAutreEtudiant`
 *     (le frais cible est DÉTECTÉ par son nom, jamais choisi) ;
 *     précédé, si l'opérateur l'a demandé, de `PurgerPresencesFantomes`
 *     sur le dossier source — sinon la garde « zéro présence » refuse,
 *     comme pour n'importe qui ;
 *   - AVANCE (aucun frais) → `AffecterAvanceVersAutreEtudiant`, sur le
 *     frais que l'opérateur a désigné (une avance n'a pas de nom de frais
 *     à faire correspondre).
 *
 * Deux refus propres à l'orchestrateur, tous deux « signaler plutôt que
 * masquer » (§11) : un frais cible fourni pour une ligne à frais (il
 * serait ignoré en silence — l'écran mentirait), et un frais cible qui
 * n'appartient pas à l'inscription désignée.
 *
 * Une transaction externe enveloppe les transactions internes des actions
 * (savepoints) : si le transfert refuse, les présences purgées reviennent.
 */
final class DeplacerPaiementMaintenance
{
    public function __construct(
        private readonly TransfererFraisVersAutreEtudiant $transfererFrais,
        private readonly AffecterAvanceVersAutreEtudiant $affecterAvance,
        private readonly PurgerPresencesFantomes $purger,
    ) {}

    /**
     * @return array{encaissement: Encaissement, mode: 'frais'|'avance', presencesSupprimees: int}
     */
    public function handle(
        Encaissement $encaissement,
        Inscription $cible,
        string $motif,
        bool $purgerPresences,
        ?int $fraisCibleId,
    ): array {
        return DB::transaction(function () use ($encaissement, $cible, $motif, $purgerPresences, $fraisCibleId): array {
            $row = Encaissement::query()
                ->with('fee.inscription')
                ->whereKey($encaissement->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($row->isAvance()) {
                if ($fraisCibleId === null) {
                    throw ValidationException::withMessages([
                        'inscription_fee_id' => __('Choose which fee of the target registration this advance pays.'),
                    ]);
                }

                $fee = InscriptionFee::query()
                    ->whereKey($fraisCibleId)
                    ->where('inscription_id', $cible->getKey())
                    ->first();

                if ($fee === null) {
                    throw ValidationException::withMessages([
                        'inscription_fee_id' => __('That fee does not belong to the target registration.'),
                    ]);
                }

                return [
                    'encaissement' => $this->affecterAvance->handle($row, $fee, $motif),
                    'mode' => 'avance',
                    'presencesSupprimees' => 0,
                ];
            }

            if ($fraisCibleId !== null) {
                throw ValidationException::withMessages([
                    'inscription_fee_id' => __('For a payment attached to a fee, the target fee is detected by name - do not pick one.'),
                ]);
            }

            $purgees = 0;

            if ($purgerPresences && $row->fee?->inscription !== null) {
                $purgees = $this->purger->handle($row->fee->inscription, $motif);
            }

            return [
                'encaissement' => $this->transfererFrais->handle($row, $cible, $motif),
                'mode' => 'frais',
                'presencesSupprimees' => $purgees,
            ];
        });
    }
}
