<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Finance\Support\CaisseResolver;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Virement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * VALIDATION d'un virement par le comptable (07/10/2026,
 * `virements.validate`) — le SEUL moment où l'argent d'un virement entre.
 *
 * Le comptable a vérifié sur le relevé bancaire que le virement est bien
 * arrivé : l'encaissement est alors créé par EnregistrerEncaissement, comme
 * n'importe quel paiement — il crédite le compte « Virement » du centre de
 * la DEMANDE (jamais du contexte du comptable), et le frais est resoldé.
 *
 * Trois choses que cet encaissement reprend de la DEMANDE, pas de la
 * décision :
 *  - `date_paiement` = `date_operation` : le jour où l'étudiant s'est
 *    présenté au centre avec sa preuve, pas le jour du clic ;
 *  - `agent_id` = l'employé qui a reçu l'étudiant et déclaré le virement ;
 *  - le centre = celui où la demande a été saisie.
 *
 * Bornes, relues SOUS VERROU (§11) : la demande est encore en attente ;
 * celui qui l'a déclarée ne la valide pas (contrôle à deux personnes, comme
 * ValiderRemiseCheque) ; le frais existe toujours, n'est pas masqué, son
 * inscription est toujours Active et son reste dû couvre le montant — sans
 * quoi la validation REFUSE avec le motif, pour que le comptable refuse la
 * demande en connaissance de cause plutôt que de poser l'argent au mauvais
 * endroit.
 */
final class ValiderVirement
{
    public function __construct(
        private readonly EnregistrerEncaissement $enregistrer,
        private readonly CaisseResolver $resolver,
    ) {}

    public function handle(Virement $virement, Employee $par): Virement
    {
        return DB::transaction(function () use ($virement, $par): Virement {
            /** @var Virement $locked */
            $locked = Virement::query()->whereKey($virement->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isDecided()) {
                throw ValidationException::withMessages([
                    'statut' => __('This bank transfer request has already been processed.'),
                ]);
            }

            if ($locked->demande_par_id === $par->id) {
                throw ValidationException::withMessages([
                    'statut' => __('The employee who declared the bank transfer cannot validate it.'),
                ]);
            }

            $fee = $locked->inscription_fee_id === null
                ? null
                : InscriptionFee::query()->whereKey($locked->inscription_fee_id)->lockForUpdate()->first();

            $blocage = self::blocage($locked, $fee);

            if ($blocage !== null) {
                throw ValidationException::withMessages(['statut' => $blocage]);
            }

            /** @var InscriptionFee $fee */
            $demandePar = Employee::query()->withoutGlobalScopes()->findOrFail($locked->demande_par_id);

            // Le compte « Virement » du centre de la DEMANDE — jamais celui du
            // contexte actif du comptable, qui travaille souvent depuis
            // « Tous les centres ».
            $caisse = $this->resolver->resolveFor($demandePar, Encaissement::METHODE_VIREMENT, (int) $locked->etablissement_id);

            $note = trim(sprintf(
                'Virement %s - payeur : %s - réf. bancaire : %s%s',
                $locked->reference,
                $locked->nom_payeur,
                $locked->reference_virement,
                $locked->note !== null && $locked->note !== '' ? ' - '.$locked->note : '',
            ));

            $encaissement = $this->enregistrer->handle([
                'student_id' => $locked->student_id,
                'inscription_fee_id' => $fee->id,
                'montant' => (float) $locked->montant,
                'methode' => Encaissement::METHODE_VIREMENT,
                'date_paiement' => $locked->date_operation?->toDateString(),
                'caisse_id' => $caisse->id,
                'etablissement_id' => $locked->etablissement_id,
                'note' => $note,
            ], $demandePar);

            $locked->update([
                'statut' => Virement::STATUT_VALIDE,
                'encaissement_id' => $encaissement->id,
                'decide_par_id' => $par->id,
                'decide_le' => now(),
                'motif_refus' => null,
            ]);

            activity('virement')
                ->performedOn($locked)
                ->event('virement_valide')
                ->withProperties([
                    'reference' => $locked->reference,
                    'montant' => number_format((float) $locked->montant, 2, '.', ''),
                    'encaissement_id' => $encaissement->id,
                    'encaissement_reference' => $encaissement->reference,
                    'date_operation' => $locked->date_operation?->toDateString(),
                    'caisse_id' => $caisse->id,
                    'etablissement_id' => $locked->etablissement_id,
                    'valide_par' => $par->nomComplet(),
                ])
                ->log("Virement {$locked->reference} validé → encaissement {$encaissement->reference}");

            return $locked;
        });
    }

    /**
     * Pourquoi cette demande ne peut pas être validée — null si elle le
     * peut. UNE définition : rejouée sous verrou ci-dessus, portée à l'écran
     * par GetVirementsList (`validationBlocker`) — le comptable voit la
     * raison AVANT de cliquer, au lieu de découvrir un refus.
     */
    public static function blocage(Virement $virement, ?InscriptionFee $fee): ?string
    {
        if ($fee === null) {
            return __('The fee this transfer was declared for no longer exists.');
        }

        if ($fee->estMasque()) {
            return __('This fee is no longer active.');
        }

        $inscription = $fee->inscription;

        if ($inscription === null || $inscription->statut !== Inscription::STATUT_ACTIVE) {
            return __('This registration is no longer active - record the money as an advance instead.');
        }

        $reste = round(max(0.0, (float) $fee->montant - $fee->montantPaye()), 2);

        if (round((float) $virement->montant, 2) > $reste) {
            return __('The fee only has :reste MAD left to pay: the declared amount exceeds it.', [
                'reste' => number_format($reste, 2, ',', ' '),
            ]);
        }

        return null;
    }
}
