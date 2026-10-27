<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Queries;

use App\Models\User;

/**
 * Read-model for the « Statistiques » tab of Gestion des séances (27/10/2026):
 * two charts of ONE group over a date window, optionally narrowed to one of
 * its students —
 *
 *  - « Statistiques de présence » : présences / absences per séance DATE;
 *  - « Suivi de la présence »     : présences / absences per STUDENT.
 *
 * Both are DERIVED from the « Absence par groupe » matrix
 * (GetAbsencesParGroupe) rather than re-querying: the group scoping (centre
 * reach, active centre + année, portée enseignant), the student list and the
 * Présent/Absent split are that query's, so the chart can never count a
 * séance or a student the matrix does not show, nor disagree with its totals.
 *
 * Two series only — « Retard » was removed from the system (25/09/2026): the
 * legacy rows that still carry it count as a présence, exactly like the
 * matrix's « P » cell; « Justifié » counts as an absence, like its « A ».
 */
final class GetStatistiquesPresence
{
    public function __construct(
        private readonly GetAbsencesParGroupe $getAbsencesParGroupe,
    ) {}

    /**
     * @param  array{groupFilter: string, studentFilter: string, dateFrom: string, dateTo: string}  $filters
     * @return array{
     *     etudiants: list<array{value: string, label: string}>,
     *     studentFilter: string,
     *     parDate: list<array{date: string, presents: int, absents: int}>,
     *     parEtudiant: list<array{id: int, nom: string, actif: bool, presents: int, absents: int}>,
     *     totals: array{seances: int, presents: int, absents: int}
     * }
     */
    public function __invoke(User $user, array $filters): array
    {
        $matrice = ($this->getAbsencesParGroupe)($user, [
            'groupFilter' => $filters['groupFilter'],
            'dateFrom' => $filters['dateFrom'],
            'dateTo' => $filters['dateTo'],
            'statutFilter' => '',
        ]);

        $etudiants = array_map(fn (array $student): array => [
            'value' => (string) $student['id'],
            'label' => trim($student['nom'].' '.$student['prenom']),
        ], $matrice['students']);

        // A student id that is not on this group's roll (stale URL, group
        // changed) is dropped rather than charting an empty set.
        $studentFilter = in_array($filters['studentFilter'], array_column($etudiants, 'value'), true)
            ? $filters['studentFilter']
            : '';

        $students = $studentFilter === ''
            ? $matrice['students']
            : array_values(array_filter(
                $matrice['students'],
                fn (array $student): bool => (string) $student['id'] === $studentFilter,
            ));

        $parDate = $this->parDate($matrice['seances'], $students);

        return [
            'etudiants' => $etudiants,
            'studentFilter' => $studentFilter,
            'parDate' => $parDate,
            'parEtudiant' => array_map(fn (array $student): array => [
                'id' => $student['id'],
                'nom' => trim($student['nom'].' '.$student['prenom']),
                'actif' => $student['actif'],
                'presents' => $student['presents'],
                'absents' => $student['absents'],
            ], $students),
            'totals' => [
                'seances' => $this->seancesComptees($matrice['seances'], $students),
                'presents' => array_sum(array_column($students, 'presents')),
                'absents' => array_sum(array_column($students, 'absents')),
            ],
        ];
    }

    /**
     * One point per DATE carrying at least one mark of the charted students —
     * a séance nobody pointed is left out instead of plotted as « 0 présent »,
     * which would read as an empty class. Two séances on the same day add up.
     *
     * @param  list<array<string, mixed>>  $seances
     * @param  list<array<string, mixed>>  $students
     * @return list<array{date: string, presents: int, absents: int}>
     */
    private function parDate(array $seances, array $students): array
    {
        $parDate = [];

        foreach ($seances as $seance) {
            $presents = 0;
            $absents = 0;

            foreach ($students as $student) {
                $cell = ((array) $student['cells'])[(string) $seance['id']] ?? null;

                if ($cell === null) {
                    continue;
                }

                $cell['lettre'] === GetAbsencesParGroupe::CELL_PRESENT ? $presents++ : $absents++;
            }

            if ($presents + $absents === 0) {
                continue;
            }

            $date = $seance['date'];
            $parDate[$date] ??= ['date' => $date, 'presents' => 0, 'absents' => 0];
            $parDate[$date]['presents'] += $presents;
            $parDate[$date]['absents'] += $absents;
        }

        return array_values($parDate);
    }

    /**
     * Séances (not dates) carrying at least one mark of the charted students.
     *
     * @param  list<array<string, mixed>>  $seances
     * @param  list<array<string, mixed>>  $students
     */
    private function seancesComptees(array $seances, array $students): int
    {
        return count(array_filter($seances, function (array $seance) use ($students): bool {
            foreach ($students as $student) {
                if (isset(((array) $student['cells'])[(string) $seance['id']])) {
                    return true;
                }
            }

            return false;
        }));
    }
}
