<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\ImportRow;
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
 * Legacy "Liste des étudiants" export -> students. The reference importer:
 * simplest of the three, no external mapping UI, dedupe scoped to the
 * batch's Centre only (students have no annee_scolaire_id).
 */
final class StudentImporter implements Importer
{
    use TracksBatchProgress;

    private const string LEGACY_SOURCE = 'ancien-crm';

    private const array EXPECTED_COLUMNS = ['Réf', 'Prénom', 'Nom', 'Téléphone', 'Sexe', 'Date de naissance'];

    /** ImportRow rows are buffered and mass-inserted every N rows — real exports run into the thousands of rows, and one create() call per row (Eloquent events + a round-trip each) was a large part of the 30s timeout. */
    private const int INSERT_BUFFER_SIZE = 500;

    /**
     * Preloaded once per analyze() call, indexed (not a linear Collection
     * scan) — real exports can be thousands of rows, so an O(rows ×
     * students) linear findDuplicate() was itself a bottleneck once the
     * per-row DB query was removed.
     *
     * @var array<string, Student>|null legacy_ref => Student
     */
    private ?array $existingByLegacyRef = null;

    /** @var array<string, list<Student>>|null normalized "prenom|nom" => matching Students */
    private ?array $existingByName = null;

    /** @var array<int, array<string, mixed>> buffered ImportRow attribute arrays, flushed every INSERT_BUFFER_SIZE rows */
    private array $pendingRows = [];

    public function __construct(
        private readonly SheetReader $sheetReader,
        private readonly ImportValidator $validator,
    ) {}

    public function module(): string
    {
        return ImportBatch::MODULE_STUDENTS;
    }

    public function analyze(UploadedFile $file, ImportContext $context, Employee $importingAdmin): ImportBatch
    {
        $filePath = $file->getRealPath();
        ['headerRowNumber' => $headerRow, 'headerMap' => $headerMap] = $this->sheetReader->detectHeader(
            $filePath,
            self::EXPECTED_COLUMNS
        );

        $this->pendingRows = [];
        [$this->existingByLegacyRef, $this->existingByName] = $this->preloadExistingStudents($context->etablissementId);

        return DB::transaction(function () use ($filePath, $headerRow, $headerMap, $file, $context, $importingAdmin): ImportBatch {
            $batch = ImportBatch::create([
                'module' => $this->module(),
                'original_filename' => $file->getClientOriginalName(),
                'etablissement_id' => $context->etablissementId,
                'annee_scolaire_id' => $context->anneeScolaireId,
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
                $wasInserted = DB::transaction(function () use ($row, $batch): bool {
                    $data = $row->raw;

                    // analyze() dedupes against a snapshot; re-check live
                    // data before writing so a re-committed batch marks the
                    // row "déjà importé" instead of hitting the unique index.
                    if ($this->alreadyImported($row, $batch)) {
                        $row->update([
                            'status' => ImportRow::STATUT_DOUBLON,
                            'errors' => [[
                                'field' => 'legacy_ref',
                                'code' => 'already_imported',
                                'message' => 'Étudiant déjà importé — ligne ignorée.',
                            ]],
                        ]);

                        return false;
                    }

                    $student = Student::create([
                        'reference' => ReferenceGenerator::make('ETU', 'students'),
                        'legacy_ref' => $row->legacy_ref,
                        'legacy_source' => self::LEGACY_SOURCE,
                        'nom' => $data['nom'],
                        'prenom' => $data['prenom'],
                        'sexe' => $data['sexe'] ?: null,
                        'date_naissance' => $data['date_naissance'] ?: null,
                        'telephone' => $data['telephone'] ?: null,
                        'etablissement_id' => $batch->etablissement_id,
                    ]);

                    $row->update([
                        'status' => ImportRow::STATUT_INSERE,
                        'created_model_type' => Student::class,
                        'created_model_id' => $student->id,
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

    /** Live re-check of the analyze()-time dedupe, run just before writing. */
    private function alreadyImported(ImportRow $row, ImportBatch $batch): bool
    {
        if ($row->legacy_ref !== null && $row->legacy_ref !== '') {
            return Student::query()
                ->where('etablissement_id', $batch->etablissement_id)
                ->where('legacy_ref', $row->legacy_ref)
                ->exists();
        }

        return false;
    }


    /** @param array<string, mixed> $rawRow */
    private function analyzeRow(ImportBatch $batch, int $rowNumber, array $rawRow, ImportContext $context): void
    {
        $legacyRef = CellNormalizer::text($rawRow['Réf'] ?? '');
        $prenom = CellNormalizer::text($rawRow['Prénom'] ?? '');
        $nom = CellNormalizer::text($rawRow['Nom'] ?? '');
        $sexe = CellNormalizer::text($rawRow['Sexe'] ?? '');
        $telephone = CellNormalizer::normalizePhone($rawRow['Téléphone'] ?? '');

        try {
            $dateNaissance = CellNormalizer::parseDate($rawRow['Date de naissance'] ?? null)?->toDateString();
            $parseErrors = [];
        } catch (ImportCellParseException $e) {
            $dateNaissance = null;
            $parseErrors = [['field' => 'date_naissance', 'code' => 'unparseable', 'message' => $e->getMessage()]];
        }

        $normalized = [
            'nom' => $nom,
            'prenom' => $prenom,
            'sexe' => $sexe,
            'telephone' => $telephone,
            'date_naissance' => $dateNaissance,
        ];

        $raw = [
            ...$normalized,
            'legacy_ref' => $legacyRef,
            'telephone_raw' => $rawRow['Téléphone'] ?? null,
            'date_naissance_raw' => $rawRow['Date de naissance'] ?? null,
        ];

        $duplicate = $this->findDuplicate($legacyRef, $prenom, $nom, $dateNaissance, $telephone);

        if ($duplicate !== null) {
            // Name the rule that matched. "Ignorées: 4" with no reason left
            // the operator unable to tell a genuine re-import from a
            // same-name collision that silently dropped a real student.
            $reason = $legacyRef !== '' && $duplicate->legacy_ref === $legacyRef
                ? sprintf(
                    'Déjà importé : %s %s — la réf. %s existe déjà dans ce centre (import précédent).',
                    $prenom,
                    $nom,
                    $legacyRef,
                )
                : sprintf(
                    'Doublon : %s %s existe déjà dans ce centre (%s) — réf. %s.',
                    $duplicate->prenom,
                    $duplicate->nom,
                    $dateNaissance !== null
                        ? 'même date de naissance '.$dateNaissance
                        : 'même téléphone',
                    $duplicate->reference,
                );

            $this->pushPendingRow($batch, $rowNumber, [
                'raw' => $raw,
                'status' => ImportRow::STATUT_DOUBLON,
                'errors' => [[
                    'field' => 'legacy_ref',
                    'code' => 'already_imported',
                    'message' => $reason,
                ]],
                'legacy_ref' => $legacyRef ?: null,
                'resolution' => ['matched_student_id' => $duplicate->id],
            ]);

            return;
        }

        $errors = [...$parseErrors, ...$this->validator->validateStudent($normalized)];

        $this->pushPendingRow($batch, $rowNumber, [
            'raw' => $raw,
            'status' => $errors === [] ? ImportRow::STATUT_NOUVEAU : ImportRow::STATUT_ERREUR,
            'errors' => $errors === [] ? null : $errors,
            'legacy_ref' => $legacyRef ?: null,
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
     * @return array{0: array<string, Student>, 1: array<string, list<Student>>}
     */
    private function preloadExistingStudents(int $etablissementId): array
    {
        $students = Student::query()
            ->where('etablissement_id', $etablissementId)
            ->get(['id', 'prenom', 'nom', 'date_naissance', 'telephone', 'legacy_ref']);

        $byLegacyRef = [];
        $byName = [];

        foreach ($students as $student) {
            if ($student->legacy_ref !== null) {
                $byLegacyRef[$student->legacy_ref] = $student;
            }

            $nameKey = mb_strtolower($student->prenom).'|'.mb_strtolower($student->nom);
            $byName[$nameKey][] = $student;
        }

        return [$byLegacyRef, $byName];
    }

    private function findDuplicate(
        string $legacyRef,
        string $prenom,
        string $nom,
        ?string $dateNaissance,
        ?string $telephone,
    ): ?Student {
        if ($legacyRef !== '' && isset($this->existingByLegacyRef[$legacyRef])) {
            return $this->existingByLegacyRef[$legacyRef];
        }

        $nameKey = mb_strtolower($prenom).'|'.mb_strtolower($nom);
        $sameName = $this->existingByName[$nameKey] ?? [];

        if ($sameName === []) {
            return null;
        }

        if ($dateNaissance !== null) {
            foreach ($sameName as $student) {
                if ($student->date_naissance?->toDateString() === $dateNaissance) {
                    return $student;
                }
            }

            return null;
        }

        if ($telephone !== null) {
            foreach ($sameName as $student) {
                if ($student->telephone === $telephone) {
                    return $student;
                }
            }
        }

        return null;
    }
}
