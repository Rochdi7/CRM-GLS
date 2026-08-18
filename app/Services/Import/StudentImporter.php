<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Employee;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Student;
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
        $errors = 0;

        foreach ($rows as $row) {
            try {
                DB::transaction(function () use ($row, $batch): void {
                    $data = $row->raw;

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
                });

                $inserted++;
            } catch (\Throwable $e) {
                $row->update([
                    'status' => ImportRow::STATUT_ECHEC_COMMIT,
                    'errors' => [['field' => null, 'code' => 'commit_failed', 'message' => $e->getMessage()]],
                ]);
                $errors++;
            }
        }

        $batch->update([
            'status' => $this->nextBatchStatus($batch, $remaining, $errors),
            'inserted_rows' => $batch->inserted_rows + $inserted,
            'error_rows' => $batch->error_rows + $errors,
            'committed_at' => $remaining === 0 ? now() : $batch->committed_at,
        ]);

        return new ImportResult(insertedCount: $inserted, skippedCount: 0, errorCount: $errors, remaining: $remaining);
    }

    private function nextBatchStatus(ImportBatch $batch, int $remaining, int $errorsThisChunk): string
    {
        if ($remaining > 0) {
            return ImportBatch::STATUT_COMMITTING;
        }

        $hasAnyErrors = $errorsThisChunk > 0 || $batch->error_rows > 0;

        return $hasAnyErrors ? ImportBatch::STATUT_COMMITTED_WITH_ERRORS : ImportBatch::STATUT_COMMITTED;
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
            $this->pushPendingRow($batch, $rowNumber, [
                'raw' => $raw,
                'status' => ImportRow::STATUT_DOUBLON,
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
