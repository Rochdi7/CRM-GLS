<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Support\ChequeOrigine;
use App\Domain\Payments\Support\ValiditeAvance;
use App\Models\Encaissement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rouvre une avance EXPIRÉE (ou sur le point de l'être) pour 14 jours à
 * compter d'aujourd'hui — la seule sortie prévue à l'écran pour un cas
 * légitime (l'étudiant malade qui revient à la troisième semaine, le
 * paiement anticipé de l'année suivante).
 *
 * Réservé au super-admin (`payments.override-advance-expiry`, superAdminOnly) : sans
 * cela le délai ne protège rien, celui qui veut réutiliser un vieil argent
 * se prolongerait lui-même. Trois bornes :
 *
 *  1. la durée n'est PAS choisie : toujours `ValiditeAvance::DUREE_JOURS`
 *     depuis aujourd'hui. Une date libre permettrait d'écrire 2099 et de
 *     rendre une avance éternelle — le délai deviendrait décoratif ;
 *  2. motif OBLIGATOIRE, conservé dans le journal avec l'ancienne et la
 *     nouvelle date : c'est ce qui explique, des mois plus tard, pourquoi de
 *     l'argent périmé a pu être appliqué ;
 *  3. il doit rester de l'argent à appliquer, et il doit exister : une
 *     avance épuisée ou financée par un chèque rejeté n'a rien à rouvrir.
 *
 * Aucun montant, aucune caisse ne bouge — seule `avance_expire_le` change,
 * sur la ligne relue sous verrou (§11).
 */
final class ProlongerValiditeAvance
{
    public function handle(Encaissement $avance, string $motif): Encaissement
    {
        $motif = trim($motif);

        if ($motif === '') {
            throw ValidationException::withMessages([
                'motif' => __('A reason is required to extend an advance.'),
            ]);
        }

        return DB::transaction(function () use ($avance, $motif): Encaissement {
            $row = Encaissement::query()->whereKey($avance->getKey())->lockForUpdate()->firstOrFail();

            if (! $row->isAvance()) {
                throw ValidationException::withMessages([
                    'avance' => __('This payment is not an unallocated advance.'),
                ]);
            }

            if (ChequeOrigine::estRejete($row)) {
                throw ValidationException::withMessages([
                    'avance' => __('This advance was funded by a rejected cheque and cannot be applied.'),
                ]);
            }

            $restant = $row->montantRestant();

            if ($restant <= 0.0) {
                throw ValidationException::withMessages([
                    'avance' => __('This advance has no remaining balance: there is nothing to extend.'),
                ]);
            }

            $ancienne = ValiditeAvance::expireLe($row);
            $nouvelle = ValiditeAvance::echeanceDepuis(now());

            // Journalisé par Auditable (la colonne change) ET par l'entrée
            // dédiée ci-dessous, qui seule porte le motif.
            $row->update(['avance_expire_le' => $nouvelle]);

            activity('encaissement')
                ->performedOn($row)
                ->event('avance_prolongee')
                ->withProperties([
                    'motif' => $motif,
                    'expire_le_avant' => $ancienne?->toDateString(),
                    'expire_le_apres' => $nouvelle->toDateString(),
                    'montant_restant' => number_format($restant, 2, '.', ''),
                    'etudiant_id' => $row->student_id,
                    'caisse_id' => $row->caisse_id,
                ])
                ->log(sprintf(
                    'Validité de l\'avance %s prolongée jusqu\'au %s - %s',
                    $row->reference,
                    $nouvelle->format('d/m/Y'),
                    $motif,
                ));

            return $row->refresh();
        });
    }
}
