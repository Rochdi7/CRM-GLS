<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Inscription;
use App\Models\Student;
use App\Services\CaisseProvisioner;
use App\Services\Import\Contracts\Importer;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportResult;
use App\Services\Import\Exceptions\ImportCellParseException;
use App\Services\Import\Support\CellNormalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Legacy "Relevé des Encaissements" export -> encaissements. An Encaissement
 * is never imported independently — every row must resolve student ->
 * active inscription (scoped to the batch's Centre+Année, never a fallback
 * to another year) -> one of THAT inscription's real fee lines -> Opérateur
 * -> Employee -> the employee's own caisse. No `frais`/`inscription_fees`
 * row is ever created here (import plan's hard "no auto-create" rule);
 * commit() calls the existing EnregistrerEncaissement action, never a raw
 * insert.
 */
final class EncaissementImporter implements Importer
{
    private const string LEGACY_SOURCE = 'ancien-crm';

    private const array EXPECTED_COLUMNS = ['Réf.', 'Élève / Payeur', 'Type', 'Montant', 'Méthode', 'Frais', 'Date', 'Opérateur'];

    private const string TYPE_REGLEMENT = 'Réglement';

    /** File label => Encaissement::METHODE_* — "Virement bancaire" is NOT a literal match. */
    private const array METHODE_MAP = [
        'Espèces' => Encaissement::METHODE_ESPECES,
        'TPE' => Encaissement::METHODE_TPE,
        'Virement bancaire' => Encaissement::METHODE_VIREMENT,
        'Chèque' => Encaissement::METHODE_CHEQUE,
    ];

    public function __construct(
        private readonly SheetReader $sheetReader,
        private readonly ImportValidator $validator,
        private readonly CaisseProvisioner $caisseProvisioner,
    ) {}

    public function module(): string
    {
        return ImportBatch::MODULE_ENCAISSEMENTS;
    }

    public function analyze(UploadedFile $file, ImportContext $context, Employee $importingAdmin): ImportBatch
    {
        $filePath = $file->getRealPath();
        ['headerRowNumber' => $headerRow, 'headerMap' => $headerMap] = $this->sheetReader->detectHeader(
            $filePath,
            self::EXPECTED_COLUMNS
        );

        $operateurEmployees = $this->resolveOperateurEmployees($context);

        return DB::transaction(function () use ($filePath, $headerRow, $headerMap, $file, $context, $importingAdmin, $operateurEmployees): ImportBatch {
            $batch = ImportBatch::create([
                'module' => $this->module(),
                'original_filename' => $file->getClientOriginalName(),
                'etablissement_id' => $context->etablissementId,
                'annee_scolaire_id' => $context->anneeScolaireId,
                'context' => ['operateurMapping' => $context->operateurMapping],
                'created_by' => $importingAdmin->id,
                'status' => ImportBatch::STATUT_ANALYZED,
                'analyzed_at' => now(),
            ]);

            $total = 0;

            foreach ($this->sheetReader->readDataRows($filePath, $headerRow, $headerMap) as $rowNumber => $rawRow) {
                $total++;
                $this->analyzeRow($batch, $rowNumber, $rawRow, $context, $operateurEmployees);
            }

            $batch->update(['total_rows' => $total]);

            return $batch->fresh();
        });
    }

    public function commit(ImportBatch $batch, array $selectedRowIds, Employee $importingAdmin): ImportResult
    {
        // Chronological order keeps inscription_fees.statut transitions
        // (Non payé -> Payé partiellement -> Payé) readable across rows
        // targeting the same fee — the final caisse solde is order-
        // independent (a pure sum) either way.
        $rows = $batch->rows()
            ->whereIn('id', $selectedRowIds)
            ->whereIn('status', ImportRow::SELECTABLE_STATUTS)
            ->get()
            ->sortBy(fn (ImportRow $row) => [$row->raw['date_paiement'] ?? '', $row->source_row_number])
            ->values();

        $inserted = 0;
        $errors = 0;

        foreach ($rows as $row) {
            try {
                DB::transaction(function () use ($row): void {
                    $data = $row->raw;
                    $resolution = $row->resolution ?? [];

                    $agent = Employee::findOrFail($resolution['agent_id']);
                    $action = app(EnregistrerEncaissement::class);

                    $encaissement = $action->handle([
                        'student_id' => $resolution['student_id'],
                        'inscription_fee_id' => $resolution['inscription_fee_id'],
                        'montant' => $data['montant'],
                        'methode' => $data['methode'],
                        'date_paiement' => $data['date_paiement'],
                        'caisse_id' => $resolution['caisse_id'],
                        'note' => sprintf('Importé de l\'ancien CRM (Réf. %s)', $row->legacy_ref),
                        'legacy_ref' => $row->legacy_ref,
                        'legacy_source' => self::LEGACY_SOURCE,
                    ], $agent);

                    $row->update([
                        'status' => ImportRow::STATUT_INSERE,
                        'created_model_type' => Encaissement::class,
                        'created_model_id' => $encaissement->id,
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
    private function analyzeRow(ImportBatch $batch, int $rowNumber, array $rawRow, ImportContext $context, array $operateurEmployees): void
    {
        $legacyRef = CellNormalizer::text($rawRow['Réf.'] ?? '');
        $type = CellNormalizer::text($rawRow['Type'] ?? '');
        $payeurRaw = CellNormalizer::text($rawRow['Élève / Payeur'] ?? '');
        $methodeLabel = CellNormalizer::text($rawRow['Méthode'] ?? '');
        $fraisLabel = CellNormalizer::text($rawRow['Frais'] ?? '');
        $operateurLabel = CellNormalizer::text($rawRow['Opérateur'] ?? '');

        $collapsed = CellNormalizer::collapseDoubledName($payeurRaw);
        $payeurName = $collapsed['value'];
        $payerAmbiguous = ! $collapsed['collapsed'];

        $conflicts = [];
        $errors = [];

        if ($type !== self::TYPE_REGLEMENT) {
            $errors[] = ['field' => 'type', 'code' => 'unsupported_type', 'message' => sprintf(
                'Type "%s" non pris en charge (seul "%s" est importé).',
                $type,
                self::TYPE_REGLEMENT
            )];
        }

        $methode = self::METHODE_MAP[$methodeLabel] ?? null;
        if ($methode === null) {
            $errors[] = ['field' => 'methode', 'code' => 'unknown_methode', 'message' => sprintf(
                'Méthode "%s" non reconnue.',
                $methodeLabel
            )];
        }

        $parseErrors = [];
        try {
            $montant = CellNormalizer::parseMoney($rawRow['Montant'] ?? '');
        } catch (ImportCellParseException $e) {
            $montant = null;
            $parseErrors[] = ['field' => 'montant', 'code' => 'unparseable', 'message' => $e->getMessage()];
        }

        try {
            $datePaiement = CellNormalizer::parseDate($rawRow['Date'] ?? null)?->toDateString();
        } catch (ImportCellParseException $e) {
            $datePaiement = null;
            $parseErrors[] = ['field' => 'date_paiement', 'code' => 'unparseable', 'message' => $e->getMessage()];
        }

        $agentId = $operateurEmployees[$operateurLabel] ?? null;
        if ($operateurLabel === '' || $agentId === null) {
            $errors[] = ['field' => 'agent_id', 'code' => 'operateur_not_mapped', 'message' => sprintf(
                'Opérateur "%s" n\'a pas été associé à un employé.',
                $operateurLabel
            )];
        }

        $raw = [
            'legacy_ref' => $legacyRef,
            'type' => $type,
            'payeur_raw' => $payeurRaw,
            'payeur' => $payeurName,
            'methode_label' => $methodeLabel,
            'frais_label' => $fraisLabel,
            'operateur' => $operateurLabel,
            'montant' => $montant,
            'methode' => $methode,
            'date_paiement' => $datePaiement,
        ];

        if ($payerAmbiguous) {
            $conflicts[] = [
                'field' => 'payeur',
                'code' => 'payer_name_ambiguous',
                'message' => sprintf('Nom du payeur ambigu : "%s".', $payeurRaw),
            ];
        }

        $studentId = null;
        $inscriptionFeeId = null;
        $caisseId = null;
        $candidateFees = [];

        if (! $payerAmbiguous && $errors === [] && $parseErrors === []) {
            $studentResolution = $this->resolveStudent($batch, $payeurName);
            $studentId = $studentResolution['student_id'];
            $conflicts = [...$conflicts, ...$studentResolution['conflicts']];

            if ($studentId !== null) {
                $feeResolution = $this->resolveInscriptionFee($batch, $context, $studentId, $fraisLabel);
                $inscriptionFeeId = $feeResolution['inscription_fee_id'];
                $candidateFees = $feeResolution['candidates'];
                $conflicts = [...$conflicts, ...$feeResolution['conflicts']];
            }

            if ($agentId !== null) {
                $caisseId = $this->resolveCaisseFor((int) $agentId);
            }
        }

        $duplicate = $this->findDuplicate($legacyRef, $studentId, $montant, $datePaiement, $methode, $inscriptionFeeId);

        if ($duplicate !== null) {
            ImportRow::create([
                'import_batch_id' => $batch->id,
                'source_row_number' => $rowNumber,
                'raw' => $raw,
                'status' => ImportRow::STATUT_DOUBLON,
                'legacy_ref' => $legacyRef ?: null,
                'resolution' => ['matched_encaissement_id' => $duplicate->id],
            ]);

            return;
        }

        $allErrors = [...$parseErrors, ...$errors];

        if ($allErrors !== []) {
            ImportRow::create([
                'import_batch_id' => $batch->id,
                'source_row_number' => $rowNumber,
                'raw' => $raw,
                'status' => ImportRow::STATUT_ERREUR,
                'errors' => $allErrors,
                'legacy_ref' => $legacyRef ?: null,
            ]);

            return;
        }

        if ($conflicts !== []) {
            ImportRow::create([
                'import_batch_id' => $batch->id,
                'source_row_number' => $rowNumber,
                'raw' => $raw,
                'status' => ImportRow::STATUT_CONFLIT,
                'errors' => $conflicts,
                'legacy_ref' => $legacyRef ?: null,
                'resolution' => [
                    'student_id' => $studentId,
                    'candidate_fees' => $candidateFees,
                    'payer_guess' => $payerAmbiguous ? CellNormalizer::bestEffortNameGuess($payeurRaw) : null,
                ],
            ]);

            return;
        }

        $normalized = [
            'student_id' => $studentId,
            'montant' => $montant,
            'date_paiement' => $datePaiement,
            'methode' => $methode,
        ];

        $validationErrors = $this->validator->validateEncaissement($normalized);

        ImportRow::create([
            'import_batch_id' => $batch->id,
            'source_row_number' => $rowNumber,
            'raw' => $raw,
            'status' => $validationErrors === [] ? ImportRow::STATUT_NOUVEAU : ImportRow::STATUT_ERREUR,
            'errors' => $validationErrors === [] ? null : $validationErrors,
            'legacy_ref' => $legacyRef ?: null,
            'resolution' => [
                'student_id' => $studentId,
                'inscription_fee_id' => $inscriptionFeeId,
                'agent_id' => $agentId,
                'caisse_id' => $caisseId,
            ],
        ]);
    }

    /** @return array<string, int> raw Opérateur label => employees.id */
    private function resolveOperateurEmployees(ImportContext $context): array
    {
        return array_map('intval', $context->operateurMapping);
    }

    private function resolveCaisseFor(int $employeeId): int
    {
        $employee = Employee::findOrFail($employeeId);
        $caisse = $employee->caisses()->first() ?? $this->caisseProvisioner->provisionFor($employee);

        return $caisse->id;
    }

    /**
     * @return array{student_id: ?int, conflicts: array<int, array{field: string, code: string, message: string}>}
     */
    private function resolveStudent(ImportBatch $batch, string $payeurName): array
    {
        if ($payeurName === '') {
            return ['student_id' => null, 'conflicts' => [
                ['field' => 'student_id', 'code' => 'student_not_found', 'message' => 'Nom du payeur vide.'],
            ]];
        }

        $normalizedTarget = mb_strtolower(CellNormalizer::text($payeurName));

        $matches = Student::query()
            ->where('etablissement_id', $batch->etablissement_id)
            ->whereRaw(
                "lower(regexp_replace(trim(concat(prenom, ' ', nom)), '\\s+', ' ', 'g')) = ?",
                [$normalizedTarget]
            )
            ->get(['id']);

        if ($matches->count() === 1) {
            return ['student_id' => $matches->first()->id, 'conflicts' => []];
        }

        if ($matches->count() > 1) {
            return ['student_id' => null, 'conflicts' => [
                ['field' => 'student_id', 'code' => 'ambiguous_student', 'message' => sprintf(
                    'Plusieurs étudiants correspondent à "%s".',
                    $payeurName
                )],
            ]];
        }

        return ['student_id' => null, 'conflicts' => [
            ['field' => 'student_id', 'code' => 'student_not_found', 'message' => sprintf('Étudiant introuvable : "%s".', $payeurName)],
        ]];
    }

    /**
     * Finds the student's ACTIVE inscription strictly within the batch's
     * Centre+Année (never another year/centre as a fallback), then matches
     * the file's Frais label against THAT inscription's own real fee
     * lines. Never creates a frais/inscription_fees row.
     *
     * @return array{inscription_fee_id: ?int, candidates: array<int, array{id: int, nom: string, montant: string, statut: string}>, conflicts: array<int, array{field: string, code: string, message: string}>}
     */
    private function resolveInscriptionFee(ImportBatch $batch, ImportContext $context, int $studentId, string $fraisLabel): array
    {
        $inscription = Inscription::query()
            ->where('student_id', $studentId)
            ->where('etablissement_id', $batch->etablissement_id)
            ->where('annee_scolaire_id', $context->anneeScolaireId)
            ->where('statut', Inscription::STATUT_ACTIVE)
            ->first();

        if ($inscription === null) {
            return ['inscription_fee_id' => null, 'candidates' => [], 'conflicts' => [
                ['field' => 'inscription_fee_id', 'code' => 'no_active_inscription', 'message' =>
                    "Aucune inscription active pour cet étudiant sur le centre/année sélectionné."],
            ]];
        }

        $fees = $inscription->fees()->get(['id', 'nom', 'montant', 'statut']);
        $normalizedTarget = mb_strtolower(CellNormalizer::text($fraisLabel));

        $exact = $fees->first(fn ($fee) => mb_strtolower(CellNormalizer::text($fee->nom)) === $normalizedTarget);

        if ($exact !== null) {
            return ['inscription_fee_id' => $exact->id, 'candidates' => [], 'conflicts' => []];
        }

        $candidates = $fees->map(fn ($fee): array => [
            'id' => $fee->id,
            'nom' => $fee->nom,
            'montant' => (string) $fee->montant,
            'statut' => $fee->statut,
        ])->all();

        return ['inscription_fee_id' => null, 'candidates' => $candidates, 'conflicts' => [
            ['field' => 'inscription_fee_id', 'code' => 'fee_not_matched', 'message' => sprintf(
                'Le libellé de frais "%s" ne correspond à aucune ligne de cette inscription.',
                $fraisLabel
            )],
        ]];
    }

    private function findDuplicate(string $legacyRef, ?int $studentId, ?string $montant, ?string $datePaiement, ?string $methode, ?int $inscriptionFeeId): ?Encaissement
    {
        if ($legacyRef !== '') {
            $existing = Encaissement::query()->where('legacy_ref', $legacyRef)->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        if ($studentId === null || $montant === null || $datePaiement === null || $methode === null || $inscriptionFeeId === null) {
            return null;
        }

        return Encaissement::query()
            ->where('student_id', $studentId)
            ->where('montant', $montant)
            ->where('date_paiement', $datePaiement)
            ->where('methode', $methode)
            ->where('inscription_fee_id', $inscriptionFeeId)
            ->first();
    }
}
