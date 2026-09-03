<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Queries;

use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Support\Collection;

/**
 * Read-model for « Absence par groupe » — the presence MATRIX of one group:
 * one row per student ever enrolled in the group, one column per séance of
 * the selected date window, each cell holding that student's roll-call
 * status for that séance.
 *
 * Deliberately NOT paginated: the matrix is one group over one period
 * (a few dozen students × a few dozen séances) and is read as a whole —
 * the query count stays flat (3 queries) whatever the row count, because
 * séances, inscriptions and présences are each fetched once and joined in
 * PHP by (student_id, seance_id).
 *
 * Students whose inscription is no longer Active are kept in the matrix and
 * flagged (`actif = false`) — their past attendance is history and must stay
 * visible; the page paints their row red, exactly like the reference CRM.
 */
final class GetAbsencesParGroupe
{
    /** Cell letters shown in the matrix: Présent / Absent. */
    public const CELL_PRESENT = 'P';

    public const CELL_ABSENT = 'A';

    /**
     * Row order, IDENTICAL to « Détails paiement »
     * (Groups\Queries\GetGroupPaymentMatrix::STATUT_ORDRE) so the same group
     * reads in the same order on both screens: active students first, then
     * the closed blocks, alphabetically inside each block. A statut absent
     * from this map sorts after every known one, never interleaved with the
     * active students.
     */
    private const STATUT_ORDRE = [
        Inscription::STATUT_ACTIVE => 0,
        Inscription::STATUT_CHANGEMENT => 1,
        Inscription::STATUT_EXPIREE => 2,
        Inscription::STATUT_ARCHIVEE => 3,
        Inscription::STATUT_ANNULEE => 4,
    ];

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @param  array{groupFilter: string, dateFrom: string, dateTo: string, statutFilter: string}  $filters
     * @return array{seances: list<array<string, mixed>>, students: list<array<string, mixed>>, totals: array<string, int>}
     */
    public function __invoke(User $user, array $filters): array
    {
        $groupId = (int) $filters['groupFilter'];

        if ($groupId === 0) {
            return ['seances' => [], 'students' => [], 'totals' => $this->emptyTotals()];
        }

        $seances = $this->seances($user, $groupId, $filters);

        if ($seances->isEmpty()) {
            return [
                'seances' => [],
                'students' => $this->students($groupId, collect(), $filters)['students'],
                'totals' => $this->emptyTotals(),
            ];
        }

        $presences = Presence::query()
            ->whereIn('seance_id', $seances->pluck('id'))
            ->get(['seance_id', 'student_id', 'statut', 'note']);

        $built = $this->students($groupId, $presences, $filters);

        // Séance ids carrying at least one pointage, resolved once — a
        // ->where() per column would rescan the whole présence collection for
        // every séance of the window.
        $seancesSaisies = $presences->pluck('seance_id')->unique()->flip();

        return [
            'seances' => $seances
                ->values()
                ->map(fn (Seance $seance, int $index): array => [
                    'id' => $seance->id,
                    'numero' => $index + 1,
                    'date' => $seance->date_seance->toDateString(),
                    'heureDebut' => $seance->heure_debut ? substr($seance->heure_debut, 0, 5) : null,
                    'heureFin' => $seance->heure_fin ? substr($seance->heure_fin, 0, 5) : null,
                    'statut' => $seance->statut,
                    // No roll-call AT ALL on this séance — the same test the
                    // rest of the app uses to call a séance untreated
                    // (SeanceController@destroy: « Effectuée OR has
                    // presences » = traitée). The page greys the WHOLE column
                    // for these, so an unmarked séance is visible as one
                    // missing day rather than as a scatter of empty cells that
                    // read like « that student wasn't there ».
                    'saisie' => $seancesSaisies->has($seance->id),
                ])
                ->all(),
            'students' => $built['students'],
            'totals' => $built['totals'],
        ];
    }

    /**
     * Séances of the group inside the window, in chronological order — the
     * matrix columns. The Statut filter narrows the COLUMNS (e.g. only the
     * séances actually « Effectuée »), never the students.
     *
     * @param  array{dateFrom: string, dateTo: string, statutFilter: string}  $filters
     * @return Collection<int, Seance>
     */
    private function seances(User $user, int $groupId, array $filters): Collection
    {
        return Seance::query()
            ->where('group_id', $groupId)
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->when($filters['dateFrom'] !== '', fn ($q) => $q->whereDate('date_seance', '>=', $filters['dateFrom']))
            ->when($filters['dateTo'] !== '', fn ($q) => $q->whereDate('date_seance', '<=', $filters['dateTo']))
            ->when(
                in_array($filters['statutFilter'], Seance::STATUTS, true),
                fn ($q) => $q->where('statut', $filters['statutFilter']),
            )
            ->orderBy('date_seance')
            ->orderBy('heure_debut')
            ->orderBy('id')
            ->get(['id', 'date_seance', 'heure_debut', 'heure_fin', 'statut']);
    }

    /**
     * One row per student of the group, with its cells keyed by seance_id.
     *
     * @param  Collection<int, Presence>  $presences
     * @param  array{dateFrom: string, dateTo: string, statutFilter: string}  $filters
     * @return array{students: list<array<string, mixed>>, totals: array<string, int>}
     */
    /**
     * Position of a statut's block, with unknown values pushed past every
     * known one — mirrors GetGroupPaymentMatrix::rangStatut().
     */
    private function rangStatut(string $statut): int
    {
        return self::STATUT_ORDRE[$statut] ?? count(self::STATUT_ORDRE);
    }

    private function students(int $groupId, Collection $presences, array $filters): array
    {
        $byStudent = $presences->groupBy('student_id');

        $inscriptions = Inscription::query()
            ->with(['student:id,reference,nom,prenom,sexe', 'student.media'])
            ->where('group_id', $groupId)
            ->get(['id', 'student_id', 'statut'])
            ->filter(fn (Inscription $inscription): bool => $inscription->student !== null)
            // An active inscription always wins over a cancelled one when the
            // same student was re-enrolled in the group.
            ->sortByDesc(fn (Inscription $i): int => $i->statut === Inscription::STATUT_ACTIVE ? 1 : 0)
            ->unique('student_id');

        $totals = $this->emptyTotals();

        // Same two-level order as « Détails paiement »: statut block first,
        // then the student's full name inside it (strcoll, like
        // GetGroupPaymentMatrix::sortRows, so accented names collate the same
        // way on both screens).
        $students = $inscriptions
            ->sort(function (Inscription $a, Inscription $b): int {
                $bloc = $this->rangStatut($a->statut) <=> $this->rangStatut($b->statut);

                return $bloc !== 0
                    ? $bloc
                    : strcoll($a->student->nomComplet(), $b->student->nomComplet());
            })
            ->values()
            ->map(function (Inscription $inscription) use ($byStudent, &$totals): array {
                $student = $inscription->student;
                $cells = [];
                $presents = 0;
                $absents = 0;

                foreach ($byStudent->get($student->id, collect()) as $presence) {
                    $isPresent = $presence->statut === Presence::STATUT_PRESENT
                        || $presence->statut === Presence::STATUT_RETARD;

                    $cells[(string) $presence->seance_id] = [
                        'statut' => $presence->statut,
                        'lettre' => $isPresent ? self::CELL_PRESENT : self::CELL_ABSENT,
                        'note' => $presence->note,
                    ];

                    $isPresent ? $presents++ : $absents++;
                }

                $totals['presents'] += $presents;
                $totals['absents'] += $absents;

                return [
                    'id' => $student->id,
                    'reference' => $student->reference,
                    'nom' => $student->nom,
                    'prenom' => $student->prenom,
                    'photoUrl' => $student->avatarUrl(),
                    'inscriptionStatut' => $inscription->statut,
                    'actif' => $inscription->statut === Inscription::STATUT_ACTIVE,
                    'presents' => $presents,
                    'absents' => $absents,
                    'cells' => (object) $cells,
                ];
            })
            ->all();

        $totals['etudiants'] = count($students);

        return ['students' => $students, 'totals' => $totals];
    }

    /**
     * @return array{etudiants: int, presents: int, absents: int}
     */
    private function emptyTotals(): array
    {
        return ['etudiants' => 0, 'presents' => 0, 'absents' => 0];
    }

    private function scopeToActiveCenter($query): void
    {
        $id = $this->context->etablissementId();

        if ($id === null) {
            return;
        }

        $query->where(fn ($q) => $q->whereNull('etablissement_id')->orWhere('etablissement_id', $id));
    }
}
