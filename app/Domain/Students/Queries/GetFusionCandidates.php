<?php

declare(strict_types=1);

namespace App\Domain\Students\Queries;

use App\Domain\Students\Actions\FusionnerEtudiants;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Recherche des fiches étudiant pour l'écran de fusion (super-admin).
 *
 * ⚠ Délibérément NON scopée au centre actif ni à l'année : un doublon naît
 * très souvent parce que la personne a été ressaisie dans un AUTRE centre,
 * et c'est exactement la paire qu'il faut pouvoir rapprocher. Les écrans
 * ordinaires (GetStudentsList) gardent leur scoping — celui-ci est un outil
 * de réparation réservé au super-admin, qui voit tout le réseau de toute
 * façon (CLAUDE.md §11, « Deliberate exceptions »).
 *
 * Les fiches déjà fusionnées (suffixe « (doublon fusionné) ») sont exclues
 * de la recherche : elles sont vides et ne doivent plus être proposées.
 */
final class GetFusionCandidates
{
    private const int LIMITE = 25;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function __invoke(string $search): Collection
    {
        $search = trim($search);

        if (mb_strlen($search) < 2) {
            return collect();
        }

        return Student::query()
            ->with('etablissement:id,nom_centre')
            ->withCount(['inscriptions', 'encaissements'])
            ->where('nom', 'not ilike', '%'.FusionnerEtudiants::SUFFIXE_DOUBLON)
            ->where(function ($q) use ($search): void {
                // ILIKE : PostgreSQL LIKE est sensible à la casse (CLAUDE.md §17).
                $q->where('nom', 'ilike', "%{$search}%")
                    ->orWhere('prenom', 'ilike', "%{$search}%")
                    ->orWhere('reference', 'ilike', "%{$search}%")
                    ->orWhere('legacy_ref', 'ilike', "%{$search}%")
                    ->orWhere('telephone', 'ilike', "%{$search}%");
            })
            ->orderBy('nom')
            ->orderBy('prenom')
            ->limit(self::LIMITE)
            ->get()
            ->map(fn (Student $s): array => [
                'id' => $s->id,
                'reference' => $s->reference,
                'legacyRef' => $s->legacy_ref,
                'nom' => $s->nom,
                'prenom' => $s->prenom,
                'telephone' => $s->telephone,
                'dateNaissance' => $s->date_naissance?->format('d/m/Y'),
                'centre' => $s->etablissement?->nom_centre,
                'inscriptionsCount' => $s->inscriptions_count,
                'encaissementsCount' => $s->encaissements_count,
            ]);
    }
}
