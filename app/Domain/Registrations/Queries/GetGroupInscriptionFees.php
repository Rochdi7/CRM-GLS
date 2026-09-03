<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Queries;

use App\Domain\Settings\Support\FraisEcheanceResolver;
use App\Models\Frais;
use App\Models\Group;
use Illuminate\Support\Collection;

/**
 * "Frais disponibles" for a group — extracted from
 * InscriptionsIndex::loadGroupFees() (same ACTIVE-only catalog filter, same
 * per-group pivot montant/date_echeance). Powers the dedicated GET
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
        $group->loadMissing([
            'frais' => fn ($q) => $q->where('statut', Frais::STATUT_ACTIF),
            'anneeScolaire',
        ]);

        return $group->frais->map(fn (Frais $frais): array => [
            'fraisId' => $frais->id,
            'nom' => $frais->nom,
            'montantInitial' => number_format((float) $frais->pivot->montant, 2, '.', ''),
            'dateEcheance' => $this->echeanceFor($group, $frais),
        ])->values();
    }

    /**
     * The due date the enrolment form pre-fills for one of the group's fees.
     *
     * The group's own échéance (`group_frais.date_echeance`, what the Groups
     * form saved) is the authority. When the group never stored one — a
     * group created before the month-derived default existed, or a row
     * re-attached without a date — a MONTHLY fee still gets the same date
     * the Groups form itself would derive (FraisEcheanceResolver: month
     * from the fee's name, day from the group's start date, year from the
     * group's school year), so the inscription always mirrors the group
     * rather than stamping « aujourd'hui » on « Frais d'Octobre ». Only a
     * fee with no month in its name (inscription, examen…) and no stored
     * date falls back to today: it is due at enrolment.
     */
    private function echeanceFor(Group $group, Frais $frais): string
    {
        $stored = $frais->pivot->date_echeance;

        if ($stored !== null && $stored !== '') {
            return (string) $stored;
        }

        return FraisEcheanceResolver::defaultFor(
            $frais->nom,
            $group->date_debut_formation?->toDateString(),
            $group->anneeScolaire?->date_debut?->toDateString(),
        ) ?? now()->toDateString();
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
