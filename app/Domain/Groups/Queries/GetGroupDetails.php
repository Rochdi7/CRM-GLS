<?php

declare(strict_types=1);

namespace App\Domain\Groups\Queries;

use App\Models\Group;
use App\Models\Inscription;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use Illuminate\Support\Carbon;

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
        $group->loadMissing([
            'enseignant', 'salle', 'etablissement', 'anneeScolaire', 'frais',
            'inscriptions.student', 'enseignants.enseignant',
        ]);

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
            'statutLabel' => $this->statutLabel($group->statut),
            'isFinished' => $isFinished,
            'etudiantsDistinctsCount' => $group->inscriptions->pluck('student_id')->unique()->count(),
            'inscriptionsActivesCount' => $group->inscriptions->where('statut', Inscription::STATUT_ACTIVE)->count(),
            'inscriptionsChangementCount' => $group->inscriptions->where('statut', Inscription::STATUT_CHANGEMENT)->count(),
            'inscriptionsAnnuleesCount' => $group->inscriptions->where('statut', Inscription::STATUT_ANNULEE)->count(),
            'canChangeEnseignant' => $user->can('update', $group) && ! $isFinished,
            'changerEnseignantUrl' => route('backoffice.groups.changer-enseignant', $group),
            'emploiDuTempsUrl' => route('backoffice.emploi-du-temps.index', ['group' => $group->id]),
            // Full assignment history — one row per teaching period, the
            // Actif one first. This is what makes "who taught this group,
            // from when to when" answerable for payroll.
            'enseignantsHistorique' => $group->enseignants->map(fn ($a): array => [
                'id' => $a->id,
                'enseignant' => $a->enseignant?->nomComplet(),
                'dateDebut' => $a->date_debut?->format('d/m/Y'),
                'dateFin' => $a->date_fin?->format('d/m/Y'),
                // Raw ISO values too: the d/m/Y strings above are for
                // display, but the "Modifier" modal needs <input type=date>
                // values (Y-m-d) to prefill the period being corrected.
                'dateDebutIso' => $a->date_debut?->format('Y-m-d'),
                'dateFinIso' => $a->date_fin?->format('Y-m-d'),
                'statut' => $a->statut,
                'isActif' => $a->isActif(),
                'motif' => $a->motif,
                'updateUrl' => route('backoffice.groups.affectations.update', [$group, $a]),
            ])->values()->all(),
            'canArchive' => ! $isFinished && $user->can('groups.archive') && $this->centerAccess->canAccessCenter($user, $group->etablissement_id),
            'archiveUrl' => route('backoffice.groups.archive', $group),
            'anneeScolaireId' => $group->annee_scolaire_id,
            'canMoveYear' => $user->can('groups.move-year') && $this->centerAccess->canAccessCenter($user, $group->etablissement_id),
            'moveYearUrl' => route('backoffice.groups.move-year', $group),
            'fees' => $group->frais->map(fn ($fee): array => [
                'nom' => $fee->nom,
                'classification' => $fee->pivot->classification,
                'montant' => number_format((float) $fee->pivot->montant, 2, '.', ''),
                'dateEcheance' => $fee->pivot->date_echeance ? Carbon::parse($fee->pivot->date_echeance)->format('d/m/Y') : null,
            ])->values()->all(),
            'inscriptions' => $group->inscriptions->map(fn ($inscription): array => [
                'reference' => $inscription->reference,
                'student' => $inscription->student?->nomComplet(),
                'studentShowUrl' => $inscription->student ? route('backoffice.students.show', $inscription->student) : null,
                'date' => $inscription->date_inscription?->format('d/m/Y'),
                'dateDebut' => $inscription->date_debut?->format('d/m/Y'),
                'dateFin' => $inscription->date_fin?->format('d/m/Y'),
                'statut' => $inscription->statut,
            ])->values()->all(),
        ];
    }

    /** Header banner title, driven by the group's own statut. */
    private function statutLabel(string $statut): string
    {
        return match ($statut) {
            Group::STATUT_EN_INSCRIPTION => 'En inscription',
            Group::STATUT_EN_FORMATION => 'Formation en cours',
            Group::STATUT_FIN_FORMATION => 'Formation terminée',
            Group::STATUT_ANNULEE => 'Formation annulée',
            default => $statut,
        };
    }
}
