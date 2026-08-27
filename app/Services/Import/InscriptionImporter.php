<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Domain\Registrations\Queries\GetGroupInscriptionFees;
use App\Domain\Settings\Support\FraisEcheanceResolver;
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

    /**
     * Every legacy_ref already imported before this batch started (scoped to
     * the batch's centre), mapped to the composite identity ("studentId|
     * groupId|date") of the inscription(s) that carry it.
     *
     * It is a MAP, not a set, because the old CRM does not guarantee its
     * refs are unique: the same "333SL126" is reused for genuinely different
     * inscriptions (different student / group / date). Treating the ref
     * alone as an identity silently dropped those rows as "doublons". A ref
     * only proves a duplicate when the rest of the row matches too.
     *
     * @var array<string, array<string, true>>|null
     */
    private ?array $existingLegacyRefs = null;

    /** @var array<string, true>|null fallback composite dedupe key ("studentId|groupId|dateInscription") of every existing inscription */
    private ?array $existingCompositeKeys = null;

    /**
     * legacy_refs seen so far WITHIN this file, each mapped to the composite
     * identities already accepted under that ref — same reasoning as
     * $existingLegacyRefs: a repeated ref is a duplicate only when it repeats
     * the same student+groupe+date.
     *
     * @var array<string, array<string, true>>
     */
    private array $legacyRefsSeenThisFile = [];

    /**
     * How many DISTINCT inscriptions this file has already accepted under a
     * given legacy_ref. The second and later ones are stored as "REF#2",
     * "REF#3"… because `inscriptions` carries a UNIQUE
     * (etablissement_id, legacy_ref) index: writing the bare ref twice would
     * fail at commit time. The suffix keeps the original ref readable and
     * traceable while making the stored value unique.
     *
     * @var array<string, int>
     */
    private array $legacyRefUsage = [];

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
        return $this->analyzeMany([$file], $context, $importingAdmin);
    }

    /**
     * Analyzes SEVERAL export files into ONE batch — the old CRM exports one
     * file per statut (Annulée and Archivée come as separate lists), so the
     * combined import takes them together. All the in-file dedupe state
     * (legacy refs, composite keys) deliberately spans the whole set: an
     * inscription present in two of the files is a doublon, exactly as if
     * both rows sat in one sheet. Row numbers restart per file, so the batch
     * keeps a per-file offset to keep source_row_number unique and ordered.
     *
     * @param  list<UploadedFile>  $files
     */
    public function analyzeMany(array $files, ImportContext $context, Employee $importingAdmin): ImportBatch
    {
        $this->legacyRefsSeenThisFile = [];
        $this->legacyRefUsage = [];
        $this->compositeKeysSeenThisFile = [];
        $this->pendingRows = [];
        $this->studentsByNormalizedName = $this->preloadStudents($context->etablissementId);
        $this->validMappedGroupIds = $this->preloadValidMappedGroupIds($context);
        $this->existingLegacyRefs = $this->preloadExistingLegacyRefs($context->etablissementId);
        $this->existingCompositeKeys = $this->preloadExistingCompositeKeys();

        return DB::transaction(function () use ($files, $context, $importingAdmin): ImportBatch {
            $batch = ImportBatch::create([
                'module' => $this->module(),
                'original_filename' => implode(' + ', array_map(
                    fn (UploadedFile $f): string => $f->getClientOriginalName(),
                    $files,
                )),
                'etablissement_id' => $context->etablissementId,
                'annee_scolaire_id' => $context->anneeScolaireId,
                'context' => [
                    'groupeMapping' => $context->groupeMapping,
                    'statutsRetenus' => $context->statutsRetenus,
                ],
                'created_by' => $importingAdmin->id,
                'status' => ImportBatch::STATUT_ANALYZED,
                'analyzed_at' => now(),
            ]);

            $total = 0;
            $rowNumberOffset = 0;

            foreach ($files as $file) {
                $filePath = $file->getRealPath();
                ['headerRowNumber' => $headerRow, 'headerMap' => $headerMap] = $this->sheetReader->detectHeader(
                    $filePath,
                    self::EXPECTED_COLUMNS
                );

                $maxRowNumber = $rowNumberOffset;

                foreach ($this->sheetReader->readDataRows($filePath, $headerRow, $headerMap) as $rowNumber => $rawRow) {
                    $total++;
                    $maxRowNumber = max($maxRowNumber, $rowNumberOffset + $rowNumber);
                    $this->analyzeRow($batch, $rowNumberOffset + $rowNumber, $rawRow, $context);
                }

                $rowNumberOffset = $maxRowNumber;
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

                    if ((int) $group->etablissement_id !== (int) $batch->etablissement_id) {
                        throw new \RuntimeException('Le groupe résolu ne correspond pas au centre du lot.');
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
                        // The GROUP's année, not the batch's. A legacy export
                        // splits one running group across three files (Active
                        // / Annulé / Archive) that land in different années,
                        // which would tear the group in half — 6 Marrakech
                        // groups, 287 inscriptions (26/08/2026). A group is a
                        // real cohort sitting in one room: it belongs to
                        // exactly one année, and every inscription follows it
                        // whatever its own statut. The batch année still
                        // decides where a NEW group is created.
                        'annee_scolaire_id' => $group->annee_scolaire_id,
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

    /**
     * Live re-check of the analyze()-time dedupe, run just before writing.
     *
     * Identity is student + groupe + date, NEVER the legacy réf. on its own:
     * the old CRM reuses one réf. across unrelated inscriptions, so matching
     * on it alone silently refused to import legitimate rows (see
     * duplicateReason()). The réf. is only used to keep the exact stored
     * value — including any "#n" suffix — free of collisions.
     */
    private function alreadyImported(ImportRow $row, ImportBatch $batch): bool
    {
        $data = $row->raw;
        $resolution = $row->resolution ?? [];

        $studentId = $resolution['student_id'] ?? $data['student_id'] ?? null;
        $groupId = $resolution['group_id'] ?? $data['group_id'] ?? null;
        $dateInscription = $data['date_inscription'] ?? null;

        if ($studentId !== null && $groupId !== null && $dateInscription !== null) {
            $sameInscription = Inscription::query()
                ->where('student_id', $studentId)
                ->where('group_id', $groupId)
                ->where('date_inscription', $dateInscription)
                ->exists();

            if ($sameInscription) {
                return true;
            }
        }

        // Nothing identical exists, but the exact réf. this row claimed may
        // have been taken since analyze() ran (a concurrent or re-committed
        // batch). Skipping is safer than crashing on the unique index.
        if ($row->legacy_ref !== null && $row->legacy_ref !== '') {
            return Inscription::query()
                ->where('etablissement_id', $batch->etablissement_id)
                ->where('legacy_ref', $row->legacy_ref)
                ->exists();
        }

        return false;
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

            // Same defaults a group created through the normal UI gets
            // (GroupController::normalizedFraisLignes): the catalog's own
            // amount, and a due date derived from the fee's month. Pinning
            // these to 0/null made every imported inscription carry 18 empty
            // fee lines, which in turn made the "Enregistrer un paiement"
            // modal list nothing at all — its query keeps only fees whose
            // reste (montant - payé) is above zero.
            $dateDebut = $group->date_debut_formation?->toDateString();
            // Fallback anchor for the due-date YEAR when the group has no
            // start date — which is the norm for imported groups, the legacy
            // export carries none. See FraisEcheanceResolver::anneeFor().
            $debutAnnee = $group->anneeScolaire?->date_debut?->toDateString();

            // Priced at what the group's own center charges — the same
            // monthly fee is 1400 in Rabat and 1200 in Agadir — falling
            // back to the catalog default when that center has no line.
            $catalogue = Frais::query()
                ->where('statut', Frais::STATUT_ACTIF)
                ->with('etablissements:id')
                ->get(['id', 'nom', 'montant_defaut']);

            $sync = [];
            foreach ($catalogue as $frais) {
                $sync[$frais->id] = [
                    'montant' => $frais->montantPourCentre($group->etablissement_id),
                    'date_echeance' => FraisEcheanceResolver::defaultFor($frais->nom, $dateDebut, $debutAnnee),
                    'classification' => null,
                ];
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

        // Statut filter (ImportContext::$statutsRetenus): a row whose statut
        // is not selected for this batch is recorded as ignorée — visible in
        // the counters with its reason, never silently dropped — so ONE
        // legacy file can be split across years (history vs current) by
        // running it twice with different filters. Compared AFTER the legacy
        // translation, so "Archivée" is filtered as "Changement".
        if ($context->statutsRetenus !== [] && ! in_array($statut, $context->statutsRetenus, true)) {
            $this->pushPendingRow($batch, $rowNumber, [
                'raw' => [
                    'legacy_ref' => $legacyRef,
                    'etudiant' => $etudiant,
                    'groupe' => $groupe,
                    'statut' => $statut,
                    'statut_label' => $statutLabel,
                ],
                'status' => ImportRow::STATUT_DOUBLON,
                'errors' => [[
                    'field' => 'statut',
                    'code' => 'statut_filtre',
                    'message' => sprintf('Statut « %s » non retenu pour cet import — ligne ignorée.', $statut),
                ]],
                'legacy_ref' => $legacyRef !== '' ? $legacyRef : null,
                'resolution' => null,
            ]);

            return;
        }

        $parseErrors = [];
        try {
            $dateInscription = CellNormalizer::parseDate($rawRow["Date d'inscription"] ?? null)?->toDateString();
        } catch (ImportCellParseException $e) {
            $dateInscription = null;
            $parseErrors[] = ['field' => 'date_inscription', 'code' => 'unparseable', 'message' => $e->getMessage()];
        }

        $studentResolution = $this->resolveStudent($etudiant, CellNormalizer::normalizePhone($rawRow['Téléphone'] ?? null));
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

        $identityKey = ($studentId !== null && $groupId !== null && $dateInscription !== null)
            ? $this->compositeKey($studentId, $groupId, $dateInscription)
            : null;

        // The ref actually stored: the sheet's own value the first time it is
        // used, "REF#2"/"#3"… when the old CRM recycled it for a different
        // inscription (see claimLegacyRef / duplicateReason).
        $storedLegacyRef = null;

        if ($legacyRef !== '') {
            $storedLegacyRef = $this->claimLegacyRef($legacyRef);

            if ($identityKey !== null) {
                $this->legacyRefsSeenThisFile[$legacyRef][$identityKey] = true;
            }
        } elseif ($identityKey !== null) {
            $this->compositeKeysSeenThisFile[$identityKey] = true;
        }

        $conflitReasons = [...$studentResolution['conflicts'], ...$groupResolution['conflicts']];

        if ($conflitReasons !== []) {
            $this->pushPendingRow($batch, $rowNumber, [
                'raw' => $raw,
                'status' => ImportRow::STATUT_CONFLIT,
                'errors' => $conflitReasons,
                'legacy_ref' => $storedLegacyRef,
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
            'legacy_ref' => $storedLegacyRef,
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
            ->get(['id', 'prenom', 'nom', 'telephone'])
            ->each(function (Student $student) use (&$index): void {
                $key = mb_strtolower(CellNormalizer::text("{$student->prenom} {$student->nom}"));
                $index[$key][] = ['id' => $student->id, 'nom' => "{$student->prenom} {$student->nom}", 'telephone' => $student->telephone];
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

        // Centre only — a mapped group from ANOTHER année is valid: the
        // inscription follows its group's année (see commit()), so a group
        // already created by the Active file is reused by the Annulé /
        // Archive files instead of being duplicated a year apart.
        return Group::query()
            ->whereIn('id', $mappedIds)
            ->where('etablissement_id', $context->etablissementId)
            ->pluck('id')
            ->flip()
            ->map(fn () => true)
            ->all();
    }

    /**
     * ref => the composite identities already stored under it. The suffix
     * added for a reused ref ("333SL126#2") is stripped, so a re-analyzed
     * file compares against the ORIGINAL ref exactly as the sheet spells it.
     *
     * @return array<string, array<string, true>>
     */
    private function preloadExistingLegacyRefs(int $etablissementId): array
    {
        $index = [];

        Inscription::query()
            ->where('etablissement_id', $etablissementId)
            ->whereNotNull('legacy_ref')
            ->get(['legacy_ref', 'student_id', 'group_id', 'date_inscription'])
            ->each(function (Inscription $inscription) use (&$index): void {
                $ref = self::baseLegacyRef((string) $inscription->legacy_ref);
                $index[$ref][$this->compositeKey(
                    $inscription->student_id,
                    $inscription->group_id,
                    $inscription->date_inscription->toDateString()
                )] = true;
            });

        return $index;
    }

    /**
     * Strips the "#2"/"#3"… disambiguation suffix this importer appends when
     * the old CRM reuses one ref for several different inscriptions.
     */
    private static function baseLegacyRef(string $legacyRef): string
    {
        $hash = mb_strrpos($legacyRef, '#');

        if ($hash === false) {
            return $legacyRef;
        }

        $suffix = mb_substr($legacyRef, $hash + 1);

        return ctype_digit($suffix) && $suffix !== '' ? mb_substr($legacyRef, 0, $hash) : $legacyRef;
    }

    /**
     * The value actually written to inscriptions.legacy_ref for a row whose
     * ref is being reused by a DIFFERENT inscription: the bare ref the first
     * time, then "REF#2", "REF#3"… Required by the UNIQUE
     * (etablissement_id, legacy_ref) index; baseLegacyRef() undoes it when
     * comparing, so re-analyzing the same file still recognises its own rows.
     */
    private function claimLegacyRef(string $legacyRef): string
    {
        $alreadyStored = count($this->existingLegacyRefs[$legacyRef] ?? []);
        $seenThisFile = $this->legacyRefUsage[$legacyRef] ?? 0;

        $occurrence = $alreadyStored + $seenThisFile + 1;
        $this->legacyRefUsage[$legacyRef] = $seenThisFile + 1;

        return $occurrence === 1 ? $legacyRef : "{$legacyRef}#{$occurrence}";
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
    private function resolveStudent(string $etudiant, ?string $telephone = null): array
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

        // Homonyms: the inscriptions export carries the student's phone,
        // and the students export (already imported) stored it — when
        // exactly one of the same-name students has this phone, it is him.
        if (count($matches) > 1 && $telephone !== null) {
            $byPhone = array_values(array_filter($matches, fn (array $s): bool => $s['telephone'] === $telephone));

            if (count($byPhone) === 1) {
                return ['student_id' => $byPhone[0]['id'], 'candidates' => [], 'conflicts' => []];
            }
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
                // A priced fee behaves exactly like one added through the
                // normal UI: visible, and offered by the payment modal.
                // Only a fee still worth 0.00 is hidden — the legacy export
                // has no amount columns, so that is a fee whose price is
                // genuinely unknown rather than one the student owes.
                // EncaissementImporter unmasks (and prices) such a line if a
                // real payment later lands on it, and masque_le is the app's
                // own hide mechanism, so anything hidden here is restorable
                // from the inscription's "Frais masqués" list.
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
        $key = ($studentId !== null && $groupId !== null && $dateInscription !== null)
            ? $this->compositeKey($studentId, $groupId, $dateInscription)
            : null;

        if ($legacyRef !== '') {
            // The old CRM does NOT keep its réf. unique: the very same
            // "333SL126" is written on rows belonging to different students,
            // groups or dates. So the réf. alone can never decide that a row
            // is a duplicate — only that it MIGHT be. What settles it is the
            // rest of the row: same étudiant + même groupe + même date =>
            // genuinely the same inscription; anything else => a distinct
            // inscription that merely inherited a recycled réf. and must be
            // imported (claimLegacyRef() gives it a "#n" suffix so the
            // unique index accepts it).
            //
            // A row we cannot identify (student/groupe/date unresolved) is
            // NOT declared a duplicate either — it goes on to the conflict /
            // error path, where the operator can see and fix it, instead of
            // vanishing into the "Ignorées" counter.
            if ($key === null) {
                return null;
            }

            if (isset($this->existingLegacyRefs[$legacyRef][$key])) {
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

            if (isset($this->legacyRefsSeenThisFile[$legacyRef][$key])) {
                return [
                    'field' => 'legacy_ref',
                    'code' => 'duplicate_in_file',
                    'message' => sprintf(
                        'Doublon dans le fichier : %sla réf. %s apparaît sur une ligne précédente avec le même groupe et la même date.',
                        $etudiant !== '' ? $etudiant.' — ' : '',
                        $legacyRef
                    ),
                ];
            }

            return null;
        }

        if ($key === null) {
            return null;
        }

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
