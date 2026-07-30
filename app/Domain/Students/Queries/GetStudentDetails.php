<?php

declare(strict_types=1);

namespace App\Domain\Students\Queries;

use App\Models\Student;

/**
 * Extracted from resources/views/backoffice/students/show.blade.php +
 * StudentController::show()'s eager loads — same fields, same relations,
 * same unpaginated related-record lists (preserved exactly, not newly
 * paginated). Read-only, no request/React dependency.
 */
final class GetStudentDetails
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(Student $student): array
    {
        $student->loadMissing([
            'etablissement',
            'inscriptions.group',
            'inscriptions.fees',
            'encaissements.caisse',
        ]);

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
            'photoUrl' => $student->getFirstMediaUrl('photo') ?: null,
            'parent' => ($student->parent_nom || $student->parent_telephone || $student->parent_relation || $student->parent_cin)
                ? [
                    'relation' => $student->parent_relation,
                    'nom' => $student->parent_nom,
                    'sexe' => $student->parent_sexe,
                    'cin' => $student->parent_cin,
                    'telephone' => $student->parent_telephone,
                ]
                : null,
            'inscriptions' => $student->inscriptions->map(fn ($inscription): array => [
                'reference' => $inscription->reference,
                'groupe' => $inscription->group?->nom,
                'date' => $inscription->date_inscription?->format('d/m/Y'),
                'total' => $inscription->montant_total !== null ? number_format((float) $inscription->montant_total, 2, '.', '') : null,
                'statut' => $inscription->statut,
            ])->values()->all(),
            'paiementsTotal' => number_format((float) $student->encaissements->sum('montant'), 2, '.', ''),
            'paiements' => $student->encaissements->map(fn ($encaissement): array => [
                'reference' => $encaissement->reference,
                'montant' => number_format((float) $encaissement->montant, 2, '.', ''),
                'methode' => $encaissement->methode,
                'date' => $encaissement->date_paiement?->format('d/m/Y'),
                'caisse' => $encaissement->caisse?->nom,
            ])->values()->all(),
        ];
    }
}
