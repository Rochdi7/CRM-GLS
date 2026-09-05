<?php

declare(strict_types=1);

namespace App\Domain\Payments\Queries;

use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\Student;

/**
 * Le dossier financier COMPLET d'un étudiant, pour l'écran de réparation
 * super-admin : tous ses encaissements et toutes les lignes de frais de
 * TOUTES ses inscriptions, quel que soit leur statut et quelle que soit
 * leur année.
 *
 * ⚠ Aucun filtre de contexte, aucun filtre de statut — à l'inverse de
 * GetInscriptionPayments et des listes ordinaires. C'est volontaire : un
 * paiement mal classé se trouve presque toujours sur un dossier Annulée ou
 * dans une autre année, c'est-à-dire exactement ce que les autres écrans
 * cachent. Cette requête ne sert QUE la page de réparation, gardée par
 * payments.move-fee (super-admin).
 *
 * Les frais MASQUÉS sont listés et signalés plutôt que retirés : un
 * paiement peut être accroché à l'un d'eux (c'est même le bug typique), et
 * l'opérateur doit le voir pour l'en sortir — un read-model ne redérive pas
 * une règle métier, il la signale (CLAUDE.md).
 */
final class GetStudentPaymentPlacement
{
    /**
     * @return array{etudiant: array<string, mixed>, inscriptions: list<array<string, mixed>>, paiements: list<array<string, mixed>>}
     */
    public function __invoke(Student $student): array
    {
        $inscriptions = Inscription::query()
            ->with(['group:id,nom', 'anneeScolaire:id,nom', 'etablissement:id,nom_centre', 'fees'])
            ->where('student_id', $student->getKey())
            ->orderByDesc('id')
            ->get();

        // Un seul SUM groupé pour tous les frais de l'étudiant : appeler
        // InscriptionFee::montantPaye() par ligne relancerait une requête
        // par frais (CLAUDE.md § Performance rules).
        $payeParFrais = Encaissement::query()
            ->whereIn('inscription_fee_id', $inscriptions->pluck('fees')->flatten()->pluck('id'))
            ->selectRaw('inscription_fee_id, SUM(montant) AS total')
            ->groupBy('inscription_fee_id')
            ->pluck('total', 'inscription_fee_id');

        $paiements = Encaissement::query()
            ->with(['caisse:id,nom', 'agent:id,nom,prenom', 'fee:id,nom,inscription_id', 'fee.inscription:id,reference'])
            ->withExists('remboursements as est_rembourse')
            ->where('student_id', $student->getKey())
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->get();

        return [
            'etudiant' => [
                'id' => $student->getKey(),
                'reference' => $student->reference,
                'nom' => $student->nom,
                'prenom' => $student->prenom,
                'telephone' => $student->telephone,
                'centre' => $student->etablissement?->nom_centre,
            ],
            'inscriptions' => $inscriptions->map(fn (Inscription $i): array => [
                'id' => $i->id,
                'reference' => $i->reference,
                'statut' => $i->statut,
                'groupe' => $i->group?->nom,
                'annee' => $i->anneeScolaire?->nom,
                'centre' => $i->etablissement?->nom_centre,
                'frais' => $i->fees->map(function ($f) use ($payeParFrais): array {
                    $paye = (float) ($payeParFrais[$f->id] ?? 0);

                    return [
                        'id' => $f->id,
                        'nom' => $f->nom,
                        'montant' => (float) $f->montant,
                        'paye' => $paye,
                        'reste' => round((float) $f->montant - $paye, 2),
                        'statut' => $f->statut,
                        'masque' => $f->masque_le !== null,
                    ];
                })->values()->all(),
            ])->values()->all(),
            'paiements' => $paiements->map(fn (Encaissement $e): array => [
                'id' => $e->id,
                'reference' => $e->reference,
                'montant' => (float) $e->montant,
                'methode' => $e->methode,
                'datePaiement' => $e->date_paiement?->format('Y-m-d'),
                'caisse' => $e->caisse?->nom,
                'agent' => trim(($e->agent?->prenom ?? '').' '.($e->agent?->nom ?? '')) ?: null,
                'fraisId' => $e->inscription_fee_id,
                'frais' => $e->fee?->nom,
                'inscriptionReference' => $e->fee?->inscription?->reference,
                'estAvance' => $e->inscription_fee_id === null,
                'estRembourse' => (bool) $e->est_rembourse,
                'estApplication' => $e->applied_from_encaissement_id !== null,
            ])->values()->all(),
        ];
    }
}
