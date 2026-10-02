<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Cheque;
use App\Models\Employee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * « Remise à la banque » (30/09/2026) — l'employé qui porte le chèque à la
 * banque le consigne : date de remise et REÇU DE DÉPÔT (obligatoire). Le chèque passe « Déposé » et
 * attend la décision du comptable (ValiderRemiseCheque).
 *
 * ⚠ AUCUN argent ne bouge, ni ici ni à la validation : le compte « Chèque »
 * du centre garde le montant. Ce parcours ne suit que le PAPIER.
 *
 * Bornes, relues SOUS VERROU (§11) :
 *  1. type « À déposer » — une GARANTIE ne va jamais à la banque, elle se
 *     restitue quand l'étudiant règle autrement ;
 *  2. statut « En possession » et pas restitué ;
 *  3. reste 0,00 DH — tout le chèque doit déjà avoir financé des paiements,
 *     sinon la banque recevrait de l'argent qu'aucun encaissement ne déclare.
 */
final class DeposerChequeEnBanque
{
    /**
     * Pourquoi ce chèque ne peut pas (encore) être remis à la banque — null
     * s'il le peut. SEULE définition de la règle : l'action la rejoue sous
     * verrou, GetChequesList la porte à l'écran (`depotBlocker`).
     */
    public static function blocage(Cheque $cheque, float $montantUtilise): ?string
    {
        if ($cheque->type !== Cheque::TYPE_A_DEPOSER) {
            return __('A guarantee cheque never goes to the bank: return it to its owner once they have paid another way.');
        }

        if ($cheque->statut !== Cheque::STATUT_EN_POSSESSION || $cheque->retourne_le !== null) {
            return __('Only a cheque still in hand can be deposited at the bank.');
        }

        $reste = round(max(0.0, (float) $cheque->montant - $montantUtilise), 2);

        if ($reste > 0.0) {
            return __('Record the payments funded by this cheque first: :reste MAD are not yet allocated.', [
                'reste' => number_format($reste, 2, ',', ' '),
            ]);
        }

        return null;
    }

    public function handle(
        Cheque $cheque,
        string $dateRemise,
        UploadedFile $recu,
        Employee $par,
    ): Cheque {
        return DB::transaction(function () use ($cheque, $dateRemise, $recu, $par): Cheque {
            /** @var Cheque $locked */
            $locked = Cheque::query()->whereKey($cheque->getKey())->lockForUpdate()->firstOrFail();

            $blocage = self::blocage($locked, $locked->montantUtilise());

            if ($blocage !== null) {
                throw ValidationException::withMessages(['date_remise' => $blocage]);
            }

            $locked->update([
                'statut' => Cheque::STATUT_DEPOSE,
                'date_remise' => $dateRemise,
                'depose_par_id' => $par->id,
            ]);

            $locked->addMedia($recu)->toMediaCollection(Cheque::MEDIA_JUSTIFICATIF_DEPOT);

            activity('cheque')
                ->performedOn($locked)
                ->event('cheque_remise_banque')
                ->withProperties([
                    'reference' => $locked->reference,
                    'numero_cheque' => $locked->numero_cheque,
                    'montant' => number_format((float) $locked->montant, 2, '.', ''),
                    'date_remise' => $dateRemise,
                    'depose_par' => $par->nomComplet(),
                ])
                ->log("Chèque {$locked->reference} remis à la banque");

            return $locked;
        });
    }
}
