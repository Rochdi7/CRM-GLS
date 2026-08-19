<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Domain\Registrations\Queries\GetGroupInscriptionFees;
use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Employee;
use App\Models\Frais;
use App\Models\Group;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Inscription;
use App\Models\InscriptionFee;
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
 * Legacy "Liste d'inscriptions" export -> inscriptions. Dedupe/resolution
 * scoped to the batch's Centre+Année (never by name/label alone — import
 * plan's "Mandatory Centre + Année scolaire scope"). Fee lines are ALWAYS
 * derived from the resolved group's group_frais via GetGroupInscriptionFees
 * — the same query the normal Inscriptions UI uses — never invented from
 * the xlsx (which has no fee columns at all) and never a new `frais` row.
 */
final class InscriptionImporter implements Importer
{
    use TracksBatchProgress;

    private const string LEGACY_SOURCE = 'ancien-crm';

    private const array EXPECTED_COLUMNS = ['Réf', 'Étudiant', 'Groupe', 'Statut', "Date d'inscription"];

    /** ImportRow rows are buffered and mass-inserted every N rows — real exports run into the thousands of rows, and one create() call per row (Eloquent events + a round-trip each) was a large part of the 30s timeout. */
    private const int INSERT_BUFFER_SIZE = 500;

    /**
     * Translates the old CRM's inscription statuts into this app's vocabulary.
     *
     * The two systems agree on every statut EXCEPT one: what the old CRM
     * calls "Archivée" is what this app calls "Changement" (the student
     * moved to another group / another arrangement rather than the record
     * being filed away). That is the single difference between the two
     * vocabularies — everything else maps to itself:
     *
     *   Active    -> Active
     *   Annulée   -> Annulée
     *   Archivée  -> Changement   <-- the only real translation
     *
     * The map also absorbs spelling variants of the SAME statut (the export
     * writes "Annulé"/"Expiré" in the masculine, and sometimes drops
     * accents). Those are not translations — they are the same value spelled
     * differently — but without them a perfectly valid cancelled
     * inscription is rejected as an unknown statut.
     *
     * "Expirée" is accepted as-is because it exists in this schema too
     * (a deprecated value kept for historical rows, see Inscription).
     * Unknown labels are deliberately left untouched so ImportValidator
     * still rejects them loudly instead of being silently coerced into
     * some default statut.
     */
    private const array STATUT_MAP = [
        'Archivée' => Inscription::STATUT_CHANGEMENT,
        // The legacy export is not consistent about the accent.
        'Archivee' => Inscription::STATUT_CHANGEMENT,
        'Archive' => Inscription::STATUT_CHANGEMENT,

        // Same statut, different spelling: the old CRM writes the masculine
        // "Annulé" where this app stores "Annulée". Not a translation —
        // just the same word — but it has to be listed or every cancelled
        // inscription is rejected as an unknown statut.
        'Annulé' => Inscription::STATUT_ANNULEE,
        'Annule' => Inscription::STATUT_ANNULEE,
        'Annulee' => Inscription::STATUT_ANNULEE,

        // Likewise for the other statuts the export may spell without
        // accents or in the masculine form.
        'Expiré' => Inscription::STATUT_EXPIREE,
        'Expire' => Inscription::STATUT_EXPIREE,
        'Expiree' => Inscription::STATUT_EXPIREE,
        'Actif' => Inscription::STATUT_ACTIVE,
    ];

    /**
     * Maps a legacy statut label onto this app's statut, leaving anything
     * unrecognised alone for the validator to reject.
     */
    private static function translateStatut(string $legacyStatut): string
    {
        return self::STATUT_MAP[$legacyStatut] ?? $legacyStatut;
    }

    /**
     * Preloaded once per analyze() call — resolving each row's student
     * against the DB via a per-row regexp_replace() query was timing out
     * on real 100+-row files; matching against an in-memory index scales
     * with the row count instead of row-count × query-cost.
     *
     * @var array<string, array<int, array{id: int, nom: string}>>|null normalized full name => candidate students, scoped to the current batch's centre
     */
    private ?array $studentsByNormalizedName = null;

    /**
     * Every group_id the mapping's "map" entries reference, verified once
     * to belong to the batch's centre+année — resolveGroup() no longer
     * queries per row for this.
     *
     * @var array<int, true>|null
     */
    private ?array $validMappedGroupIds = null;

    /** @var array<string, true>|null every legacy_ref already imported before this batch started, scoped to the batch's centre */
    private ?array $existingLegacyRefs = null;

    /** @var array<string, true>|null fallback composite dedupe key ("studentId|groupId|dateInscription") of every existing inscription */
    private ?array $existingCompositeKeys = null;

    /** legacy_refs seen so far WITHIN this file. */
    private array $legacyRefsSeenThisFile = [];

    /** composite keys seen so far WITHIN this file. */
    private array $compositeKeysSeenThisFile = [];

    /** @var array<int, array<string, mixed>> buffered ImportRow attribute arrays, flushed every INSERT_BUFFER_SIZE rows */
    private array $pendingRows = [];

    public function __construct(
        private readonly SheetReader $sheetReader,
        private readonly ImportValidator $validator,
        private readonly GetGroupInscriptionFees $getGroupInscriptionFees,
    ) {}

    public function module(): string
    {
        return ImportBatch::MODULE_INSCRIPTIONS;
    }

    public function analyze(UploadedFile $file, ImportContext $context, Employee $importingAdmin): ImportBatch
    {
        $filePath = $file->getRealPath();
        ['headerRowNumber' => $headerRow, 'headerMap' => $headerMap] = $this->sheetReader->detectHeader(
            $filePath,
            self::EXPECTED_COLUMNS
        );

        $this->legacyRefsSeenThisFile = [];
        $this->compositeKeysSeenThisFile = [];
        $this->pendingRows = [];
        $this->studentsByNormalizedName = $this->preloadStudents($context->etablissementId);
        $this->validMappedGroupIds = $this->preloadValidMappedGroupIds($context);
        $this->existingLegacyRefs = $this->preloadExistingLegacyRefs($context->etablissementId);
        $this->existingCompositeKeys = $this->preloadExistingCompositeKeys();

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

                    // analyze() dedupes against a snapshot; re-check live
                    // data before writing so a re-committed batch marks the
                    // row "déjà importé" instead of hitting the unique index.
                    if ($this->alreadyImported($row, $batch)) {
                        $row->update([
                            'status' => ImportRow::STATUT_DOUBLON,
                            'errors' => [[
                                'field' => 'legacy_ref',
                                'code' => 'already_imported',
                                'message' => 'Inscription déjà importée — ligne ignorée.',
                            ]],
                        ]);

                        return false;
                    }

                    // A CONFLIT row carries candidates, not decisions. Without
                    // this it reached Inscription::create() with a null
                    // student_id and died on the NOT NULL constraint — a raw
                    // SQL error in the failure list instead of a reason.
                    $this->assertResolved($resolution, $data);

                    $groupId = $resolution['group_id'] ?? $data['group_id'] ?? null;
                    $group = Group::findOrFail($groupId);

                    if ((int) $group->etablissement_id !== (int) $batch->etablissement_id
                        || (int) $group->annee_scolaire_id !== (int) $batch->annee_scolaire_id) {
                        throw new \RuntimeException('Le groupe résolu ne correspond pas au centre/année du lot.');
                    }

                    $feeLines = $this->buildFeeLines($group);
                    $total = array_sum(array_column($feeLines, 'montant'));

                    $inscription = Inscription::create([
                        'reference' => ReferenceGenerator::make('INS', 'inscriptions'),
                        'legacy_ref' => $row->legacy_ref,
                        'legacy_source' => self::LEGACY_SOURCE,
                        'student_id' => $resolution['student_id'] ?? $data['student_id'],
                        'group_id' => $group->id,
                        'etablissement_id' => $batch->etablissement_id,
                        'annee_scolaire_id' => $batch->annee_scolaire_id,
                        'statut' => $data['statut'],
                        'date_inscription' => $data['date_inscription'],
                        'date_debut' => $group->date_debut_formation?->toDateString(),
                        'date_fin' => $group->date_fin_formation?->toDateString(),
                        'montant_total' => $total > 0 ? $total : null,
                        'created_by' => $importingAdmin->id,
                    ]);

                    foreach ($feeLines as $line) {
                        $inscription->fees()->create($line);
                    }

                    $row->update([
                        'status' => ImportRow::STATUT_INSERE,
                        'created_model_type' => Inscription::class,
                        'created_model_id' => $inscription->id,
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
     * Every link an inscription needs before it can be written. Reports the
     * cause in French rather than letting a null reach the database.
     *
     * @param  array<string, mixed>  $resolution
     * @param  array<string, mixed>  $data
     */
    private function assertResolved(array $resolution, array $data): void
    {
        if (($resolution['student_id'] ?? $data['student_id'] ?? null) === null) {
            throw new \RuntimeException(
                "Étudiant introuvable dans ce centre — vérifier l'orthographe ou importer d'abord les étudiants."
            );
        }

        if (($resolution['group_id'] ?? $data['group_id'] ?? null) === null) {
            throw new \RuntimeException(
                "Groupe non associé — revenir à l'écran d'association des groupes."
            );
        }

        if (($data['date_inscription'] ?? null) === null) {
            throw new \RuntimeException("Date d'inscription illisible dans le fichier.");
        }
    }

    /** Live re-check of the analyze()-time dedupe, run just before writing. */
    private function alreadyImported(ImportRow $row, ImportBatch $batch): bool
    {
        if ($row->legacy_ref !== null && $row->legacy_ref !== '') {
            return Inscription::query()
                ->where('etablissement_id', $batch->etablissement_id)
                ->where('legacy_ref', $row->legacy_ref)
                ->exists();
        }

        $data = $row->raw;
        $resolution = $row->resolution ?? [];

        if (($resolution['student_id'] ?? null) === null || ($resolution['group_id'] ?? null) === null) {
            return false;
        }

        return Inscription::query()
            ->where('student_id', $resolution['student_id'])
            ->where('group_id', $resolution['group_id'])
            ->where('date_inscription', $data['date_inscription'])
            ->exists();
    }


    /**
     * Every active catalog Frais row gets a pivot line on a newly-created
     * group — the exact same sync as GroupController::normalizedFraisLignes()
     * — never a hand-picked subset, never a new `frais` row.
     */
    public function createGroupWithFullCatalogSync(array $attributes): Group
    {
        return DB::transaction(function () use ($attributes): Group {
            $group = Group::create($attributes);

            $sync = [];
            foreach (Frais::query()->where('statut', Frais::STATUT_ACTIF)->pluck('id') as $fraisId) {
                $sync[$fraisId] = ['montant' => 0, 'date_echeance' => null, 'classification' => null];
            }
            $group->frais()->sync($sync);

            return $group;
        });
    }

    /** @param array<string, mixed> $rawRow */
    private function analyzeRow(ImportBatch $batch, int $rowNumber, array $rawRow, ImportContext $context): void
    {
        $legacyRef = CellNormalizer::text($rawRow['Réf'] ?? '');
        $etudiant = CellNormalizer::text($rawRow['Étudiant'] ?? '');
        $groupe = CellNormalizer::text($rawRow['Groupe'] ?? '');
        $statutLabel = CellNormalizer::text($rawRow['Statut'] ?? '');
        $statut = self::translateStatut($statutLabel);

        $parseErrors = [];
        try {
            $dateInscription = CellNormalizer::parseDate($rawRow["Date d'inscription"] ?? null)?->toDateString();
        } catch (ImportCellParseException $e) {
            $dateInscription = null;
            $parseErrors[] = ['field' => 'date_inscription', 'code' => 'unparseable', 'message' => $e->getMessage()];
        }

        $studentResolution = $this->resolveStudent($etudiant);
        $groupResolution = $this->resolveGroup($context, $groupe);

        $raw = [
            'legacy_ref' => $legacyRef,
            'etudiant' => $etudiant,
            'groupe' => $groupe,
            'statut' => $statut,
            'statut_label' => $statutLabel,
            'date_inscription' => $dateInscription,
            'student_id' => $studentResolution['student_id'],
            'group_id' => $groupResolution['group_id'],
        ];

        $studentId = $studentResolution['student_id'];
        $groupId = $groupResolution['group_id'];

        $duplicateReason = $this->duplicateReason($legacyRef, $studentId, $groupId, $dateInscription, $etudiant);

        if ($duplicateReason !== null) {
            $this->pushPendingRow($batch, $rowNumber, [
                'raw' => $raw,
                'status' => ImportRow::STATUT_DOUBLON,
                // Without this the row showed up only as an anonymous bump
                // in the "Ignorées" counter.
                'errors' => [$duplicateReason],
                'legacy_ref' => $legacyRef ?: null,
                'resolution' => null,
            ]);

            return;
        }

        if ($legacyRef !== '') {
            $this->legacyRefsSeenThisFile[$legacyRef] = true;
        } elseif ($studentId !== null && $groupId !== null && $dateInscription !== null) {
            $this->compositeKeysSeenThisFile[$this->compositeKey($studentId, $groupId, $dateInscription)] = true;
        }

        $conflitReasons = [...$studentResolution['conflicts'], ...$groupResolution['conflicts']];

        if ($conflitReasons !== []) {
            $this->pushPendingRow($batch, $rowNumber, [
                'raw' => $raw,
                'status' => ImportRow::STATUT_CONFLIT,
                'errors' => $conflitReasons,
                'legacy_ref' => $legacyRef ?: null,
                'resolution' => [
                    'student_candidates' => $studentResolution['candidates'],
                    'group_candidates' => $groupResolution['candidates'],
                ],
            ]);

            return;
        }

        $normalized = [
            'student_id' => $studentId,
            'group_id' => $groupId,
            'date_inscription' => $dateInscription,
            'statut' => $statut,
        ];

        $errors = [...$parseErrors, ...$this->validator->validateInscription($normalized)];

        $this->pushPendingRow($batch, $rowNumber, [
            'raw' => $raw,
            'status' => $errors === [] ? ImportRow::STATUT_NOUVEAU : ImportRow::STATUT_ERREUR,
            'errors' => $errors === [] ? null : $errors,
            'legacy_ref' => $legacyRef ?: null,
            'resolution' => ['student_id' => $studentId, 'group_id' => $groupId],
        ]);
    }

    /**
     * Buffers one ImportRow's attributes instead of calling create() per
     * row — flushed in bulk via flushPendingRows() every
     * INSERT_BUFFER_SIZE rows, and once more at the end of analyze().
     *
     * @param array<string, mixed> $attributes
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
            'legacy_ref' => $attributes['legacy_ref'] ?? null,
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
     * Verifies once (not per row) that every "map"-action group_id in the
     * mapping actually belongs to the batch's centre+année.
     *
     * @return array<int, true>
     */
    private function preloadValidMappedGroupIds(ImportContext $context): array
    {
        $mappedIds = [];
        foreach ($context->groupeMapping as $mapping) {
            if ($mapping['action'] === 'map' && isset($mapping['group_id'])) {
                $mappedIds[] = (int) $mapping['group_id'];
            }
        }

        if ($mappedIds === []) {
            return [];
        }

        return Group::query()
            ->whereIn('id', $mappedIds)
            ->where('etablissement_id', $context->etablissementId)
            ->where('annee_scolaire_id', $context->anneeScolaireId)
            ->pluck('id')
            ->flip()
            ->map(fn () => true)
            ->all();
    }

    /** @return array<string, true> */
    private function preloadExistingLegacyRefs(int $etablissementId): array
    {
        return Inscription::query()
            ->where('etablissement_id', $etablissementId)
            ->whereNotNull('legacy_ref')
            ->pluck('legacy_ref')
            ->flip()
            ->map(fn () => true)
            ->all();
    }

    /** @return array<string, true> */
    private function preloadExistingCompositeKeys(): array
    {
        $index = [];

        Inscription::query()
            ->get(['student_id', 'group_id', 'date_inscription'])
            ->each(function (Inscription $inscription) use (&$index): void {
                $index[$this->compositeKey($inscription->student_id, $inscription->group_id, $inscription->date_inscription->toDateString())] = true;
            });

        return $index;
    }

    private function compositeKey(int $studentId, int $groupId, string $dateInscription): string
    {
        return "{$studentId}|{$groupId}|{$dateInscription}";
    }

    /**
     * @return array{student_id: ?int, candidates: array<int, array{id: int, nom: string}>, conflicts: array<int, array{field: string, code: string, message: string}>}
     */
    private function resolveStudent(string $etudiant): array
    {
        if ($etudiant === '') {
            return ['student_id' => null, 'candidates' => [], 'conflicts' => [
                ['field' => 'student_id', 'code' => 'student_not_found', 'message' => 'Étudiant vide.'],
            ]];
        }

        $normalizedTarget = mb_strtolower(CellNormalizer::text($etudiant));
        $matches = $this->studentsByNormalizedName[$normalizedTarget] ?? [];

        if (count($matches) === 1) {
            return ['student_id' => $matches[0]['id'], 'candidates' => [], 'conflicts' => []];
        }

        if (count($matches) > 1) {
            return [
                'student_id' => null,
                'candidates' => $matches,
                'conflicts' => [['field' => 'student_id', 'code' => 'ambiguous_student', 'message' => sprintf(
                    'Plusieurs étudiants correspondent à "%s".',
                    $etudiant
                )]],
            ];
        }

        return ['student_id' => null, 'candidates' => [], 'conflicts' => [
            ['field' => 'student_id', 'code' => 'student_not_found', 'message' => sprintf('Étudiant introuvable : "%s".', $etudiant)],
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

        if ($mapping['action'] === 'map') {
            $groupId = (int) $mapping['group_id'];

            if (! isset($this->validMappedGroupIds[$groupId])) {
                return ['group_id' => null, 'candidates' => [], 'conflicts' => [
                    ['field' => 'group_id', 'code' => 'group_out_of_scope', 'message' => sprintf(
                        'Le groupe associé à "%s" ne correspond pas au centre/année sélectionné.',
                        $groupe
                    )],
                ]];
            }

            return ['group_id' => $groupId, 'candidates' => [], 'conflicts' => []];
        }

        // action === 'create': the group is created once, eagerly, when the
        // mapping is submitted by the controller — by the time analyzeRow()
        // runs, $context->groupeMapping already carries a resolved group_id.
        if (isset($mapping['group_id'])) {
            return ['group_id' => (int) $mapping['group_id'], 'candidates' => [], 'conflicts' => []];
        }

        return ['group_id' => null, 'candidates' => [], 'conflicts' => [
            ['field' => 'group_id', 'code' => 'group_not_created', 'message' => sprintf(
                'Le groupe "%s" devait être créé mais ne l\'a pas été.',
                $groupe
            )],
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildFeeLines(Group $group): array
    {
        return $this->getGroupInscriptionFees->__invoke($group)->map(function (array $fee): array {
            $initial = (float) $fee['montantInitial'];

            return [
                'frais_id' => $fee['fraisId'],
                'nom' => $fee['nom'],
                'montant_initial' => $initial,
                'remise_pct' => null,
                'remise_montant' => null,
                'montant' => InscriptionFee::computeMontant($initial, null, null),
                'date_echeance' => $fee['dateEcheance'],
                'statut' => InscriptionFee::STATUT_NON_PAYE,
                // The legacy export carries no fee amounts, so every one of
                // the group's ~18 catalog lines would otherwise appear on
                // the inscription priced at 0.00 — 15 of them fees the
                // student never owed. Import-created lines therefore start
                // MASKED; EncaissementImporter unmasks (and prices) the ones
                // a real payment lands on. masque_le is the app's own
                // existing hide mechanism, so anything wrongly masked can be
                // restored from the inscription's "Frais masqués" list.
                'masque_le' => $initial > 0 ? null : now(),
            ];
        })->all();
    }

    /**
     * Returns WHY the row is a duplicate, or null when it is not one.
     *
     * A bare boolean left every skipped row unexplained on the Result
     * screen ("Ignorées: 4" with nothing to click); the four causes below
     * are genuinely different problems — already in the database vs. the
     * same row twice inside one file — and the operator needs to tell them
     * apart to know whether anything was actually lost.
     *
     * @return array{field: string, code: string, message: string}|null
     */
    private function duplicateReason(string $legacyRef, ?int $studentId, ?int $groupId, ?string $dateInscription, string $etudiant = ''): ?array
    {
        if ($legacyRef !== '') {
            if (isset($this->existingLegacyRefs[$legacyRef])) {
                return [
                    'field' => 'legacy_ref',
                    'code' => 'already_in_database',
                    'message' => sprintf(
                        'Déjà importée : %sla réf. %s existe déjà dans ce centre (import précédent).',
                        $etudiant !== '' ? $etudiant.' — ' : '',
                        $legacyRef
                    ),
                ];
            }

            if (isset($this->legacyRefsSeenThisFile[$legacyRef])) {
                return [
                    'field' => 'legacy_ref',
                    'code' => 'duplicate_in_file',
                    'message' => sprintf(
                        'Doublon dans le fichier : %sla réf. %s apparaît sur une ligne précédente.',
                        $etudiant !== '' ? $etudiant.' — ' : '',
                        $legacyRef
                    ),
                ];
            }

            return null;
        }

        if ($studentId === null || $groupId === null || $dateInscription === null) {
            return null;
        }

        $key = $this->compositeKey($studentId, $groupId, $dateInscription);

        if (isset($this->existingCompositeKeys[$key])) {
            return [
                'field' => 'student_id',
                'code' => 'already_in_database',
                'message' => sprintf(
                    'Déjà importée : %sa déjà une inscription dans ce groupe au %s.',
                    $etudiant !== '' ? $etudiant.' ' : 'cet étudiant ',
                    $dateInscription
                ),
            ];
        }

        if (isset($this->compositeKeysSeenThisFile[$key])) {
            return [
                'field' => 'student_id',
                'code' => 'duplicate_in_file',
                'message' => sprintf(
                    'Doublon dans le fichier : %smême groupe et même date (%s) sur une ligne précédente.',
                    $etudiant !== '' ? $etudiant.' — ' : 'même étudiant, ',
                    $dateInscription
                ),
            ];
        }

        return null;
    }
}
