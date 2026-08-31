<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Actions;

use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Masque ou restaure UNE ligne de frais d'une inscription — la corbeille de
 * « Frais de cette inscription » dans le modal d'édition. Elle ne supprime
 * jamais : elle pose `masque_le`, la ligne et son historique de paiement
 * restent intacts. montant_total est recalculé sur les frais encore visibles.
 *
 * ⚠ **Masquer un frais DÉJÀ PAYÉ libère d'abord son argent en avance**
 * (31/08/2026). Sans cela, l'argent restait accroché à une ligne invisible :
 * il ne comptait plus nulle part — ni dans le dû, ni dans l'onglet Avances —
 * et l'étudiant ne pouvait plus le réutiliser alors qu'il avait bel et bien
 * payé. Signalé sur une inscription dont « Frais d'inscription A1/A2/B1 »
 * (300 DH) et « Frais d'inscription B2 » (200 DH) étaient marqués Payé puis
 * masqués : 500 DH évaporés de l'écran.
 *
 * C'est EXACTEMENT la libération que fait déjà le retrait d'un frais au
 * niveau du GROUPE (Groups\Actions\RetirerFraisGroupe) et le retrait d'une
 * ligne dans MettreAJourFraisInscription — les trois chemins qui retirent un
 * frais d'une inscription doivent se comporter pareil, sinon lequel des
 * trois l'utilisateur emprunte change ce qu'il advient de son argent.
 *
 * Ordre imposé : convertir PUIS masquer. Le convertisseur revérifie que
 * chaque encaissement appartient bien à cette inscription et recalcule le
 * statut du frais ; un frais n'est vraiment « retiré » qu'une fois son
 * argent rendu disponible.
 *
 * Un frais NON payé ne déclenche rien : il n'y a pas d'argent à libérer.
 * Un paiement REMBOURSÉ est écarté (son argent a déjà quitté la caisse, le
 * convertisseur le refuse) plutôt que de faire échouer tout le masquage.
 *
 * Restaurer ne « re-colle » PAS les avances au frais : elles restent des
 * avances, ré-applicables à la main sur n'importe quel frais de l'étudiant
 * (même règle que RetirerFraisGroupe::restore). Le frais revient donc dû, et
 * l'argent est là, en attente d'affectation — ce qui est le comportement
 * correct : entre-temps l'avance a pu être appliquée ailleurs.
 */
final class BasculerVisibiliteFraisInscription
{
    public function __construct(
        private readonly ConvertirEncaissementsEnAvance $convertirEnAvance,
    ) {}

    /**
     * @return float le montant libéré en avance (0.0 si le frais n'était pas payé)
     */
    public function hide(Inscription $inscription, InscriptionFee $fee): float
    {
        return DB::transaction(function () use ($inscription, $fee): float {
            $this->assertBelongsTo($inscription, $fee);

            $encaissements = Encaissement::query()
                ->where('inscription_fee_id', $fee->id)
                ->whereDoesntHave('remboursements')
                ->get();

            $libere = 0.0;

            if ($encaissements->isNotEmpty()) {
                $this->convertirEnAvance->handle($inscription, $encaissements->pluck('id')->all());
                $libere = round((float) $encaissements->sum('montant'), 2);
            }

            $fee->update(['masque_le' => now(), 'masque_origine' => InscriptionFee::MASQUE_ORIGINE_MANUEL]);

            $this->recalculerMontantTotal($inscription);

            return $libere;
        });
    }

    public function restore(Inscription $inscription, InscriptionFee $fee): void
    {
        DB::transaction(function () use ($inscription, $fee): void {
            $this->assertBelongsTo($inscription, $fee);

            $fee->update(['masque_le' => null, 'masque_origine' => null]);

            $this->recalculerMontantTotal($inscription);
        });
    }

    private function assertBelongsTo(Inscription $inscription, InscriptionFee $fee): void
    {
        if ($fee->inscription_id !== $inscription->id) {
            throw ValidationException::withMessages([
                'fee' => __('This fee does not belong to this registration.'),
            ]);
        }
    }

    private function recalculerMontantTotal(Inscription $inscription): void
    {
        $inscription->update([
            'montant_total' => $inscription->fees()->whereNull('masque_le')->sum('montant') ?: null,
        ]);
    }
}
