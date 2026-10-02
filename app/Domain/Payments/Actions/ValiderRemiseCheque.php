<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Cheque;
use App\Models\Employee;
use App\Models\Remboursement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Décision du COMPTABLE sur une remise à la banque (30/09/2026,
 * `cheques.validate-deposit`).
 *
 * ACCEPTER — la banque a accepté le chèque : « Déposé » → « Encaissé », avec
 * qui l'a validé et quand. REJETER — la banque l'a refusé : → « Rejeté ».
 *
 * ⚠ AUCUN argent ne bouge, dans un sens comme dans l'autre (décision du
 * 01/10/2026) : l'argent d'un chèque reste dans le compte « Chèque » du
 * centre, où l'encaissement l'a crédité (CaisseResolver). Les suites d'un
 * rejet restent les gestes existants (remboursement depuis le compte Chèque,
 * restitution du papier).
 *
 * ANNULER LE REJET (02/10/2026) — « Rejeté » cliqué par erreur : le chèque
 * revient « Déposé » et attend de nouveau la décision. Refusé dès que le
 * rejet a eu une SUITE qu'on ne peut pas défaire d'ici : papier restitué à
 * son propriétaire, ou remboursement (non annulé) d'un paiement qu'il a
 * financé — ce remboursement a débité le compte Chèque PARCE QUE le chèque
 * était rejeté (CaisseResolver::forRemboursement).
 *
 * Bornes, sous verrou : seul un chèque « Déposé » se décide (un ancien
 * « Encaissé » peut encore être rejeté) ; l'employé qui a DÉPOSÉ le chèque ne
 * valide jamais sa propre remise (contrôle à deux personnes).
 */
final class ValiderRemiseCheque
{
    public function accepter(Cheque $cheque, Employee $par): Cheque
    {
        return DB::transaction(function () use ($cheque, $par): Cheque {
            /** @var Cheque $locked */
            $locked = Cheque::query()->whereKey($cheque->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->statut !== Cheque::STATUT_DEPOSE) {
                throw ValidationException::withMessages([
                    'statut' => __('Only a cheque deposited at the bank can be accepted.'),
                ]);
            }

            $this->refuserAutoValidation($locked, $par);

            $locked->update([
                'statut' => Cheque::STATUT_ENCAISSE,
                'depot_valide_le' => now(),
                'depot_valide_par_id' => $par->id,
            ]);

            $this->journaliser($locked, Cheque::STATUT_DEPOSE, $par);

            return $locked;
        });
    }

    public function rejeter(Cheque $cheque, Employee $par): Cheque
    {
        return DB::transaction(function () use ($cheque, $par): Cheque {
            /** @var Cheque $locked */
            $locked = Cheque::query()->whereKey($cheque->getKey())->lockForUpdate()->firstOrFail();

            if (! self::rejetable($locked)) {
                throw ValidationException::withMessages([
                    'statut' => __('This status change is not allowed from the current status.'),
                ]);
            }

            if ($locked->statut === Cheque::STATUT_DEPOSE) {
                $this->refuserAutoValidation($locked, $par);
            }

            $ancien = $locked->statut;
            $locked->update(['statut' => Cheque::STATUT_REJETE]);

            $this->journaliser($locked, $ancien, $par);

            return $locked;
        });
    }

    public function annulerRejet(Cheque $cheque, Employee $par): Cheque
    {
        return DB::transaction(function () use ($cheque, $par): Cheque {
            /** @var Cheque $locked */
            $locked = Cheque::query()->whereKey($cheque->getKey())->lockForUpdate()->firstOrFail();

            $rembourse = Remboursement::query()
                ->nonAnnules()
                ->whereIn('encaissement_id', $locked->encaissements()->select('id'))
                ->exists();

            $blocage = self::blocageAnnulationRejet($locked, $rembourse);

            if ($blocage !== null) {
                throw ValidationException::withMessages(['statut' => $blocage]);
            }

            $locked->update([
                'statut' => Cheque::STATUT_DEPOSE,
                'depot_valide_le' => null,
                'depot_valide_par_id' => null,
            ]);

            $this->journaliser($locked, Cheque::STATUT_REJETE, $par);

            return $locked;
        });
    }

    /**
     * Why a rejection cannot be undone — null when it can. The ONE
     * definition: replayed under lock above, carried to the screen by
     * GetChequesList (`rejetAnnulable`).
     */
    public static function blocageAnnulationRejet(Cheque $cheque, bool $rembourse): ?string
    {
        if ($cheque->statut !== Cheque::STATUT_REJETE) {
            return __('This status change is not allowed from the current status.');
        }

        if ($cheque->retourne_le !== null) {
            return __('This cheque was already returned to its owner: the rejection can no longer be undone.');
        }

        if ($rembourse) {
            return __('A payment of this cheque was refunded after the rejection: cancel that refund first.');
        }

        return null;
    }

    /** « Déposé » or « Encaissé » → Rejeté (a bank can bounce a cheque late). Shared with GetChequesList. */
    public static function rejetable(Cheque $cheque): bool
    {
        return in_array($cheque->statut, [Cheque::STATUT_DEPOSE, Cheque::STATUT_ENCAISSE], true);
    }

    private function refuserAutoValidation(Cheque $cheque, Employee $par): void
    {
        if ($cheque->depose_par_id !== null && $cheque->depose_par_id === $par->id) {
            throw ValidationException::withMessages([
                'statut' => __('The employee who deposited the cheque cannot validate their own deposit.'),
            ]);
        }
    }

    private function journaliser(Cheque $cheque, string $ancien, Employee $par): void
    {
        activity('cheque')
            ->performedOn($cheque)
            ->event('cheque_statut')
            ->withProperties([
                'reference' => $cheque->reference,
                'statut_avant' => $ancien,
                'statut_apres' => $cheque->statut,
                'montant' => number_format((float) $cheque->montant, 2, '.', ''),
                'numero_cheque' => $cheque->numero_cheque,
                'banque' => $cheque->banque,
                'proprietaire' => $cheque->proprietaire_nom,
                'etudiant_id' => $cheque->student_id,
                'decide_par' => $par->nomComplet(),
            ])
            ->log("Chèque {$cheque->reference} : {$ancien} → {$cheque->statut}");
    }
}
