<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\Employee;
use App\Models\Group;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\Student;
use App\Services\Import\Concerns\TracksBatchProgress;
use App\Services\Import\Contracts\Importer;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportResult;
use App\Services\Import\Exceptions\ImportCellParseException;
use App\Services\Import\Support\CellNormalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Legacy "Registre des présences" export -> séances + their per-student roll
 * call.
 *
 * This is the only importer that writes TWO tables. The export has one line
 * per (élève, groupe, date, horaire) — the séance itself is implicit, so it
 * is DERIVED: every distinct (groupe, date, heure_debut) becomes one Seance,
 * and each line becomes one Presence on it. A séance already present in the
 * database (created by hand or generated from a créneau) is reused as-is and
 * never duplicated — only its roll call is filled in.
 *
 * Resolution is scoped to the batch's CENTRE (like the payments importer —
 * a register spans groups living in two années, since a still-running group
 * was imported into the current année while its terminated siblings stayed
 * in the previous one): élèves are matched by normalized full name within
 * the centre, groupes only through the operator's explicit mapping (never by
 * label alone) — and a derived séance always carries its GROUP's année, not
 * the batch's, so the register never files a séance into a year its group
 * does not live in. The "Enseignant" column is advisory — an unmatched teacher
 * never blocks a line, it just leaves the séance falling back to the
 * group's own teacher rather than inventing an employee.
 */
final class PresenceImporter implements Importer
{
    use TracksBatchProgress;

    private const array EXPECTED_COLUMNS = ['Élève', 'Groupe', 'Date', 'Statut'];

    /** Same buffering rationale as the other importers — real registers run into the thousands of lines. */
    private const int INSERT_BUFFER_SIZE = 500;

    /**
     * The export's roll-call vocabulary mapped onto Presence::STATUTS.
     *
     * The two systems already agree on "Présent"/"Absent"; the entries below
     * only absorb spelling variants (missing accent, masculine/feminine) so
     * a perfectly valid line is never rejected as an unknown statut.
     * "Retard"/"Justifié" exist in this app and are accepted in case a newer
     * export carries them. Anything unrecognised is deliberately left
     * untouched for ImportValidator to reject loudly — including the literal
     * "-" the export writes when the roll call was never filled in.
     */
    private const array STATUT_MAP = [
        'Present' => Presence::STATUT_PRESENT,
        'Présente' => Presence::STATUT_PRESENT,
        'Presente' => Presence::STATUT_PRESENT,
        'Absente' => Presence::STATUT_ABSENT,
        'Justifie' => Presence::STATUT_JUSTIFIE,
        'Justifiée' => Presence::STATUT_JUSTIFIE,
        'Justifiee' => Presence::STATUT_JUSTIFIE,
        'En retard' => Presence::STATUT_RETARD,
    ];

    /**
     * Normalized full name => candidate élèves in the batch's centre.
     * Preloaded once per analyze() for the same reason as the other
     * importers: a per-row lookup query does not scale to a 1000-line file.
     *
     * @var array<string, array<int, array{id: int, nom: string}>>|null
     */
    private ?array $studentsByNormalizedName = null;

    /**
     * Normalized full name => employees.id, for the advisory Enseignant
     * column. Only unambiguous matches are kept.
     *
     * @var array<string, int>|null
     */
    private ?array $enseignantsByNormalizedName = null;

    /** @var array<int, true>|null every mapped group_id verified to belong to the batch's centre (whatever its année — see the class doc) */
    private ?array $validMappedGroupIds = null;

    /**
     * Séance identity ("groupId|date|heureDebut") => seances.id, for séances
     * that already existed before this batch — so a line lands on the app's
     * own séance instead of proposing a duplicate one.
     *
     * @var array<string, int>|null
     */
    private ?array $existingSeanceIds = null;

    /**
     * "seanceId|studentId" of every roll-call line already recorded — the
     * real dedupe key, since `presences` is UNIQUE (seance_id, student_id).
     *
     * @var array<string, true>|null
     */
    private ?array $existingPresenceKeys = null;

    /** @var array<string, true> composite keys ("groupId|date|heureDebut|studentId") already accepted WITHIN this file */
    private array $presenceKeysSeenThisFile = [];

    /** @var array<int, array<string, mixed>> buffered ImportRow attribute arrays */
    private array $pendingRows = [];

    public function __construct(
        private readonly SheetReader $sheetReader,
        private readonly ImportValidator $validator,
    ) {}

    public function module(): string
    {
        return ImportBatch::MODULE_PRESENCES;
    }

    public function analyze(UploadedFile $file, ImportContext $context, Employee $importingAdmin): ImportBatch
    {
        $filePath = $file->getRealPath();
        ['headerRowNumber' => $headerRow, 'headerMap' => $headerMap] = $this->sheetReader->detectHeader(
            $filePath,
            self::EXPECTED_COLUMNS
        );

        $this->presenceKeysSeenThisFile = [];
        $this->pendingRows = [];
        $this->studentsByNormalizedName = $this->preloadStudents($context->etablissementId);
        $this->enseignantsByNormalizedName = $this->preloadEnseignants($context->etablissementId);
        $this->validMappedGroupIds = $this->preloadValidMappedGroupIds($context);
        $this->existingSeanceIds = $this->preloadExistingSeances($context);
        $this->existingPresenceKeys = $this->preloadExistingPresences();

        return DB::transaction(function () use ($filePath, $headerRow, $headerMap, $file, $context, $importingAdmin): ImportBatch {
            $batch = ImportBatch::create([
                'module' => $this->module(),
                'original_filename' => $file->getClientOriginalName(),
                'etablissement_id' => $context->etablissementId,
                'annee_scolaire_id' => $context->anneeScolaireId,
                'context' => ['groupeMapping' => $context->groupeMapping],
                'created_by' => $importingAdmin->id,
                'status' => ImportBatch::STATUT_ANALYZED,
                'analyzed_at' => now(),
            ]);

            $total = 0;

            foreach ($this->sheetReader->readDataRows($filePath, $headerRow, $headerMap) as $rowNumber => $rawRow) {
                $total++;
                $this->analyzeRow($batch, $rowNumber, $rawRow, $context);
            }

            $this->flushPendingRows();

            $batch->update(['total_rows' => $total]);

            return $batch->fresh();
        });
    }

    public function commit(ImportBatch $batch, array $selectedRowIds, Employee $importingAdmin, int $chunkSize = 5): ImportResult
    {
        $eligibleQuery = $batch->rows()
            ->whereIn('id', $selectedRowIds)
            ->whereIn('status', ImportRow::SELECTABLE_STATUTS);

        $totalEligible = (clone $eligibleQuery)->count();
        $rows = (clone $eligibleQuery)->orderBy('id')->limit($chunkSize)->get();
        $remaining = max(0, $totalEligible - $rows->count());

        $inserted = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($rows as $row) {
            try {
                $wasInserted = DB::transaction(function () use ($row, $batch, $importingAdmin): bool {
                    $data = $row->raw;
                    $resolution = $row->resolution ?? [];

                    // A CONFLIT row carries candidates, not decisions —
                    // report the cause in French instead of letting a null
                    // reach a NOT NULL column as a raw SQL error.
                    $this->assertResolved($resolution, $data);

                    $group = Group::findOrFail($resolution['group_id'] ?? $data['group_id']);

                    if ((int) $group->etablissement_id !== (int) $batch->etablissement_id) {
                        throw new \RuntimeException('Le groupe résolu ne correspond pas au centre du lot.');
                    }

                    // The séance is derived, not imported: created by the
                    // first line that mentions it, reused by every later
                    // line of the same (groupe, date, horaire) — including
                    // one created by hand or generated from a créneau
                    // before the import ran.
                    $seance = $this->resolveOrCreateSeance($batch, $group, $data, $resolution, $importingAdmin);

                    $studentId = (int) ($resolution['student_id'] ?? $data['student_id']);

                    // analyze() deduped against a snapshot; re-check live
                    // data before writing so a re-committed batch marks the
                    // row "déjà importée" instead of hitting the UNIQUE
                    // (seance_id, student_id) index.
                    $alreadyRecorded = Presence::query()
                        ->where('seance_id', $seance->id)
                        ->where('student_id', $studentId)
                        ->exists();

                    if ($alreadyRecorded) {
                        $row->update([
                            'status' => ImportRow::STATUT_DOUBLON,
                            'errors' => [[
                                'field' => 'statut',
                                'code' => 'already_imported',
                                'message' => 'Présence déjà enregistrée pour cet élève sur cette séance — ligne ignorée.',
                            ]],
                        ]);

                        return false;
                    }

                    $presence = Presence::create([
                        'seance_id' => $seance->id,
                        'student_id' => $studentId,
                        'statut' => $data['statut'],
                        'note' => null,
                    ]);

                    $row->update([
                        'status' => ImportRow::STATUT_INSERE,
                        'created_model_type' => Presence::class,
                        'created_model_id' => $presence->id,
                        'resolution' => [...$resolution, 'seance_id' => $seance->id],
                    ]);

                    return true;
                });

                if ($wasInserted) {
                    $inserted++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $row->update([
                    'status' => ImportRow::STATUT_ECHEC_COMMIT,
                    'errors' => [['field' => null, 'code' => 'commit_failed', 'message' => $e->getMessage()]],
                ]);
                $errors++;
            }
        }

        $this->syncBatchProgress($batch, $remaining);

        return new ImportResult(insertedCount: $inserted, skippedCount: $skipped, errorCount: $errors, remaining: $remaining);
    }

    /**
     * Finds the séance this line belongs to, creating it only when none
     * exists yet for that (groupe, date, heure_debut).
     *
     * An existing séance is never modified — not its statut, not its hours,
     * not its teacher. The register records what happened; a séance already
     * in the app is the app's own record of the same event, and silently
     * rewriting it from a legacy file would destroy manual corrections.
     *
     * A séance CREATED here is marked "Effectuée": the register only ever
     * lists sessions that actually took place (that is what a roll call
     * is), so leaving them "Prévue" would show hundreds of past séances as
     * still upcoming.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $resolution
     */
    private function resolveOrCreateSeance(ImportBatch $batch, Group $group, array $data, array $resolution, Employee $importingAdmin): Seance
    {
        $dateSeance = (string) $data['date_seance'];
        $heureDebut = $data['heure_debut'] ?? null;

        $query = Seance::query()
            ->where('group_id', $group->id)
            ->whereDate('date_seance', $dateSeance);

        // A null heure_debut is its own identity, not a wildcard: matching
        // it against any time would merge two genuinely different sessions
        // held on the same day.
        if ($heureDebut === null) {
            $query->whereNull('heure_debut');
        } else {
            $query->where('heure_debut', $heureDebut);
        }

        $seance = $query->first();

        if ($seance !== null) {
            return $seance;
        }

        return Seance::create([
            'group_id' => $group->id,
            'creneau_id' => null,
            'date_seance' => $dateSeance,
            'heure_debut' => $heureDebut,
            'heure_fin' => $data['heure_fin'] ?? null,
            // The file's teacher when it matched an employee of this centre,
            // otherwise the group's own current teacher.
            'enseignant_id' => $resolution['enseignant_id'] ?? $group->enseignant_id,
            'etablissement_id' => $batch->etablissement_id,
            // The GROUP's année, not the batch's: the register spans groups
            // of two années, and a séance filed into a year its group does
            // not live in would be invisible to every année-scoped screen.
            'annee_scolaire_id' => $group->annee_scolaire_id,
            'statut' => Seance::STATUT_EFFECTUEE,
            'created_by' => $importingAdmin->id,
        ]);
    }

    /**
     * Every link a présence needs before it can be written, reported in
     * French rather than letting a null reach the database.
     *
     * @param  array<string, mixed>  $resolution
     * @param  array<string, mixed>  $data
     */
    private function assertResolved(array $resolution, array $data): void
    {
        if (($resolution['student_id'] ?? $data['student_id'] ?? null) === null) {
            throw new \RuntimeException(
                "Élève introuvable dans ce centre — vérifier l'orthographe ou importer d'abord les étudiants."
            );
        }

        if (($resolution['group_id'] ?? $data['group_id'] ?? null) === null) {
            throw new \RuntimeException(
                "Groupe non associé — revenir à l'écran d'association des groupes."
            );
        }

        if (($data['date_seance'] ?? null) === null) {
            throw new \RuntimeException('Date de séance illisible dans le fichier.');
        }

        if (! in_array($data['statut'] ?? '', Presence::STATUTS, true)) {
            throw new \RuntimeException('Statut de présence absent ou non reconnu sur cette ligne.');
        }
    }

    /** @param array<string, mixed> $rawRow */
    private function analyzeRow(ImportBatch $batch, int $rowNumber, array $rawRow, ImportContext $context): void
    {
        $eleve = CellNormalizer::text($rawRow['Élève'] ?? '');
        $groupe = CellNormalizer::text($rawRow['Groupe'] ?? '');
        $matiere = CellNormalizer::text($rawRow['Matière'] ?? '');
        $enseignantLabel = CellNormalizer::text($rawRow['Enseignant'] ?? '');
        $statutLabel = CellNormalizer::text($rawRow['Statut'] ?? '');
        $statut = self::translateStatut($statutLabel);

        $parseErrors = [];

        try {
            $dateSeance = CellNormalizer::parseDate($rawRow['Date'] ?? null)?->toDateString();
        } catch (ImportCellParseException $e) {
            $dateSeance = null;
            $parseErrors[] = ['field' => 'date_seance', 'code' => 'unparseable', 'message' => $e->getMessage()];
        }

        try {
            [$heureDebut, $heureFin] = CellNormalizer::parseTimeRange($rawRow['Horaire'] ?? null);
        } catch (ImportCellParseException $e) {
            $heureDebut = null;
            $heureFin = null;
            $parseErrors[] = ['field' => 'heure_debut', 'code' => 'unparseable', 'message' => $e->getMessage()];
        }

        $studentResolution = $this->resolveStudent($eleve);
        $groupResolution = $this->resolveGroup($context, $groupe);
        $enseignantId = $this->resolveEnseignant($enseignantLabel);

        $studentId = $studentResolution['student_id'];
        $groupId = $groupResolution['group_id'];

        $raw = [
            'eleve' => $eleve,
            'groupe' => $groupe,
            'matiere' => $matiere,
            'enseignant' => $enseignantLabel,
            'horaire' => CellNormalizer::text($rawRow['Horaire'] ?? ''),
            'statut' => $statut,
            'statut_label' => $statutLabel,
            'date_seance' => $dateSeance,
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
            'student_id' => $studentId,
            'group_id' => $groupId,
        ];

        $duplicateReason = $this->duplicateReason($studentId, $groupId, $dateSeance, $heureDebut, $eleve);

        if ($duplicateReason !== null) {
            $this->pushPendingRow($batch, $rowNumber, [
                'raw' => $raw,
                'status' => ImportRow::STATUT_DOUBLON,
                // Without this the line showed up only as an anonymous bump
                // in the "Ignorées" counter.
                'errors' => [$duplicateReason],
                'resolution' => null,
            ]);

            return;
        }

        if ($studentId !== null && $groupId !== null && $dateSeance !== null) {
            $this->presenceKeysSeenThisFile[$this->fileKey($groupId, $dateSeance, $heureDebut, $studentId)] = true;
        }

        $conflitReasons = [...$studentResolution['conflicts'], ...$groupResolution['conflicts']];

        if ($conflitReasons !== []) {
            $this->pushPendingRow($batch, $rowNumber, [
                'raw' => $raw,
                'status' => ImportRow::STATUT_CONFLIT,
                'errors' => $conflitReasons,
                'resolution' => [
                    'student_candidates' => $studentResolution['candidates'],
                    'group_candidates' => $groupResolution['candidates'],
                    'enseignant_id' => $enseignantId,
                ],
            ]);

            return;
        }

        $errors = [...$parseErrors, ...$this->validator->validatePresence([
            'student_id' => $studentId,
            'group_id' => $groupId,
            'date_seance' => $dateSeance,
            'statut' => $statut,
        ])];

        $this->pushPendingRow($batch, $rowNumber, [
            'raw' => $raw,
            'status' => $errors === [] ? ImportRow::STATUT_NOUVEAU : ImportRow::STATUT_ERREUR,
            'errors' => $errors === [] ? null : $errors,
            'resolution' => [
                'student_id' => $studentId,
                'group_id' => $groupId,
                'enseignant_id' => $enseignantId,
                // Informational for the preview screen ("la séance existe
                // déjà, seul l'appel sera complété") — the séance itself is
                // only resolved/created at commit time.
                'existing_seance_id' => ($groupId !== null && $dateSeance !== null)
                    ? ($this->existingSeanceIds[$this->seanceKey($groupId, $dateSeance, $heureDebut)] ?? null)
                    : null,
            ],
        ]);
    }

    /** Maps a legacy roll-call label onto this app's statut, leaving anything unrecognised for the validator. */
    private static function translateStatut(string $legacyStatut): string
    {
        return self::STATUT_MAP[$legacyStatut] ?? $legacyStatut;
    }

    /**
     * Returns WHY the line is a duplicate, or null when it is not one.
     *
     * The identity of a roll-call line is (groupe, date, horaire, élève) —
     * the same key `presences` enforces as UNIQUE (seance_id, student_id)
     * once the séance is resolved. A line that cannot be identified yet
     * (unresolved élève/groupe/date) is deliberately NOT called a duplicate:
     * it goes on to the conflict/error path where the operator can see and
     * fix it, instead of vanishing into the "Ignorées" counter.
     *
     * @return array{field: string, code: string, message: string}|null
     */
    private function duplicateReason(?int $studentId, ?int $groupId, ?string $dateSeance, ?string $heureDebut, string $eleve): ?array
    {
        if ($studentId === null || $groupId === null || $dateSeance === null) {
            return null;
        }

        $seanceId = $this->existingSeanceIds[$this->seanceKey($groupId, $dateSeance, $heureDebut)] ?? null;

        if ($seanceId !== null && isset($this->existingPresenceKeys["{$seanceId}|{$studentId}"])) {
            return [
                'field' => 'statut',
                'code' => 'already_in_database',
                'message' => sprintf(
                    'Déjà importée : %sa déjà une présence enregistrée sur la séance du %s.',
                    $eleve !== '' ? $eleve.' ' : 'cet élève ',
                    $dateSeance
                ),
            ];
        }

        if (isset($this->presenceKeysSeenThisFile[$this->fileKey($groupId, $dateSeance, $heureDebut, $studentId)])) {
            return [
                'field' => 'statut',
                'code' => 'duplicate_in_file',
                'message' => sprintf(
                    'Doublon dans le fichier : %sapparaît déjà sur la séance du %s à la même heure.',
                    $eleve !== '' ? $eleve.' — ' : '',
                    $dateSeance
                ),
            ];
        }

        return null;
    }

    private function seanceKey(int $groupId, string $dateSeance, ?string $heureDebut): string
    {
        return "{$groupId}|{$dateSeance}|".($heureDebut ?? '');
    }

    private function fileKey(int $groupId, string $dateSeance, ?string $heureDebut, int $studentId): string
    {
        return $this->seanceKey($groupId, $dateSeance, $heureDebut)."|{$studentId}";
    }

    /**
     * @return array{student_id: ?int, candidates: array<int, array{id: int, nom: string}>, conflicts: array<int, array{field: string, code: string, message: string}>}
     */
    private function resolveStudent(string $eleve): array
    {
        if ($eleve === '') {
            return ['student_id' => null, 'candidates' => [], 'conflicts' => [
                ['field' => 'student_id', 'code' => 'student_not_found', 'message' => 'Élève vide.'],
            ]];
        }

        $matches = $this->studentsByNormalizedName[mb_strtolower(CellNormalizer::text($eleve))] ?? [];

        if (count($matches) === 1) {
            return ['student_id' => $matches[0]['id'], 'candidates' => [], 'conflicts' => []];
        }

        if (count($matches) > 1) {
            return [
                'student_id' => null,
                'candidates' => $matches,
                'conflicts' => [['field' => 'student_id', 'code' => 'ambiguous_student', 'message' => sprintf(
                    'Plusieurs élèves correspondent à "%s".',
                    $eleve
                )]],
            ];
        }

        return ['student_id' => null, 'candidates' => [], 'conflicts' => [
            ['field' => 'student_id', 'code' => 'student_not_found', 'message' => sprintf('Élève introuvable : "%s".', $eleve)],
        ]];
    }

    /**
     * @return array{group_id: ?int, candidates: array<int, mixed>, conflicts: array<int, array{field: string, code: string, message: string}>}
     */
    private function resolveGroup(ImportContext $context, string $groupe): array
    {
        if ($groupe === '') {
            return ['group_id' => null, 'candidates' => [], 'conflicts' => [
                ['field' => 'group_id', 'code' => 'group_not_mapped', 'message' => 'Groupe vide.'],
            ]];
        }

        $mapping = $context->groupeMapping[$groupe] ?? null;

        if ($mapping === null) {
            return ['group_id' => null, 'candidates' => [], 'conflicts' => [
                ['field' => 'group_id', 'code' => 'group_not_mapped', 'message' => sprintf(
                    'Le groupe "%s" n\'a pas été associé.',
                    $groupe
                )],
            ]];
        }

        // Both actions arrive already resolved: a "create" group is created
        // eagerly by the controller when the mapping is submitted, exactly
        // as for Inscriptions.
        $groupId = isset($mapping['group_id']) ? (int) $mapping['group_id'] : null;

        if ($groupId === null) {
            return ['group_id' => null, 'candidates' => [], 'conflicts' => [
                ['field' => 'group_id', 'code' => 'group_not_created', 'message' => sprintf(
                    'Le groupe "%s" devait être créé mais ne l\'a pas été.',
                    $groupe
                )],
            ]];
        }

        if (! isset($this->validMappedGroupIds[$groupId])) {
            return ['group_id' => null, 'candidates' => [], 'conflicts' => [
                ['field' => 'group_id', 'code' => 'group_out_of_scope', 'message' => sprintf(
                    'Le groupe associé à "%s" ne correspond pas au centre sélectionné.',
                    $groupe
                )],
            ]];
        }

        return ['group_id' => $groupId, 'candidates' => [], 'conflicts' => []];
    }

    /**
     * Advisory only — the teacher never blocks a line. An unknown or
     * ambiguous name simply lets the séance fall back to the group's own
     * teacher, rather than inventing an employee.
     */
    private function resolveEnseignant(string $label): ?int
    {
        if ($label === '') {
            return null;
        }

        return $this->enseignantsByNormalizedName[mb_strtolower(CellNormalizer::text($label))] ?? null;
    }

    /**
     * Buffers one ImportRow's attributes instead of calling create() per
     * line — flushed in bulk every INSERT_BUFFER_SIZE rows and once more at
     * the end of analyze().
     *
     * @param  array<string, mixed>  $attributes
     */
    private function pushPendingRow(ImportBatch $batch, int $rowNumber, array $attributes): void
    {
        $now = now();

        $this->pendingRows[] = [
            'import_batch_id' => $batch->id,
            'source_row_number' => $rowNumber,
            'raw' => json_encode($attributes['raw']),
            'status' => $attributes['status'],
            'errors' => isset($attributes['errors']) ? json_encode($attributes['errors']) : null,
            'resolution' => isset($attributes['resolution']) ? json_encode($attributes['resolution']) : null,
            // The register carries no per-line reference of its own — the
            // identity is (groupe, date, horaire, élève), not a réf.
            'legacy_ref' => null,
            'created_model_type' => null,
            'created_model_id' => null,
            'selected' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (count($this->pendingRows) >= self::INSERT_BUFFER_SIZE) {
            $this->flushPendingRows();
        }
    }

    private function flushPendingRows(): void
    {
        if ($this->pendingRows === []) {
            return;
        }

        ImportRow::query()->insert($this->pendingRows);
        $this->pendingRows = [];
    }

    /**
     * @return array<string, array<int, array{id: int, nom: string}>>
     */
    private function preloadStudents(int $etablissementId): array
    {
        $index = [];

        Student::query()
            ->where('etablissement_id', $etablissementId)
            ->get(['id', 'prenom', 'nom'])
            ->each(function (Student $student) use (&$index): void {
                $key = mb_strtolower(CellNormalizer::text("{$student->prenom} {$student->nom}"));
                $index[$key][] = ['id' => $student->id, 'nom' => "{$student->prenom} {$student->nom}"];
            });

        return $index;
    }

    /**
     * Employees reachable from the batch's centre, indexed by normalized
     * full name in BOTH orders ("prenom nom" and "nom prenom") — the
     * register writes teacher names free-hand and is not consistent about
     * which comes first. A key claimed by two different employees is
     * dropped, so an ambiguous name resolves to nothing rather than to the
     * wrong person.
     *
     * Matches the centre through the employee_etablissement pivot with the
     * primary column as a fallback, the same access rule as everywhere else
     * (CLAUDE.md §16).
     *
     * @return array<string, int>
     */
    private function preloadEnseignants(int $etablissementId): array
    {
        $index = [];
        $ambiguous = [];

        Employee::query()
            ->where(function ($query) use ($etablissementId): void {
                $query
                    ->whereHas('etablissements', fn ($q) => $q->where('etablissements.id', $etablissementId))
                    ->orWhere('etablissement_id', $etablissementId);
            })
            ->get(['id', 'prenom', 'nom'])
            ->each(function (Employee $employee) use (&$index, &$ambiguous): void {
                $keys = array_unique([
                    mb_strtolower(CellNormalizer::text("{$employee->prenom} {$employee->nom}")),
                    mb_strtolower(CellNormalizer::text("{$employee->nom} {$employee->prenom}")),
                ]);

                foreach ($keys as $key) {
                    if ($key === '') {
                        continue;
                    }

                    if (isset($index[$key]) && $index[$key] !== $employee->id) {
                        $ambiguous[$key] = true;
                    }

                    $index[$key] = $employee->id;
                }
            });

        return array_diff_key($index, $ambiguous);
    }

    /**
     * Verifies once (not per row) that every mapped group_id actually
     * belongs to the batch's CENTRE — deliberately not its année: the
     * register spans both (see the class doc), and the derived séance takes
     * the group's own année at commit time.
     *
     * @return array<int, true>
     */
    private function preloadValidMappedGroupIds(ImportContext $context): array
    {
        $mappedIds = [];

        foreach ($context->groupeMapping as $mapping) {
            if (isset($mapping['group_id'])) {
                $mappedIds[] = (int) $mapping['group_id'];
            }
        }

        if ($mappedIds === []) {
            return [];
        }

        return Group::query()
            ->whereIn('id', $mappedIds)
            ->where('etablissement_id', $context->etablissementId)
            ->pluck('id')
            ->flip()
            ->map(fn () => true)
            ->all();
    }

    /**
     * @return array<string, int> "groupId|date|heureDebut" => seances.id
     */
    private function preloadExistingSeances(ImportContext $context): array
    {
        $index = [];

        Seance::query()
            ->where('etablissement_id', $context->etablissementId)
            ->get(['id', 'group_id', 'date_seance', 'heure_debut'])
            ->each(function (Seance $seance) use (&$index): void {
                $index[$this->seanceKey(
                    (int) $seance->group_id,
                    $seance->date_seance->toDateString(),
                    $seance->heure_debut === null ? null : substr((string) $seance->heure_debut, 0, 8)
                )] = $seance->id;
            });

        return $index;
    }

    /**
     * @return array<string, true> "seanceId|studentId"
     */
    private function preloadExistingPresences(): array
    {
        if ($this->existingSeanceIds === null || $this->existingSeanceIds === []) {
            return [];
        }

        return Presence::query()
            ->whereIn('seance_id', array_values($this->existingSeanceIds))
            ->get(['seance_id', 'student_id'])
            ->mapWithKeys(fn (Presence $presence): array => ["{$presence->seance_id}|{$presence->student_id}" => true])
            ->all();
    }
}
