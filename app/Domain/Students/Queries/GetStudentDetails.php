<?php

declare(strict_types=1);

namespace App\Domain\Students\Queries;

use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Extracted from resources/views/backoffice/students/show.blade.php +
 * StudentController::show()'s eager loads — same fields, same relations,
 * same unpaginated related-record lists (preserved exactly, not newly
 * paginated). Read-only, no request/React dependency.
 *
 * Two presentation rules (24/08/2026, after the legacy import brought
 * several years of history per student):
 *  - inscriptions are grouped by année scolaire (newest year first);
 *  - the Paiements list shows the payments of ONE inscription statut only:
 *    the Active inscription(s) when the student has one, otherwise the
 *    Annulée one(s), otherwise Changement — the priority the Encaissements
 *    import also uses. Unallocated avances (no fee) always show.
 */
final class GetStudentDetails
{
    /** Statut priority for the Paiements list. */
    private const array PAIEMENTS_PRIORITE = [
        Inscription::STATUT_ACTIVE,
        Inscription::STATUT_ANNULEE,
        Inscription::STATUT_CHANGEMENT,
    ];

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Student $student): array
    {
        $student->loadMissing([
            'etablissement',
            'inscriptions.group',
            'inscriptions.fees',
            'inscriptions.anneeScolaire',
            'encaissements.caisse',
            'encaissements.fee',
        ]);

        $inscriptionRows = $student->inscriptions->map(fn (Inscription $inscription): array => [
            'reference' => $inscription->reference,
            'groupe' => $inscription->group?->nom,
            'date' => $inscription->date_inscription?->format('d/m/Y'),
            'total' => $inscription->montant_total !== null ? number_format((float) $inscription->montant_total, 2, '.', '') : null,
            'statut' => $inscription->statut,
            'anneeScolaire' => $inscription->anneeScolaire?->nom,
        ]);

        [$paiementsScope, $paiements] = $this->paiementsAffiches($student);

        return [
            'id' => $student->id,
            'reference' => $student->reference,
            'nomComplet' => $student->nomComplet(),
            'prenom' => $student->prenom,
            'niveau' => $student->niveau,
            'orientation' => $student->niveau ? $student->orientation() : null,
            'sexe' => $student->sexe,
            'dateNaissance' => $student->date_naissance?->format('d/m/Y'),
            'cin' => $student->cin,
            'telephone' => $student->telephone,
            'whatsapp' => $student->whatsapp,
            'email' => $student->email,
            'adresse' => $student->adresse,
            'centre' => $student->etablissement?->nom_centre,
            'photoUrl' => $student->avatarUrl(),
            'parent' => ($student->parent_nom || $student->parent_telephone || $student->parent_relation || $student->parent_cin)
                ? [
                    'relation' => $student->parent_relation,
                    'nom' => $student->parent_nom,
                    'sexe' => $student->parent_sexe,
                    'cin' => $student->parent_cin,
                    'telephone' => $student->parent_telephone,
                ]
                : null,
            'inscriptions' => $inscriptionRows->values()->all(),
            'inscriptionsParAnnee' => $this->groupByAnnee($student->inscriptions, $inscriptionRows),
            'paiementsScope' => $paiementsScope,
            'paiementsTotal' => number_format((float) $paiements->sum('montant'), 2, '.', ''),
            'paiements' => $paiements->map(fn (Encaissement $encaissement): array => [
                'reference' => $encaissement->reference,
                'montant' => number_format((float) $encaissement->montant, 2, '.', ''),
                'methode' => $encaissement->methode,
                'date' => $encaissement->date_paiement?->format('d/m/Y'),
                'caisse' => $encaissement->caisse?->nom,
            ])->values()->all(),
        ];
    }

    /**
     * One group per année scolaire, newest year first; inscriptions with no
     * year (legacy edge case) come last under a null label.
     *
     * @param  Collection<int, Inscription>  $inscriptions
     * @param  Collection<int, array<string, mixed>>  $rows  same order as $inscriptions
     * @return list<array{annee: ?string, inscriptions: list<array<string, mixed>>}>
     */
    private function groupByAnnee(Collection $inscriptions, Collection $rows): array
    {
        $groups = [];

        foreach ($inscriptions as $index => $inscription) {
            $annee = $inscription->anneeScolaire;
            $key = $annee?->id ?? 0;

            $groups[$key] ??= [
                'annee' => $annee?->nom,
                'dateDebut' => $annee?->date_debut?->toDateString() ?? '',
                'inscriptions' => [],
            ];
            $groups[$key]['inscriptions'][] = $rows[$index];
        }

        usort($groups, fn (array $a, array $b): int => strcmp($b['dateDebut'], $a['dateDebut']));

        return array_map(fn (array $g): array => [
            'annee' => $g['annee'],
            'inscriptions' => $g['inscriptions'],
        ], $groups);
    }

    /**
     * Payments of the first statut (Active > Annulée > Changement) the
     * student actually holds, plus unallocated avances. With no inscription
     * at all, every payment shows.
     *
     * @return array{?string, Collection<int, Encaissement>}
     */
    private function paiementsAffiches(Student $student): array
    {
        foreach (self::PAIEMENTS_PRIORITE as $statut) {
            $ids = $student->inscriptions->where('statut', $statut)->pluck('id');

            if ($ids->isEmpty()) {
                continue;
            }

            return [$statut, $student->encaissements->filter(
                fn (Encaissement $e): bool => $e->fee === null || $ids->contains($e->fee->inscription_id),
            )->values()];
        }

        return [null, $student->encaissements->values()];
    }
}
