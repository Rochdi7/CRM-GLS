<?php

declare(strict_types=1);

namespace App\Domain\Students\Actions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Domain\Students\Support\GardeEtudiantTransfere;
use App\Models\Employee;
use App\Models\Group;
use App\Models\Student;
use App\Models\StudentTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * DEMANDE de transfert d'un étudiant vers un autre centre (25/09/2026).
 *
 * Rien ne bouge ici : ni la fiche, ni un dossier, ni un dirham. La demande
 * enregistre QUI part, VERS OÙ, dans QUEL GROUPE (obligatoire — un
 * transfert sans affectation laisserait l'étudiant sans dossier à
 * l'arrivée) et POURQUOI. La décision appartient au super-admin
 * (ValiderTransfertEtudiant / RefuserTransfertEtudiant).
 *
 * Toutes les vérifications tournent sous verrou dans une transaction : deux
 * demandes simultanées pour la même fiche (double-clic, deux onglets) se
 * sérialisent et la seconde voit la première « En attente ».
 */
final class DemanderTransfertEtudiant
{
    public function handle(Student $student, int $etablissementCibleId, int $groupCibleId, string $motif, Employee $requestedBy): StudentTransfer
    {
        return DB::transaction(function () use ($student, $etablissementCibleId, $groupCibleId, $motif, $requestedBy): StudentTransfer {
            $student = Student::query()->whereKey($student->getKey())->lockForUpdate()->firstOrFail();

            GardeEtudiantTransfere::assertNonTransfere($student);

            if ($student->etablissement_id === null) {
                throw ValidationException::withMessages([
                    'student_id' => __('This student has no center and cannot be transferred.'),
                ]);
            }

            if ((int) $student->etablissement_id === $etablissementCibleId) {
                throw ValidationException::withMessages([
                    'etablissement_cible_id' => __("The target center must be different from the student's current center."),
                ]);
            }

            self::assertGroupeCible(Group::query()->find($groupCibleId), $etablissementCibleId);

            if (StudentTransfer::query()
                ->where('student_id', $student->id)
                ->where('statut', StudentTransfer::STATUT_EN_ATTENTE)
                ->exists()) {
                throw ValidationException::withMessages([
                    'student_id' => __('A transfer request is already pending for this student.'),
                ]);
            }

            return StudentTransfer::create([
                'reference' => ReferenceGenerator::make('TRE', 'student_transfers'),
                'student_id' => $student->id,
                'etablissement_source_id' => $student->etablissement_id,
                'etablissement_cible_id' => $etablissementCibleId,
                'group_cible_id' => $groupCibleId,
                'statut' => StudentTransfer::STATUT_EN_ATTENTE,
                'motif' => trim($motif),
                'requested_by' => $requestedBy->id,
            ]);
        });
    }

    /**
     * Le groupe d'affectation doit appartenir au centre CIBLE et être encore
     * ouvert. Partagé avec la validation, qui rejoue la règle : le groupe a
     * pu être clôturé ou supprimé entre la demande et la décision.
     */
    public static function assertGroupeCible(?Group $groupe, int $etablissementCibleId, string $field = 'group_cible_id'): Group
    {
        if ($groupe === null) {
            throw ValidationException::withMessages([
                $field => __('The target group no longer exists.'),
            ]);
        }

        if ((int) $groupe->etablissement_id !== $etablissementCibleId) {
            throw ValidationException::withMessages([
                $field => __('This group does not belong to the target center.'),
            ]);
        }

        if (in_array($groupe->statut, Group::STATUTS_HISTORIQUE, true)) {
            throw ValidationException::withMessages([
                $field => __('This group is closed and cannot receive a transferred student.'),
            ]);
        }

        return $groupe;
    }
}
