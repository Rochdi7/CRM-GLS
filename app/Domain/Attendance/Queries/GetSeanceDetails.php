<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Queries;

use App\Models\Inscription;
use App\Models\Seance;

/**
 * Read-model for the fiche de présence (Seances/Show): the séance header +
 * one line per actively-enrolled student of its group, pre-filled with any
 * roll call already saved.
 */
final class GetSeanceDetails
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(Seance $seance): array
    {
        $seance->load(['group:id,nom,niveau', 'enseignant:id,nom,prenom']);

        $existing = $seance->presences()
            ->get()
            ->keyBy('student_id');

        $students = Inscription::query()
            ->with(['student:id,reference,nom,prenom,sexe', 'student.media'])
            ->where('group_id', $seance->group_id)
            ->where('statut', Inscription::STATUT_ACTIVE)
            ->get()
            ->filter(fn (Inscription $inscription): bool => $inscription->student !== null)
            ->unique('student_id')
            ->sortBy(fn (Inscription $i): string => mb_strtolower("{$i->student->nom} {$i->student->prenom}"))
            ->values()
            ->map(fn (Inscription $inscription): array => [
                'id' => $inscription->student->id,
                'reference' => $inscription->student->reference,
                'nom' => $inscription->student->nom,
                'prenom' => $inscription->student->prenom,
                'photoUrl' => $inscription->student->avatarUrl(),
                'statut' => $existing->get($inscription->student->id)?->statut,
                'note' => $existing->get($inscription->student->id)?->note ?? '',
            ]);

        return [
            'id' => $seance->id,
            'dateSeance' => $seance->date_seance->toDateString(),
            'heureDebut' => $seance->heure_debut ? substr($seance->heure_debut, 0, 5) : null,
            'heureFin' => $seance->heure_fin ? substr($seance->heure_fin, 0, 5) : null,
            'groupNom' => $seance->group?->nom,
            'groupNiveau' => $seance->group?->niveau,
            'enseignant' => $seance->enseignant?->nomComplet(),
            'enseignantId' => $seance->enseignant_id,
            'statut' => $seance->statut,
            'note' => $seance->note,
            'motifAnnulation' => $seance->motif_annulation,
            'students' => $students,
        ];
    }
}
