<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Support\CaisseResolver;
use App\Domain\Payments\Support\ChequeOrigine;
use App\Models\DemandeRemboursement;
use App\Models\Employee;
use App\Models\Encaissement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * APPROBATION d'une demande de remboursement (02/11/2026,
 * `refunds.approve` — directeur dans ses centres, ou super-admin) : le SEUL
 * moment où l'argent d'un remboursement demandé sort.
 *
 * Le remboursement est créé par EnregistrerRemboursement, comme n'importe
 * quel remboursement : plafond du paiement, frais masqué, solde du compte
 * débité — tout est rejoué sous verrou. Ce qui se décide ICI, c'est le
 * compte débité, selon la méthode choisie :
 *
 *  - **Espèces** (défaut, et seule méthode d'un directeur) — la caisse
 *    PHYSIQUE de l'employé qui a DEMANDÉ le remboursement
 *    (CaisseResolver::forRemboursement : c'est lui qui rend l'argent au
 *    guichet), avec l'exception du chèque rejeté (le compte Chèque du
 *    centre, l'argent n'étant jamais entré dans un tiroir) ;
 *  - **TPE / Virement** (super-admin seul, `refunds.choose-method`) — le
 *    compte de cette méthode du centre de la DEMANDE, jamais celui du
 *    contexte de l'approbateur (qui travaille souvent depuis « Tous les
 *    centres »).
 *
 * `agent_id` du remboursement = le DEMANDEUR (celui qui a reçu l'étudiant),
 * comme l'encaissement né d'un virement validé ; l'approbateur est sur la
 * demande (`decide_par_id`) et dans la note.
 */
final class ApprouverDemandeRemboursement
{
    public function __construct(
        private readonly EnregistrerRemboursement $enregistrer,
        private readonly CaisseResolver $resolver,
    ) {}

    public function handle(DemandeRemboursement $demande, Employee $par, string $methode, bool $peutChoisirMethode): DemandeRemboursement
    {
        return DB::transaction(function () use ($demande, $par, $methode, $peutChoisirMethode): DemandeRemboursement {
            /** @var DemandeRemboursement $locked */
            $locked = DemandeRemboursement::query()->whereKey($demande->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isEnAttente()) {
                throw ValidationException::withMessages([
                    'demande' => __('This refund request has already been processed.'),
                ]);
            }

            if (! in_array($methode, DemandeRemboursement::METHODES, true)) {
                throw ValidationException::withMessages([
                    'methode' => __('Unknown payment method.'),
                ]);
            }

            // Refusé, jamais ramené à Espèces en silence : l'écran aurait
            // annoncé une sortie du compte TPE pendant que le tiroir payait.
            if ($methode !== Encaissement::METHODE_ESPECES && ! $peutChoisirMethode) {
                throw ValidationException::withMessages([
                    'methode' => __('Only a super-admin can approve a refund by card or bank transfer.'),
                ]);
            }

            $encaissement = null;

            if ($locked->encaissement_reference !== null) {
                $encaissement = $locked->encaissement_id === null
                    ? null
                    : Encaissement::query()->with(['caisse', 'cheque'])->find($locked->encaissement_id);

                if ($encaissement === null) {
                    throw ValidationException::withMessages([
                        'demande' => __('The payment this refund was requested for (:reference) no longer exists.', [
                            'reference' => $locked->encaissement_reference,
                        ]),
                    ]);
                }
            }

            $demandeur = Employee::query()->withoutGlobalScopes()->findOrFail($locked->demande_par_id);

            if ($methode === Encaissement::METHODE_ESPECES) {
                $caisse = $this->resolver->forRemboursement($demandeur, $encaissement);
            } else {
                if ($encaissement !== null && ChequeOrigine::estRejete($encaissement)) {
                    throw ValidationException::withMessages([
                        'methode' => __("This payment was funded by a rejected cheque: the refund reverses the centre's Cheque account, approve it in cash."),
                    ]);
                }

                $caisse = $this->resolver->resolveFor($demandeur, $methode, $locked->etablissement_id === null ? null : (int) $locked->etablissement_id);
            }

            $ligne = sprintf('Demande %s approuvée par %s (%s).', $locked->reference, $par->nomComplet(), $methode);
            $note = trim((string) $locked->note);

            $remboursement = $this->enregistrer->handle([
                'beneficiaire_id' => $locked->beneficiaire_id,
                'encaissement_id' => $encaissement?->id,
                'caisse_id' => $caisse->id,
                'etablissement_id' => $locked->etablissement_id,
                'montant' => (float) $locked->montant,
                // L'argent sort AUJOURD'HUI — c'est la date du mouvement de
                // caisse, pas celle de la demande.
                'date_remboursement' => now()->toDateString(),
                'motif' => $locked->motif,
                'note' => $note === '' ? $ligne : $note."\n".$ligne,
            ], $demandeur);

            $locked->update([
                'statut' => DemandeRemboursement::STATUT_APPROUVEE,
                'methode' => $methode,
                'remboursement_id' => $remboursement->id,
                'decide_par_id' => $par->id,
                'decide_le' => now(),
                'motif_refus' => null,
            ]);

            activity('demande_remboursement')
                ->performedOn($locked)
                ->event('demande_remboursement_approuvee')
                ->withProperties([
                    'reference' => $locked->reference,
                    'montant' => number_format((float) $locked->montant, 2, '.', ''),
                    'methode' => $methode,
                    'caisse_id' => $caisse->id,
                    'remboursement_reference' => $remboursement->reference,
                    'etablissement_id' => $locked->etablissement_id,
                    'approuve_par' => $par->nomComplet(),
                ])
                ->log("Demande de remboursement {$locked->reference} approuvée → {$remboursement->reference}");

            return $locked;
        });
    }
}
