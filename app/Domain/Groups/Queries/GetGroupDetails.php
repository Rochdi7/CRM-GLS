<?php

declare(strict_types=1);

namespace App\Domain\Groups\Queries;

use App\Models\Group;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;

/**
 * Extracted from resources/views/backoffice/groups/show.blade.php +
 * GroupController::show()'s eager loads. Read-only — the "archive" action
 * itself is NOT part of this class; canArchive() only reports whether the
 * (unconverted) archive form should render, mirroring the Blade's own
 * `@can('archive', $group)` + status check.
 */
final class GetGroupDetails
{
    public function __construct(private readonly CenterAccessService $centerAccess) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Group $group, User $user): array
    {
        $group->loadMissing(['enseignant', 'salle', 'etablissement', 'anneeScolaire', 'frais', 'inscriptions.student']);

        $isFinished = $group->statut === Group::STATUT_FIN_FORMATION;

        return [
            'id' => $group->id,
            'nom' => $group->nom,
            'niveau' => $group->niveau,
            'enseignant' => $group->enseignant?->nomComplet(),
            'centre' => $group->etablissement?->nom_centre,
            'anneeScolaire' => $group->anneeScolaire?->nom,
            'dateDebutFormation' => $group->date_debut_formation?->format('d/m/Y'),
            'dateFinFormation' => $group->date_fin_formation?->format('d/m/Y'),
            'statut' => $group->statut,
            'isFinished' => $isFinished,
            'canArchive' => ! $isFinished && $user->can('groups.archive') && $this->centerAccess->canAccessCenter($user, $group->etablissement_id),
            'archiveUrl' => route('backoffice.groups.archive', $group),
            'fees' => $group->frais->map(fn ($fee): array => [
                'nom' => $fee->nom,
                'classification' => $fee->pivot->classification,
                'montant' => number_format((float) $fee->pivot->montant, 2, '.', ''),
            ])->values()->all(),
            'inscriptions' => $group->inscriptions->map(fn ($inscription): array => [
                'reference' => $inscription->reference,
                'student' => $inscription->student?->nomComplet(),
                'studentShowUrl' => $inscription->student ? route('backoffice.students.show', $inscription->student) : null,
                'date' => $inscription->date_inscription?->format('d/m/Y'),
                'statut' => $inscription->statut,
            ])->values()->all(),
        ];
    }
}
