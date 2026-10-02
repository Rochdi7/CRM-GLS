<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Cheque;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Supprime un chèque de la base — super-admin uniquement (`cheques.delete`
 * ∈ superAdminOnly(), 29/09/2026). Typiquement un chèque créé en trop par
 * l'import legacy (IMPORT-P…).
 *
 * ⚠ AUCUN argent ne bouge. Un chèque est un inventaire OFF-LEDGER : les
 * encaissements qu'il a financés restent tels quels (montant, méthode
 * « Chèque », caisse, date, et la COPIE de `numero_cheque` / `banque`
 * qu'ils portent déjà). Seul leur lien `cheque_id` passe à NULL — un
 * `save()` par ligne pour qu'Auditable journalise chacun — et le chèque
 * est ensuite supprimé. Ils deviennent des paiements par chèque ordinaires,
 * comme ceux de l'import sans chèque suivi.
 *
 * Une borne : un chèque REJETÉ qui a financé des paiements est REFUSÉ.
 * Ce lien est ce qui dit que l'argent n'est jamais arrivé — un
 * remboursement le lit pour débiter le compte Chèque du centre au lieu de
 * la caisse physique (CaisseResolver::forRemboursement, ChequeOrigine).
 * Le couper ferait sortir du vrai cash pour un chèque sans provision.
 */
final class SupprimerCheque
{
    /**
     * @return int nombre de paiements détachés
     */
    public function handle(Cheque $cheque): int
    {
        return DB::transaction(function () use ($cheque): int {
            $cheque = Cheque::query()->whereKey($cheque->id)->lockForUpdate()->firstOrFail();
            $encaissements = $cheque->encaissements()->lockForUpdate()->get();

            if ($cheque->statut === Cheque::STATUT_REJETE && $encaissements->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'cheque' => __('A rejected cheque that funded payments cannot be deleted: refunds rely on it.'),
                ]);
            }

            foreach ($encaissements as $encaissement) {
                $encaissement->cheque_id = null;
                $encaissement->save();
            }

            activity('cheque')
                ->performedOn($cheque)
                ->event('cheque_supprime')
                ->withProperties([
                    'reference' => $cheque->reference,
                    'numero_cheque' => $cheque->numero_cheque,
                    'montant' => number_format((float) $cheque->montant, 2, '.', ''),
                    'statut' => $cheque->statut,
                    'banque' => $cheque->banque,
                    'etudiant_id' => $cheque->student_id,
                    'proprietaire' => $cheque->proprietaire_nom,
                    'encaissements_detaches' => $encaissements->pluck('reference')->all(),
                ])
                ->log("Chèque {$cheque->reference} supprimé ({$encaissements->count()} paiement(s) détaché(s))");

            $cheque->delete();

            return $encaissements->count();
        });
    }
}
