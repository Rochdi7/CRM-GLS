<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Cheque;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Services\CaisseProvisioner;
use App\Services\Import\Concerns\TracksBatchProgress;
use App\Services\Import\Contracts\Importer;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportResult;
use App\Services\Import\Exceptions\ImportCellParseException;
use App\Services\Import\Support\CellNormalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
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
    use TracksBatchProgress;

    private const string LEGACY_SOURCE = 'ancien-crm';

    private const array EXPECTED_COLUMNS = ['Réf.', 'Élève / Payeur', 'Type', 'Montant', 'Méthode', 'Frais', 'Date', 'Opérateur'];

    private const string TYPE_REGLEMENT = 'Réglement';

    /** resolution key => French label, used to explain exactly what a row is still missing. */
    private const array REQUIRED_RESOLUTION_LABELS = [
        'student_id' => 'étudiant',
        'agent_id' => 'opérateur',
        'caisse_id' => 'caisse',
    ];

    /** ImportRow rows are buffered and mass-inserted every N rows — real exports run into the thousands of rows, and one create() call per row (Eloquent events + a round-trip each) was a large part of the 30s timeout. */
    private const int INSERT_BUFFER_SIZE = 500;

    /** File label => Encaissement::METHODE_* — "Virement bancaire" is NOT a literal match. */
    private const array METHODE_MAP = [
        'Espèces' => Encaissement::METHODE_ESPECES,
        'TPE' => Encaissement::METHODE_TPE,
        'Virement bancaire' => Encaissement::METHODE_VIREMENT,
        'Chèque' => Encaissement::METHODE_CHEQUE,
        // The legacy CRM writes "Chéque" (e-acute) — a misspelling of
        // "Chèque". Both map to the same method; without this the whole
        // cheque column lands in ERREUR as an unknown méthode.
        'Chéque' => Encaissement::METHODE_CHEQUE,
    ];

    /**
     * Per-analyze()-call caches, rebuilt fresh every call — real exports can
     * be thousands of rows (the same student paying many times over a
     * year), so resolving "which student"/"which fee"/"which caisse"/
     * "already imported?" per row against the DB was timing out (30s
     * execution limit) on real files. Preloading once and matching in
     * memory turns O(rows) queries into O(1).
     *
     * @var array<string, int>|null normalized full name => students.id, scoped to the current batch's centre
     */
    private ?array $studentsByNormalizedName = null;

    /** @var array<int, int> employees.id => caisses.id, memoized within one analyze() call */
    private array $caisseIdByEmployeeId = [];

    /** @var array<int, Inscription>|null student_id => their one active inscription in this batch's centre+année, preloaded once */
    private ?array $activeInscriptionByStudentId = null;

    /**
     * Mirrors ImportContext::$includeInactiveInscriptions for the duration
     * of one analyze() run, so the "no inscription" message can say which
     * statuts were actually searched rather than guessing.
     */
    private bool $includeInactiveInscriptions = false;

    /** @var array<int, Collection<int, InscriptionFee>>|null inscription_id => its fee lines, preloaded once */
    private ?array $feesByInscriptionId = null;

    /** @var array<string, true>|null every legacy_ref already imported before this batch started, preloaded once */
    private ?array $existingEncaissementLegacyRefs = null;

    /**
     * @var array<string, true>|null fallback dedupe composite keys
     * ("studentId|montant|date|methode|inscriptionFeeId") of every existing
     * encaissement, preloaded once — used when a row has no legacy_ref.
     */
    private ?array $existingEncaissementCompositeKeys = null;

    /** legacy_refs seen so far WITHIN this file — catches duplicate rows inside the same upload, which the preloaded DB set can't see. */
    private array $legacyRefsSeenThisFile = [];

    /** composite keys (see $existingEncaissementCompositeKeys) seen so far WITHIN this file. */
    private array $compositeKeysSeenThisFile = [];

    /** @var array<int, array<string, mixed>> buffered ImportRow attribute arrays, flushed every INSERT_BUFFER_SIZE rows */
    private array $pendingRows = [];

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
        $this->caisseIdByEmployeeId = [];
        $this->legacyRefsSeenThisFile = [];
        $this->compositeKeysSeenThisFile = [];
        $this->pendingRows = [];
        $this->studentsByNormalizedName = $this->preloadStudents($context->etablissementId);
        $this->includeInactiveInscriptions = $context->includeInactiveInscriptions;
        $this->activeInscriptionByStudentId = $this->preloadActiveInscriptions($context);
        $this->feesByInscriptionId = $this->preloadFees(array_map(
            fn (Inscription $inscription): int => $inscription->id,
            $this->activeInscriptionByStudentId
        ));
        $this->existingEncaissementLegacyRefs = $this->preloadExistingLegacyRefs();
        $this->existingEncaissementCompositeKeys = $this->preloadExistingCompositeKeys();

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
                $this->analyzeRow($batch, $rowNumber, $rawRow, $operateurEmployees);
            }

            $this->flushPendingRows();

            $batch->update(['total_rows' => $total]);

            return $batch->fresh();
        });
    }

    public function commit(ImportBatch $batch, array $selectedRowIds, Employee $importingAdmin, int $chunkSize = 5): ImportResult
    {
        // Chronological order keeps inscription_fees.statut transitions
        // (Non payé -> Payé partiellement -> Payé) readable across rows
        // targeting the same fee — the final caisse solde is order-
        // independent (a pure sum) either way. Sorting the FULL eligible
        // set before slicing (rather than sorting each chunk on its own)
        // keeps that ordering correct across chunked commit() calls, since
        // each call only ever re-sorts whatever is still eligible — the
        // same stable ordering, just re-computed on a shrinking set.
        $eligible = $batch->rows()
            ->whereIn('id', $selectedRowIds)
            ->whereIn('status', ImportRow::SELECTABLE_STATUTS)
            ->get()
            ->sortBy(fn (ImportRow $row) => [$row->raw['date_paiement'] ?? '', $row->source_row_number])
            ->values();

        $totalEligible = $eligible->count();
        $rows = $eligible->take($chunkSize);
        $remaining = max(0, $totalEligible - $rows->count());

        $inserted = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($rows as $row) {
            try {
                $wasInserted = DB::transaction(function () use ($row, $batch): bool {
                    $data = $row->raw;
                    $resolution = $row->resolution ?? [];

                    // A CONFLIT row reaches commit() only once the operator
                    // has resolved it on the preview screen. Until then its
                    // resolution carries candidates, not decisions — fail it
                    // with a message naming the missing link instead of
                    // dereferencing keys that were never written.
                    $this->assertResolved($resolution, (bool) ($data['is_avance'] ?? false));

                    // Re-check at write time, not just against the snapshot
                    // taken during analyze(): the batch may have been
                    // committed already, or another import may have written
                    // the same payment in between. The DB's unique index on
                    // legacy_ref is the real backstop — this turns that
                    // constraint violation into a row marked "déjà importé"
                    // instead of a raw SQL error in the failure list.
                    if ($this->alreadyImported($row)) {
                        $row->update([
                            'status' => ImportRow::STATUT_DOUBLON,
                            'errors' => [[
                                'field' => 'legacy_ref',
                                'code' => 'already_imported',
                                'message' => 'Encaissement déjà importé — ligne ignorée.',
                            ]],
                        ]);

                        return false;
                    }

                    $agent = Employee::findOrFail($resolution['agent_id']);
                    $action = app(EnregistrerEncaissement::class);

                    // A Chèque payment must exist in the Chèques module too —
                    // that is where cheques are tracked and followed up. The
                    // export carries no cheque number, so one is not invented:
                    // the record is created already "Encaissé" (the money was
                    // received) and says so in its note.
                    $cheque = $data['methode'] === Encaissement::METHODE_CHEQUE
                        ? $this->createChequeRecord($row, $data, $resolution, $agent, $batch)
                        : null;

                    $encaissement = $action->handle([
                        'student_id' => $resolution['student_id'],
                        'inscription_fee_id' => $resolution['inscription_fee_id'] ?? null,
                        'cheque_id' => $cheque?->id,
                        'numero_cheque' => $cheque?->numero_cheque,
                        'banque' => $cheque?->banque,
                        'montant' => $data['montant'],
                        'methode' => $data['methode'],
                        'date_paiement' => $data['date_paiement'],
                        'caisse_id' => $resolution['caisse_id'],
                        'note' => $this->importNote($row, $data),
                        'legacy_ref' => $row->legacy_ref,
                        'legacy_source' => self::LEGACY_SOURCE,
                    ], $agent);

                    $row->update([
                        'status' => ImportRow::STATUT_INSERE,
                        'created_model_type' => Encaissement::class,
                        'created_model_id' => $encaissement->id,
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
     * Every link an encaissement needs before it can be written. A row
     * still missing one is unresolved (student introuvable, frais non
     * rapproché, opérateur non associé...) and must never reach
     * EnregistrerEncaissement.
     *
     * @param  array<string, mixed>  $resolution
     */
    /**
     * Whether this row's payment is already in the database right now.
     * analyze() dedupes against a snapshot; by commit() time that snapshot
     * can be stale (batch re-committed, concurrent import), so the check is
     * repeated against live data before writing.
     */
    /**
     * Creates the Chèques-module record behind an imported cheque payment.
     *
     * The export has no numéro/banque/échéance columns, so nothing is
     * invented: the numéro records that it is missing (traceable back to the
     * legacy Réf.) and the cheque is stored as Encaissé, since the payment
     * it belongs to was actually received in the old CRM.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $resolution
     */
    private function createChequeRecord(ImportRow $row, array $data, array $resolution, Employee $agent, ImportBatch $batch): Cheque
    {
        return Cheque::create([
            'reference' => ReferenceGenerator::make('CHQ', 'cheques'),
            'source' => Cheque::SOURCE_ETUDIANT,
            'student_id' => $resolution['student_id'],
            'numero_cheque' => sprintf('IMPORT-%s', $row->legacy_ref ?: $row->id),
            'montant' => $data['montant'],
            'date_reception' => $data['date_paiement'],
            'type' => Cheque::TYPE_A_DEPOSER,
            'statut' => Cheque::STATUT_ENCAISSE,
            'note' => "Importé de l'ancien CRM — numéro de chèque absent de l'export.",
            'etablissement_id' => $batch->etablissement_id,
            'agent_id' => $agent->id,
        ]);
    }

    /**
     * A Chèque payment now gets a real Chèques-module record (see
     * createChequeRecord); the note flags that its numéro was absent from the
     * export, so nobody mistakes the placeholder for a real cheque number.
     *
     * @param  array<string, mixed>  $data
     */
    private function importNote(ImportRow $row, array $data): string
    {
        $note = sprintf("Importé de l'ancien CRM (Réf. %s)", $row->legacy_ref);

        $loose = $row->resolution['fee_matched_loosely'] ?? null;

        if ($loose !== null) {
            $note .= sprintf(
                ' — frais "%s" rattaché à "%s" (libellé absent de cette inscription)',
                $loose['requested'],
                $loose['attached_to']
            );
        }

        if ($data['is_avance'] ?? false) {
            $note .= " — avance (aucun frais indiqué dans l'export)";
        }

        if (($data['methode'] ?? null) === Encaissement::METHODE_CHEQUE) {
            $note .= " — chèque enregistré sans numéro (absent de l'export)";
        }

        return $note;
    }

    private function alreadyImported(ImportRow $row): bool
    {
        if ($row->legacy_ref !== null && $row->legacy_ref !== '') {
            return Encaissement::query()->where('legacy_ref', $row->legacy_ref)->exists();
        }

        $data = $row->raw;
        $resolution = $row->resolution ?? [];

        return Encaissement::query()
            ->where('student_id', $resolution['student_id'])
            ->where('inscription_fee_id', $resolution['inscription_fee_id'])
            ->where('montant', $data['montant'])
            ->where('date_paiement', $data['date_paiement'])
            ->where('methode', $data['methode'])
            ->exists();
    }

    private function assertResolved(array $resolution, bool $isAvance = false): void
    {
        // Report the CAUSE, not the symptom. A missing inscription_fee_id is
        // almost always "this student has no inscription", not "the fee is
        // named differently" — saying "frais à rapprocher" sent people
        // hunting through fee labels for a problem that isn't there.
        if (($resolution['student_id'] ?? null) === null) {
            throw new \RuntimeException(
                "Étudiant introuvable dans ce centre — vérifier l'orthographe du payeur ou importer d'abord les étudiants."
            );
        }

        // An avance is money received with no fee attached yet
        // (Encaissement::isAvance()), so a null inscription_fee_id is the
        // correct shape for it, not a failure.
        if (! $isAvance && ($resolution['inscription_fee_id'] ?? null) === null) {
            throw new \RuntimeException(
                "Cet étudiant n'a aucune inscription utilisable pour le centre/année de ce lot — importer d'abord les inscriptions, ou relancer l'analyse en acceptant les inscriptions annulées / changement."
            );
        }

        $missing = [];

        foreach (self::REQUIRED_RESOLUTION_LABELS as $key => $label) {
            if (($resolution[$key] ?? null) === null) {
                $missing[] = $label;
            }
        }

        if ($missing !== []) {
            throw new \RuntimeException(sprintf(
                'Ligne non résolue — %s à rapprocher avant insertion.',
                implode(', ', $missing)
            ));
        }
    }


    /** @param array<string, mixed> $rawRow */
    private function analyzeRow(ImportBatch $batch, int $rowNumber, array $rawRow, array $operateurEmployees): void
    {
        $legacyRef = CellNormalizer::text($rawRow['Réf.'] ?? '');
        $type = CellNormalizer::text($rawRow['Type'] ?? '');
        $payeurRaw = CellNormalizer::text($rawRow['Élève / Payeur'] ?? '');
        $methodeLabel = CellNormalizer::text($rawRow['Méthode'] ?? '');
        $fraisLabel = CellNormalizer::text($rawRow['Frais'] ?? '');
        $isAvance = $fraisLabel === '';
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
            'is_avance' => $isAvance,
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
        $looseFeeMatch = null;

        // The caisse depends only on the mapped opérateur, so it resolves
        // regardless of how the payer/frais lookups turn out — a row that
        // conflicts on the student must still keep its valid caisse.
        if ($agentId !== null) {
            $caisseId = $this->resolveCaisseFor((int) $agentId);
        }

        if (! $payerAmbiguous && $errors === [] && $parseErrors === []) {
            $studentResolution = $this->resolveStudent($payeurName);
            $studentId = $studentResolution['student_id'];
            $conflicts = [...$conflicts, ...$studentResolution['conflicts']];

            // No Frais label at all = an avance: money received now, to be
            // allocated to a fee later (Encaissement::isAvance()). It is a
            // valid payment, not an unresolved one, so no fee lookup runs.
            if ($studentId !== null && ! $isAvance) {
                $feeResolution = $this->resolveInscriptionFee($studentId, $fraisLabel);
                $inscriptionFeeId = $feeResolution['inscription_fee_id'];
                $candidateFees = $feeResolution['candidates'];
                $looseFeeMatch = $feeResolution['fee_matched_loosely'] ?? null;
                $conflicts = [...$conflicts, ...$feeResolution['conflicts']];
            }
        }

        $duplicateReason = $this->duplicateReason($legacyRef, $studentId, $montant, $datePaiement, $methode, $inscriptionFeeId, $payeurName);

        if ($duplicateReason !== null) {
            $this->pushPendingRow($batch, $rowNumber, [
                'raw' => $raw,
                'status' => ImportRow::STATUT_DOUBLON,
                'errors' => [$duplicateReason],
                'legacy_ref' => $legacyRef ?: null,
                'resolution' => null,
            ]);

            return;
        }

        if ($legacyRef !== '') {
            $this->legacyRefsSeenThisFile[$legacyRef] = true;
        } elseif ($studentId !== null && $montant !== null && $datePaiement !== null && $methode !== null && $inscriptionFeeId !== null) {
            $this->compositeKeysSeenThisFile[$this->compositeKey($studentId, $montant, $datePaiement, $methode, $inscriptionFeeId)] = true;
        }

        $allErrors = [...$parseErrors, ...$errors];

        if ($allErrors !== []) {
            $this->pushPendingRow($batch, $rowNumber, [
                'raw' => $raw,
                'status' => ImportRow::STATUT_ERREUR,
                'errors' => $allErrors,
                'legacy_ref' => $legacyRef ?: null,
            ]);

            return;
        }

        if ($conflicts !== []) {
            $this->pushPendingRow($batch, $rowNumber, [
                'raw' => $raw,
                'status' => ImportRow::STATUT_CONFLIT,
                'errors' => $conflicts,
                'legacy_ref' => $legacyRef ?: null,
                'resolution' => [
                    'student_id' => $studentId,
                    // Whatever DID resolve is kept, not just the student:
                    // a row conflicting on frais alone still has a valid
                    // opérateur/caisse, and dropping them made the commit
                    // guard report them as missing too.
                    'inscription_fee_id' => $inscriptionFeeId,
                    'agent_id' => $agentId,
                    'caisse_id' => $caisseId,
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

        $this->pushPendingRow($batch, $rowNumber, [
            'raw' => $raw,
            'status' => $validationErrors === [] ? ImportRow::STATUT_NOUVEAU : ImportRow::STATUT_ERREUR,
            'errors' => $validationErrors === [] ? null : $validationErrors,
            'legacy_ref' => $legacyRef ?: null,
            'resolution' => [
                'student_id' => $studentId,
                'inscription_fee_id' => $inscriptionFeeId,
                'agent_id' => $agentId,
                'caisse_id' => $caisseId,
                'fee_matched_loosely' => $looseFeeMatch,
            ],
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

    /** @return array<string, int> raw Opérateur label => employees.id */
    private function resolveOperateurEmployees(ImportContext $context): array
    {
        return array_map('intval', $context->operateurMapping);
    }

    private function resolveCaisseFor(int $employeeId): int
    {
        if (isset($this->caisseIdByEmployeeId[$employeeId])) {
            return $this->caisseIdByEmployeeId[$employeeId];
        }

        $employee = Employee::findOrFail($employeeId);
        $caisse = $employee->caisses()->first() ?? $this->caisseProvisioner->provisionFor($employee);

        return $this->caisseIdByEmployeeId[$employeeId] = $caisse->id;
    }

    /**
     * Preloads every student of the batch's centre once, keyed by their
     * normalized "prenom nom" — same normalization as the old per-row
     * whereRaw(regexp_replace(...)) query, just computed in PHP instead of
     * re-run against Postgres for every single row.
     *
     * @return array<string, int> normalized full name => students.id (a name matched by more than one student maps to -1, meaning "ambiguous")
     */
    private function preloadStudents(int $etablissementId): array
    {
        $index = [];

        Student::query()
            ->where('etablissement_id', $etablissementId)
            ->get(['id', 'prenom', 'nom'])
            ->each(function (Student $student) use (&$index): void {
                $key = mb_strtolower(CellNormalizer::text("{$student->prenom} {$student->nom}"));
                $index[$key] = array_key_exists($key, $index) ? -1 : $student->id;
            });

        return $index;
    }

    /**
     * One inscription per student in the batch's centre+année, preloaded
     * once instead of one query per row.
     *
     * By DEFAULT only ACTIVE inscriptions are eligible — attaching money to
     * a cancelled enrolment is not something that should happen silently.
     *
     * When the operator ticks "inclure les inscriptions annulées /
     * changement" on the upload screen, cancelled ("Annulée") and changed
     * ("Changement" — what the old CRM calls "Archivée") inscriptions are
     * accepted as a fallback too: those students really did pay during the
     * year, and refusing their payments drops historical revenue and leaves
     * the caisse totals short. Active still wins wherever both exist.
     *
     * @return array<int, Inscription> student_id => Inscription
     */
    private function preloadActiveInscriptions(ImportContext $context): array
    {
        $query = Inscription::query()
            ->where('etablissement_id', $context->etablissementId)
            ->where('annee_scolaire_id', $context->anneeScolaireId);

        if (! $context->includeInactiveInscriptions) {
            // Default: only a live enrolment may receive money.
            $query->where('statut', Inscription::STATUT_ACTIVE);
        }

        return $query
            // Active always wins when the fallback is on and a student has
            // both — the money belongs to the live enrolment.
            ->orderByRaw('case when statut = ? then 0 else 1 end', [Inscription::STATUT_ACTIVE])
            ->orderByDesc('date_inscription')
            ->get(['id', 'student_id', 'statut'])
            // keyBy keeps the LAST occurrence, so reverse first to make the
            // highest-priority (Active, most recent) row the one that wins.
            ->reverse()
            ->keyBy('student_id')
            ->all();
    }

    /**
     * Every fee line of the preloaded active inscriptions, in one query
     * instead of one per student.
     *
     * @param  array<int, int>  $inscriptionIds
     * @return array<int, Collection<int, InscriptionFee>> inscription_id => its fees
     */
    private function preloadFees(array $inscriptionIds): array
    {
        if ($inscriptionIds === []) {
            return [];
        }

        return InscriptionFee::query()
            ->whereIn('inscription_id', $inscriptionIds)
            ->get(['id', 'inscription_id', 'nom', 'montant', 'statut'])
            ->groupBy('inscription_id')
            ->all();
    }

    /** @return array<string, true> */
    private function preloadExistingLegacyRefs(): array
    {
        return Encaissement::query()
            ->whereNotNull('legacy_ref')
            ->pluck('legacy_ref')
            ->flip()
            ->map(fn () => true)
            ->all();
    }

    /**
     * Fallback dedupe key when a row has no legacy_ref — preloaded once
     * (only encaissements that actually have an inscription_fee_id, since
     * that's the only shape this fallback ever matches against).
     *
     * @return array<string, true>
     */
    private function preloadExistingCompositeKeys(): array
    {
        $index = [];

        Encaissement::query()
            ->whereNotNull('inscription_fee_id')
            ->get(['student_id', 'montant', 'date_paiement', 'methode', 'inscription_fee_id'])
            ->each(function (Encaissement $e) use (&$index): void {
                $index[$this->compositeKey(
                    $e->student_id,
                    (string) $e->montant,
                    $e->date_paiement->toDateString(),
                    $e->methode,
                    $e->inscription_fee_id
                )] = true;
            });

        return $index;
    }

    private function compositeKey(int $studentId, string $montant, string $datePaiement, string $methode, int $inscriptionFeeId): string
    {
        return "{$studentId}|{$montant}|{$datePaiement}|{$methode}|{$inscriptionFeeId}";
    }

    /**
     * @return array{student_id: ?int, conflicts: array<int, array{field: string, code: string, message: string}>}
     */
    private function resolveStudent(string $payeurName): array
    {
        if ($payeurName === '') {
            return ['student_id' => null, 'conflicts' => [
                ['field' => 'student_id', 'code' => 'student_not_found', 'message' => 'Nom du payeur vide.'],
            ]];
        }

        $normalizedTarget = mb_strtolower(CellNormalizer::text($payeurName));
        $match = $this->studentsByNormalizedName[$normalizedTarget] ?? null;

        if ($match === -1) {
            return ['student_id' => null, 'conflicts' => [
                ['field' => 'student_id', 'code' => 'ambiguous_student', 'message' => sprintf(
                    'Plusieurs étudiants correspondent à "%s".',
                    $payeurName
                )],
            ]];
        }

        if ($match !== null) {
            return ['student_id' => $match, 'conflicts' => []];
        }

        return ['student_id' => null, 'conflicts' => [
            ['field' => 'student_id', 'code' => 'student_not_found', 'message' => sprintf('Étudiant introuvable : "%s".', $payeurName)],
        ]];
    }

    /**
     * Finds the student's ACTIVE inscription strictly within the batch's
     * Centre+Année (never another year/centre as a fallback — the
     * preloaded index is already scoped that way), then matches the file's
     * Frais label against THAT inscription's own real fee lines. Never
     * creates a frais/inscription_fees row.
     *
     * @return array{inscription_fee_id: ?int, candidates: array<int, array{id: int, nom: string, montant: string, statut: string}>, conflicts: array<int, array{field: string, code: string, message: string}>}
     */
    private function resolveInscriptionFee(int $studentId, string $fraisLabel): array
    {
        $inscription = $this->activeInscriptionByStudentId[$studentId] ?? null;

        if ($inscription === null) {
            return ['inscription_fee_id' => null, 'candidates' => [], 'conflicts' => [
                ['field' => 'inscription_fee_id', 'code' => 'no_inscription', 'message' => $this->includeInactiveInscriptions
                    ? "Aucune inscription pour cet étudiant sur le centre/année sélectionné (ni active, ni annulée, ni changement) — importer d'abord les inscriptions."
                    : "Aucune inscription active pour cet étudiant sur le centre/année sélectionné. Si son inscription a été annulée ou changée, cochez « Accepter les inscriptions annulées / changement » avant d'analyser."],
            ]];
        }

        $fees = $this->feesByInscriptionId[$inscription->id] ?? collect();
        $normalizedTarget = mb_strtolower(CellNormalizer::text($fraisLabel));

        $exact = $fees->first(fn (InscriptionFee $fee) => mb_strtolower(CellNormalizer::text($fee->nom)) === $normalizedTarget);

        if ($exact !== null) {
            return ['inscription_fee_id' => $exact->id, 'candidates' => [], 'conflicts' => []];
        }

        $candidates = $fees->map(fn (InscriptionFee $fee): array => [
            'id' => $fee->id,
            'nom' => $fee->nom,
            'montant' => (string) $fee->montant,
            'statut' => $fee->statut,
        ])->all();

        // The student HAS an active inscription, the label just doesn't match
        // one of its lines by name (legacy wording drift, or one payment
        // covering several fees: "Frais A, Frais B"). Rather than blocking a
        // payment we know belongs to this inscription, attach it to the
        // inscription's first still-unpaid line so the money lands on the
        // right registration and the balance stays correct.
        //
        // Deliberately the FIRST UNPAID line, not just any line: dropping it
        // on an already-settled fee would flip a correct "Payé" into an
        // overpayment. When everything is already paid there is nothing
        // sensible to attach to, so that stays a conflict for a human.
        $fallback = $fees->first(
            fn (InscriptionFee $fee): bool => $fee->statut !== InscriptionFee::STATUT_PAYE
        );

        if ($fallback !== null) {
            return [
                'inscription_fee_id' => $fallback->id,
                'candidates' => $candidates,
                'conflicts' => [],
                'fee_matched_loosely' => [
                    'requested' => $fraisLabel,
                    'attached_to' => $fallback->nom,
                ],
            ];
        }

        return ['inscription_fee_id' => null, 'candidates' => $candidates, 'conflicts' => [
            ['field' => 'inscription_fee_id', 'code' => 'fee_not_matched', 'message' => sprintf(
                'Le libellé de frais "%s" ne correspond à aucune ligne, et toutes les lignes de cette inscription sont déjà payées.',
                $fraisLabel
            )],
        ]];
    }

    /**
     * Whether this row is a re-import of an already-processed payment.
     * Primary key: legacy_ref, checked against both what existed in the DB
     * before this run AND every legacy_ref already seen earlier in THIS
     * SAME file (two rows in one upload sharing a Réf. are still a
     * duplicate, but a DB-only snapshot can't see that). Fallback, when a
     * row has no legacy_ref: the same student+montant+date+méthode+fee
     * composite key, checked the same two ways.
     */
    /**
     * Returns WHY the payment is a duplicate, or null when it is not one.
     *
     * Skipped payments are the ones an operator most needs explained — an
     * unexplained "Ignorées" count on a money import is indistinguishable
     * from lost revenue.
     *
     * @return array{field: string, code: string, message: string}|null
     */
    private function duplicateReason(string $legacyRef, ?int $studentId, ?string $montant, ?string $datePaiement, ?string $methode, ?int $inscriptionFeeId, string $payeur = ''): ?array
    {
        if ($legacyRef !== '') {
            if (isset($this->existingEncaissementLegacyRefs[$legacyRef])) {
                return [
                    'field' => 'legacy_ref',
                    'code' => 'already_in_database',
                    'message' => sprintf(
                        "Déjà importé : %sla réf. %s existe déjà (import précédent) — le montant n'a pas été compté deux fois.",
                        $payeur !== '' ? $payeur.' — ' : '',
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
                        $payeur !== '' ? $payeur.' — ' : '',
                        $legacyRef
                    ),
                ];
            }

            return null;
        }

        if ($studentId === null || $montant === null || $datePaiement === null || $methode === null || $inscriptionFeeId === null) {
            return null;
        }

        $key = $this->compositeKey($studentId, $montant, $datePaiement, $methode, $inscriptionFeeId);

        if (isset($this->existingEncaissementCompositeKeys[$key])) {
            return [
                'field' => 'montant',
                'code' => 'already_in_database',
                'message' => sprintf(
                    'Déjà importé : un paiement identique (%s DH, %s, %s) existe déjà pour %s et ce frais.',
                    $montant,
                    $methode,
                    $datePaiement,
                    $payeur !== '' ? $payeur : 'cet étudiant'
                ),
            ];
        }

        if (isset($this->compositeKeysSeenThisFile[$key])) {
            return [
                'field' => 'montant',
                'code' => 'duplicate_in_file',
                'message' => sprintf(
                    'Doublon dans le fichier : %s— même frais, même montant (%s DH) et même date (%s) sur une ligne précédente.',
                    $payeur !== '' ? $payeur.' ' : 'même étudiant ',
                    $montant,
                    $datePaiement
                ),
            ];
        }

        return null;
    }
}
