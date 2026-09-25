<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Queries;

use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Onglet « Absences » de la fiche étudiant et de la fiche inscription
 * (25/09/2026) : les absences de CET étudiant seulement.
 *
 * Deux portées, une seule requête :
 *  - pour l'ÉTUDIANT : ses appels dans les séances du CENTRE de sa fiche,
 *    tous groupes de ce centre confondus. ⚠ Borné au centre (25/09/2026) :
 *    après un transfert, les appels du centre de départ ne doivent JAMAIS
 *    entrer dans les compteurs du centre d'arrivée (fausse donnée). Ils
 *    restent sur la fiche d'origine et ne se lisent, côté arrivée, que dans
 *    la section « Historique » (GetStudentDetails::historiqueTransfert),
 *    étiquetée au nom de l'ancien centre ;
 *  - pour une INSCRIPTION : ses appels dans les séances du groupe de ce
 *    dossier. Il n'existe aucune FK `presences → inscriptions` ; la liaison
 *    est (student × séances du groupe), exactement celle de
 *    `GardePresencesInscription`. Un dossier détaché de son groupe
 *    (`group_id` NULL) n'a donc aucun appel lisible.
 *
 * Une « absence » = `Absent` ou `Justifié` (absence excusée). Les compteurs
 * portent sur TOUS les statuts, pour situer ces absences dans le total des
 * appels. Une requête pour toute la liste, jamais une par ligne.
 */
final class GetAbsencesEtudiant
{
    public const STATUTS_ABSENCE = [Presence::STATUT_ABSENT, Presence::STATUT_JUSTIFIE];

    /**
     * @return array{total: int, compteurs: array<string, int>, absences: list<array<string, mixed>>}
     */
    public function pourEtudiant(Student $student): array
    {
        return $this->lire($student->id, null, $student->etablissement_id === null ? null : (int) $student->etablissement_id);
    }

    /**
     * @return array{total: int, compteurs: array<string, int>, absences: list<array<string, mixed>>}
     */
    public function pourInscription(Inscription $inscription): array
    {
        if ($inscription->group_id === null) {
            return $this->vide();
        }

        return $this->lire($inscription->student_id, (int) $inscription->group_id);
    }

    /**
     * @return array{total: int, compteurs: array<string, int>, absences: list<array<string, mixed>>}
     */
    private function lire(int $studentId, ?int $groupId, ?int $centreId = null): array
    {
        $rows = DB::table('presences as p')
            ->join('seances as se', 'se.id', '=', 'p.seance_id')
            ->leftJoin('groups as g', 'g.id', '=', 'se.group_id')
            ->leftJoin('employees as e', 'e.id', '=', 'se.enseignant_id')
            ->where('p.student_id', $studentId)
            ->when($groupId !== null, fn ($q) => $q->where('se.group_id', $groupId))
            // Séance sans centre (donnée ancienne) : rattachée par son groupe.
            ->when($centreId !== null, fn ($q) => $q->where(fn ($w) => $w
                ->where('se.etablissement_id', $centreId)
                ->orWhere(fn ($n) => $n->whereNull('se.etablissement_id')->where('g.etablissement_id', $centreId))))
            ->orderByDesc('se.date_seance')
            ->orderByDesc('se.heure_debut')
            ->get([
                'p.id', 'p.statut', 'p.note',
                'se.id as seance_id', 'se.date_seance', 'se.heure_debut', 'se.heure_fin',
                'g.nom as groupe', 'e.nom as enseignant_nom', 'e.prenom as enseignant_prenom',
            ]);

        $compteurs = Presence::compteurs($rows);

        return [
            'total' => $rows->count(),
            'compteurs' => $compteurs,
            'absences' => $rows
                ->whereIn('statut', self::STATUTS_ABSENCE)
                ->values()
                ->map(fn ($r): array => [
                    'id' => (int) $r->id,
                    'seanceId' => (int) $r->seance_id,
                    'date' => Carbon::parse($r->date_seance)->format('d/m/Y'),
                    'heure' => $r->heure_debut !== null
                        ? substr((string) $r->heure_debut, 0, 5).($r->heure_fin !== null ? ' - '.substr((string) $r->heure_fin, 0, 5) : '')
                        : null,
                    'groupe' => $r->groupe,
                    'enseignant' => trim(($r->enseignant_prenom ?? '').' '.($r->enseignant_nom ?? '')) ?: null,
                    'statut' => (string) $r->statut,
                    'note' => $r->note,
                ])
                ->all(),
        ];
    }

    /**
     * @return array{total: int, compteurs: array<string, int>, absences: list<array<string, mixed>>}
     */
    private function vide(): array
    {
        return [
            'total' => 0,
            'compteurs' => Presence::compteurs([]),
            'absences' => [],
        ];
    }
}
