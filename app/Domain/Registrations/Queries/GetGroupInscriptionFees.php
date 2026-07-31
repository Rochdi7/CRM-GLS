<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Queries;

use App\Models\Frais;
use App\Models\Group;
use Illuminate\Support\Collection;

/**
 * "Frais disponibles" for a group — extracted verbatim from
 * InscriptionsIndex::loadGroupFees() (same ACTIVE-only catalog filter, same
 * per-group pivot montant/date_echeance, same today-fallback for a missing
 * due date). Powers the dedicated GET
 * backoffice/groups/{group}/inscription-fees endpoint used by the create
 * form when the group selection changes (docs/phase-9-inscriptions-
 * mapping.md — chosen over embedding every group's fees in the options
 * payload up front).
 */
final class GetGroupInscriptionFees
{
    /**
     * @return Collection<int, array{fraisId: int, nom: string, montantInitial: string, dateEcheance: string}>
     */
    public function __invoke(Group $group): Collection
    {
        $group->loadMissing(['frais' => fn ($q) => $q->where('statut', Frais::STATUT_ACTIF)]);

        return $group->frais->map(fn (Frais $frais): array => [
            'fraisId' => $frais->id,
            'nom' => $frais->nom,
            'montantInitial' => number_format((float) $frais->pivot->montant, 2, '.', ''),
            'dateEcheance' => $frais->pivot->date_echeance ?: now()->toDateString(),
        ])->values();
    }

    /**
     * The group's own training dates, for the create form's read-only
     * date_debut/date_fin display (auto-filled alongside the fee lines).
     *
     * @return array{dateDebut: ?string, dateFin: ?string}
     */
    public function trainingDates(Group $group): array
    {
        return [
            'dateDebut' => $group->date_debut_formation?->toDateString(),
            'dateFin' => $group->date_fin_formation?->toDateString(),
        ];
    }
}
