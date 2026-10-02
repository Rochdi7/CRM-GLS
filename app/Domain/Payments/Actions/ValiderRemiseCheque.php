<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Cheque;
use App\Models\Employee;
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
