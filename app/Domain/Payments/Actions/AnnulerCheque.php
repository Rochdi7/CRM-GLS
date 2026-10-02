<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Cheque;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Annule un chèque (02/10/2026, `cheques.cancel` — comptable + super-admin) :
 * saisi par erreur, remplacé, illisible… Le chèque passe « Annulé » et reste
 * LISTÉ, barré, avec son motif — jamais supprimé (la suppression reste un
 * geste super-admin, SupprimerCheque).
 *
 * ⚠ AUCUN argent ne bouge, par construction : un chèque qui a financé le
 * moindre paiement est REFUSÉ. Celui-là ne s'annule pas, il se REJETTE
 * (ValiderRemiseCheque) et l'argent se règle par un remboursement.
 *
 * Bornes, relues SOUS VERROU (§11) — blocage() en est la SEULE définition,
 * portée à l'écran par GetChequesList (`annulationBlocker`) :
 *  1. pas déjà annulé ;
 *  2. aucun paiement financé ;
 *  3. pas restitué — le papier est reparti, le dossier est clos ;
 *  4. motif OBLIGATOIRE — ajouté à la note (jamais écrasée) et au journal.
 *
 * Un chèque « Annulé » ne paie plus, ne va plus à la banque et ne se
 * restitue plus : chacun de ces gestes exige « En possession ».
 */
final class AnnulerCheque
{
    public static function blocage(Cheque $cheque, float $montantUtilise): ?string
    {
        if ($cheque->statut === Cheque::STATUT_ANNULE) {
            return __('This cheque is already cancelled.');
        }

        if ($montantUtilise > 0.0) {
            return __('This cheque already paid for payments: reject it instead of cancelling it.');
        }

        if ($cheque->retourne_le !== null) {
            return __('This cheque was returned to its owner: nothing left to cancel.');
        }

        return null;
    }

    public function handle(Cheque $cheque, string $motif, Employee $par): Cheque
    {
        $motif = trim($motif);

        if ($motif === '') {
            throw ValidationException::withMessages([
                'motif' => __('A reason is required to cancel a cheque.'),
            ]);
        }

        return DB::transaction(function () use ($cheque, $motif, $par): Cheque {
            /** @var Cheque $locked */
            $locked = Cheque::query()->whereKey($cheque->getKey())->lockForUpdate()->firstOrFail();

            $blocage = self::blocage($locked, $locked->montantUtilise());

            if ($blocage !== null) {
                throw ValidationException::withMessages(['motif' => $blocage]);
            }

            $ancien = $locked->statut;
            $note = trim((string) $locked->note);
            $suffixe = '[ANNULÉ] le '.now()->format('d/m/Y').' par '.$par->nomComplet().' - '.rtrim($motif, '.').'.';

            $locked->update([
                'statut' => Cheque::STATUT_ANNULE,
                'note' => $note === '' ? $suffixe : $note."\n".$suffixe,
            ]);

            activity('cheque')
                ->performedOn($locked)
                ->event('cheque_annule')
                ->withProperties([
                    'reference' => $locked->reference,
                    'statut_avant' => $ancien,
                    'statut_apres' => Cheque::STATUT_ANNULE,
                    'montant' => number_format((float) $locked->montant, 2, '.', ''),
                    'numero_cheque' => $locked->numero_cheque,
                    'banque' => $locked->banque,
                    'proprietaire' => $locked->proprietaireLabel(),
                    'etudiant_id' => $locked->student_id,
                    'motif' => $motif,
                    'annule_par' => $par->nomComplet(),
                ])
                ->log("Chèque {$locked->reference} annulé");

            return $locked;
        });
    }
}
