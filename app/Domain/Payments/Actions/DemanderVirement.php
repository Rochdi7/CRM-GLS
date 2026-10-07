<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Domain\Students\Support\GardeEtudiantTransfere;
use App\Models\Employee;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Virement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * DEMANDE de virement (07/10/2026) — l'étudiant se présente au centre avec la
 * preuve qu'il a viré l'argent sur le compte de l'école ; le guichet le
 * DÉCLARE, le comptable VÉRIFIERA (ValiderVirement).
 *
 * ⚠ AUCUN argent ne bouge ici : rien n'est encore vérifié, donc rien n'est
 * encaissé. La ligne est « En attente de vérification », hors de tout total
 * encaissé ; la page des paiements la compte À PART (« Virements en
 * attente »).
 *
 * Bornes, relues SOUS VERROU (§11) :
 *  1. le frais appartient à l'inscription, l'inscription à l'étudiant ;
 *  2. l'inscription est ACTIVE (même règle qu'un encaissement : de l'argent
 *     neuf ne rouvre pas un dossier clos) et l'étudiant n'est pas transféré ;
 *  3. le frais n'est pas MASQUÉ (audit R-01 : rien ne se pose sur un frais
 *     qui n'est plus dû) ;
 *  4. le montant ne dépasse pas le reste dû MOINS les virements déjà en
 *     attente sur ce frais — sinon deux demandes passent et la seconde
 *     validation tombe sur un frais déjà soldé.
 */
final class DemanderVirement
{
    /**
     * @param array{
     *     student_id:int, inscription_id:int, fee_id:int, montant:float|string,
     *     nom_payeur:string, reference_virement:string, date_operation:string,
     *     note?:string|null
     * } $data
     */
    public function handle(array $data, UploadedFile $justificatif, Employee $demandePar, int $etablissementId): Virement
    {
        return DB::transaction(function () use ($data, $justificatif, $demandePar, $etablissementId): Virement {
            /** @var Inscription $inscription */
            $inscription = Inscription::query()->whereKey((int) $data['inscription_id'])->lockForUpdate()->firstOrFail();

            if ($inscription->student_id !== (int) $data['student_id']) {
                throw ValidationException::withMessages([
                    'inscription_id' => __('This registration does not belong to the selected student.'),
                ]);
            }

            if ($inscription->statut !== Inscription::STATUT_ACTIVE) {
                throw ValidationException::withMessages([
                    'inscription_id' => __('This registration is no longer active - record the money as an advance instead.'),
                ]);
            }

            GardeEtudiantTransfere::assertNonTransfere($inscription->student);

            /** @var InscriptionFee $fee */
            $fee = InscriptionFee::query()->whereKey((int) $data['fee_id'])->lockForUpdate()->firstOrFail();

            if ($fee->inscription_id !== $inscription->id) {
                throw ValidationException::withMessages([
                    'fee_id' => __('One of the selected fees does not belong to this registration.'),
                ]);
            }

            if ($fee->estMasque()) {
                throw ValidationException::withMessages([
                    'fee_id' => __('This fee is no longer active.'),
                ]);
            }

            $montant = round((float) $data['montant'], 2);
            $disponible = self::resteDisponible($fee);

            if ($montant > $disponible) {
                throw ValidationException::withMessages([
                    'montant' => __('The amount cannot exceed the remaining balance of this fee (:reste MAD, pending transfers deducted).', [
                        'reste' => number_format($disponible, 2, ',', ' '),
                    ]),
                ]);
            }

            $virement = Virement::create([
                'reference' => ReferenceGenerator::make('VIR', 'virements'),
                'student_id' => $inscription->student_id,
                'inscription_id' => $inscription->id,
                'inscription_fee_id' => $fee->id,
                'etablissement_id' => $etablissementId,
                'montant' => $montant,
                'nom_payeur' => trim((string) $data['nom_payeur']),
                'reference_virement' => trim((string) $data['reference_virement']),
                'date_operation' => $data['date_operation'],
                'note' => ($data['note'] ?? '') !== '' ? $data['note'] : null,
                'demande_par_id' => $demandePar->id,
            ]);

            $virement->addMedia($justificatif)->toMediaCollection(Virement::MEDIA_JUSTIFICATIF);

            activity('virement')
                ->performedOn($virement)
                ->event('virement_demande')
                ->withProperties([
                    'reference' => $virement->reference,
                    'montant' => number_format($montant, 2, '.', ''),
                    'etudiant_id' => $virement->student_id,
                    'inscription_fee_id' => $fee->id,
                    'frais' => $fee->nom,
                    'nom_payeur' => $virement->nom_payeur,
                    'reference_virement' => $virement->reference_virement,
                    'date_operation' => $virement->date_operation?->toDateString(),
                    'demande_par' => $demandePar->nomComplet(),
                    'etablissement_id' => $etablissementId,
                ])
                ->log("Virement {$virement->reference} déclaré, en attente de vérification");

            return $virement;
        });
    }

    /**
     * Reste dû du frais MOINS les virements encore en attente dessus — ce
     * qu'une NOUVELLE demande peut réclamer. UNE définition, partagée avec
     * la liste des frais impayés (GetInscriptionUnpaidFees) pour que le
     * formulaire plafonne exactement ce que le serveur accepte.
     */
    public static function resteDisponible(InscriptionFee $fee): float
    {
        $reste = round(max(0.0, (float) $fee->montant - $fee->montantPaye()), 2);
        $enAttente = round((float) Virement::query()
            ->where('inscription_fee_id', $fee->id)
            ->enAttente()
            ->sum('montant'), 2);

        return round(max(0.0, $reste - $enAttente), 2);
    }
}
