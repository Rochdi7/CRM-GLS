<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Queries;

use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\User;
use App\Domain\Groups\Support\PorteeEnseignant;
use App\Models\Group;
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
    public const STATUT_ORDRE = [
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

        // Portée enseignant : le groupe demandé doit être l'un des siens —
        // sinon la liste des étudiants d'un groupe voisin sortirait par son id.
        $groupeHorsPortee = PorteeEnseignant::estRestreint($user)
            && ! PorteeEnseignant::scopeGroupes(Group::query()->whereKey($groupId), $user)->exists();

        if ($groupId === 0 || $groupeHorsPortee) {
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

        $built['students'] = $this->marquerAvantArrivee($groupId, $built['students'], $seances, $seancesSaisies);

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
    /**
     * Default window of the matrix (24/09/2026): the last N séances up to
     * today — about one month of classes. A whole year (189 columns) is
     * unreadable. Returns the date of the N-th most recent non-cancelled
     * séance (or the oldest one when the group has fewer), same scoping as
     * the matrix itself; null when there is none.
     */
    public const SEANCES_FENETRE_PAR_DEFAUT = 22;

    public function debutFenetreParDefaut(User $user, int $groupId, int $nombre = self::SEANCES_FENETRE_PAR_DEFAUT): ?string
    {
        $dates = $this->seances($user, $groupId, ['dateFrom' => '', 'dateTo' => now()->toDateString(), 'statutFilter' => ''])
            ->reject(fn (Seance $seance): bool => $seance->statut === Seance::STATUT_ANNULEE)
            ->sortByDesc(fn (Seance $seance): string => $seance->date_seance->toDateString())
            ->take($nombre);

        return $dates->isEmpty() ? null : $dates->last()->date_seance->toDateString();
    }

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
    public static function rangStatut(string $statut): int
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
                $bloc = self::rangStatut($a->statut) <=> self::rangStatut($b->statut);

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
                    // Filled by marquerAvantArrivee() once the séances are known.
                    'avantArrivee' => [],
                ];
            })
            ->all();

        $totals['etudiants'] = count($students);

        return ['students' => $students, 'totals' => $totals];
    }

    /**
     * « Avant arrivée » (06/10/2026): a student often joins a group after its
     * start (after 15 séances, mid-month…). The séances held before they
     * arrived are not missing marks — the student was simply not on the roll
     * yet — so they are painted BLACK on the page and in the export, apart
     * from the light grey « non pointé » cell that means a real oversight.
     *
     * Arrival = the date of the student's FIRST presence line in this group,
     * looked up over ALL its séances (not only the window), so narrowing the
     * dates never turns an old gap into a pre-arrival one. A cell is avant
     * arrivée when the séance was saisie, the student carries no mark on it
     * and it is dated before that arrival. An ACTIVE student never called at
     * all has not arrived yet, so every unmarked saisie séance is avant
     * arrivée; a CLOSED one never called keeps plain grey gaps (never came).
     * Cancelled séances are never avant arrivée — the page and the export
     * draw their X instead.
     *
     * Served as a list of séance ids per student (`avantArrivee`) so the page
     * and ExporterMatriceAbsences read one decision instead of re-deriving it.
     *
     * @param  list<array<string, mixed>>  $students
     * @param  Collection<int, Seance>  $seances
     * @param  Collection<int|string, int>  $seancesSaisies
     * @return list<array<string, mixed>>
     */
    private function marquerAvantArrivee(int $groupId, array $students, Collection $seances, Collection $seancesSaisies): array
    {
        $arrivees = Presence::query()
            ->join('seances', 'seances.id', '=', 'presences.seance_id')
            ->where('seances.group_id', $groupId)
            ->whereIn('presences.student_id', array_column($students, 'id'))
            ->groupBy('presences.student_id')
            ->selectRaw('presences.student_id, MIN(seances.date_seance) AS arrivee')
            ->pluck('arrivee', 'student_id')
            ->map(fn ($date): string => substr((string) $date, 0, 10));

        return array_map(function (array $student) use ($arrivees, $seances, $seancesSaisies): array {
            $arrivee = $arrivees->get($student['id']);
            $cells = (array) $student['cells'];

            // Never called AND the inscription is closed (Annulée,
            // Changement…): the student did not « arrive later », they never
            // came and the file is over — painting the whole row black read as
            // a late arrival. Plain gaps instead.
            if ($arrivee === null && ! $student['actif']) {
                $student['avantArrivee'] = [];

                return $student;
            }

            $student['avantArrivee'] = $seances
                ->filter(fn (Seance $seance): bool => $seancesSaisies->has($seance->id)
                    && $seance->statut !== Seance::STATUT_ANNULEE
                    && ! isset($cells[(string) $seance->id])
                    && ($arrivee === null || $seance->date_seance->toDateString() < $arrivee))
                ->pluck('id')
                ->values()
                ->all();

            return $student;
        }, $students);
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
