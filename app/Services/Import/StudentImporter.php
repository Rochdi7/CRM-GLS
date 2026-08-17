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

            $batch->update(['total_rows' => $total]);

            return $batch->fresh();
        });
    }

    public function commit(ImportBatch $batch, array $selectedRowIds, Employee $importingAdmin): ImportResult
    {
        $rows = $batch->rows()
            ->whereIn('id', $selectedRowIds)
            ->whereIn('status', ImportRow::SELECTABLE_STATUTS)
            ->get();

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
            'status' => $errors > 0 ? ImportBatch::STATUT_COMMITTED_WITH_ERRORS : ImportBatch::STATUT_COMMITTED,
            'inserted_rows' => $batch->inserted_rows + $inserted,
            'error_rows' => $batch->error_rows + $errors,
            'committed_at' => now(),
        ]);

        return new ImportResult(insertedCount: $inserted, skippedCount: 0, errorCount: $errors);
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

        $duplicate = $this->findDuplicate($batch, $legacyRef, $prenom, $nom, $dateNaissance, $telephone);

        if ($duplicate !== null) {
            ImportRow::create([
                'import_batch_id' => $batch->id,
                'source_row_number' => $rowNumber,
                'raw' => $raw,
                'status' => ImportRow::STATUT_DOUBLON,
                'legacy_ref' => $legacyRef ?: null,
                'resolution' => ['matched_student_id' => $duplicate->id],
            ]);

            return;
        }

        $errors = [...$parseErrors, ...$this->validator->validateStudent($normalized)];

        ImportRow::create([
            'import_batch_id' => $batch->id,
            'source_row_number' => $rowNumber,
            'raw' => $raw,
            'status' => $errors === [] ? ImportRow::STATUT_NOUVEAU : ImportRow::STATUT_ERREUR,
            'errors' => $errors === [] ? null : $errors,
            'legacy_ref' => $legacyRef ?: null,
        ]);
    }

    private function findDuplicate(
        ImportBatch $batch,
        string $legacyRef,
        string $prenom,
        string $nom,
        ?string $dateNaissance,
        ?string $telephone,
    ): ?Student {
        if ($legacyRef !== '') {
            $existing = Student::query()
                ->where('etablissement_id', $batch->etablissement_id)
                ->where('legacy_ref', $legacyRef)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        $query = Student::query()
            ->where('etablissement_id', $batch->etablissement_id)
            ->whereRaw('lower(prenom) = ?', [mb_strtolower($prenom)])
            ->whereRaw('lower(nom) = ?', [mb_strtolower($nom)]);

        if ($dateNaissance !== null) {
            return (clone $query)->where('date_naissance', $dateNaissance)->first();
        }

        if ($telephone !== null) {
            return (clone $query)->where('telephone', $telephone)->first();
        }

        return null;
    }
}
