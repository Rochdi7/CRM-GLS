<?php

declare(strict_types=1);

namespace App\Domain\Groups\Queries;

use App\Models\Group;
use App\Models\Inscription;
use Illuminate\Support\Collection;

/**
 * Drills into one of the Groups list's Étudiants/Statistique badges (distinct
 * students / active / changement / annulée) — same counts GetGroupsList
 * computes via withCount, now returning the actual student rows behind
 * whichever badge was clicked.
 */
final class GetGroupStudentsBySegment
{
    public const SEGMENT_TOTAL = 'total';

    public const SEGMENT_ACTIVE = 'active';

    public const SEGMENT_ANNULEE = 'annulee';

    public const SEGMENT_CHANGEMENT = 'changement';

    public const SEGMENT_ETUDIANTS = 'etudiants';

    public const SEGMENTS = [
        self::SEGMENT_TOTAL,
        self::SEGMENT_ACTIVE,
        self::SEGMENT_ANNULEE,
        self::SEGMENT_CHANGEMENT,
        self::SEGMENT_ETUDIANTS,
    ];

    /**
     * @return Collection<int, array{
     *     reference: string, prenom: string, nom: string, cin: ?string,
     *     telephone: ?string, dateNaissance: ?string, niveauScolaire: ?string,
     *     dateInscription: ?string,
     * }>
     */
    public function __invoke(Group $group, string $segment): Collection
    {
        $query = Inscription::query()
            ->with('student')
            ->where('group_id', $group->id);

        match ($segment) {
            self::SEGMENT_ACTIVE => $query->where('statut', Inscription::STATUT_ACTIVE),
            self::SEGMENT_ANNULEE => $query->where('statut', Inscription::STATUT_ANNULEE),
            self::SEGMENT_CHANGEMENT => $query->where('statut', Inscription::STATUT_CHANGEMENT),
            // "total" and "étudiants" both start from every inscription in
            // the group; étudiants additionally dedupes by student below.
            default => null,
        };

        $inscriptions = $query->latest('date_inscription')->get();

        if ($segment === self::SEGMENT_ETUDIANTS) {
            $inscriptions = $inscriptions->unique('student_id');
        }

        return $inscriptions
            ->filter(fn (Inscription $inscription): bool => $inscription->student !== null)
            ->map(fn (Inscription $inscription): array => [
                'reference' => $inscription->student->reference,
                'prenom' => $inscription->student->prenom,
                'nom' => $inscription->student->nom,
                'cin' => $inscription->student->cin,
                'telephone' => $inscription->student->telephone,
                'dateNaissance' => $inscription->student->date_naissance?->format('d/m/Y'),
                'niveauScolaire' => $inscription->student->niveau,
                'dateInscription' => $inscription->date_inscription?->format('d/m/Y'),
            ])
            ->values();
    }
}
