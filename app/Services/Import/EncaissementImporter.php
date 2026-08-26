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
use App\Domain\Finance\Support\CaisseResolver;
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

    /**
     * Legacy Frais label (lower-case) => the catalogue fee it really is.
     *
     * GLS charges exactly TWO inscription fees — « Frais d'inscription
     * A1/A2/B1 » (300 DH) and « Frais d'inscription B2 » (200 DH) — but the
     * old CRM spelled the first one four different ways over the years.
     * Without this map 1 793 payments matched no line by name and fell to
     * the loose fallback, which piled them onto whatever line was still
     * unpaid (26/08/2026). Monthly fees are unaffected: they already match
     * exactly.
     */
    private const array FRAIS_ALIASES = [
        "frais d'inscription" => "Frais d'inscription A1/A2/B1",
        "frais d'inscription 1" => "Frais d'inscription A1/A2/B1",
        "frais d'inscription a1" => "Frais d'inscription A1/A2/B1",
        "frais d'inscription a2" => "Frais d'inscription A1/A2/B1",
        "frais d'inscription b1" => "Frais d'inscription A1/A2/B1",
        "frais d'inscription 2" => "Frais d'inscription B2",
    ];

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
     * @var array<string, list<int>>|null normalized full name => every students.id carrying it (homonyms ⇒ several), scoped to the current batch's centre
     */
    private ?array $studentsByNormalizedName = null;

    /** @var array<int, int> employees.id => caisses.id, memoized within one analyze() call */
    private array $caisseIdByEmployeeId = [];

    /** @var array<int, list<Inscription>>|null student_id => every inscription they hold in this batch's CENTRE (all années), best candidate first, preloaded once */
    private ?array $activeInscriptionByStudentId = null;

    /**
     * Mirrors ImportContext::$includeInactiveInscriptions for the duration
     * of one analyze() run, so the "no inscription" message can say which
     * statuts were actually searched rather than guessing.
     */
    private bool $includeInactiveInscriptions = false;

    /**
     * Import-only override: when set, EVERY imported payment lands in this
     * caisse whatever its méthode (see ImportContext::$caisseForceeId).
     * Null = the normal per-méthode routing of CaisseResolver.
     */
    private ?int $caisseForceeId = null;

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
        private readonly CaisseResolver $caisseResolver,
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
        $this->caisseForceeId = $context->caisseForceeId;
        $this->activeInscriptionByStudentId = $this->preloadActiveInscriptions($context);
        $this->feesByInscriptionId = $this->preloadFees(array_map(
            fn (Inscription $inscription): int => $inscription->id,
            // Flattened: the index now holds EVERY inscription a student
            // has in this centre (see preloadActiveInscriptions), so the
            // fee preload must cover all of them, not one per student.
            array_merge(...array_values($this->activeInscriptionByStudentId) ?: [[]])
        ));
        $this->existingEncaissementLegacyRefs = $this->preloadExistingLegacyRefs($context->etablissementId);
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
                    if ($this->alreadyImported($row, $batch)) {
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

                    // The legacy inscriptions export carries no fee amounts,
                    // so imported fee lines start at 0.00 and every payment
                    // against them showed "Montant 0.00 / Payé 1300.00".
                    // The payments export is the only place the real amount
                    // exists, so an imported fee priced at zero is back-filled
                    // from the money that actually arrived. Import-only: a
                    // fee created through normal CRUD always has its montant
                    // set by the user and is never touched here.
                    // ONE payment settling SEVERAL fees (comma-separated
                    // Frais cell) becomes one encaissement PER fee, so each
                    // line settles for what it is really owed instead of one
                    // line absorbing the whole amount. A single-fee row keeps
                    // exactly its previous single-write behaviour.
                    $allocations = $this->allocateAcrossFees(
                        $resolution['split_fee_ids'] ?? null,
                        // A loosely-matched row picks its line HERE, against
                        // live balances, so consecutive payments of one
                        // student walk down the unpaid lines instead of all
                        // piling on the first.
                        $this->pickLooseFee($resolution)
                            ?? ($resolution['inscription_fee_id'] ?? null),
                        (string) $data['montant'],
                    );

                    $encaissement = null;

                    foreach ($allocations as $index => [$feeId, $montant]) {
                        $this->backfillImportedFeeAmount($feeId, $montant);

                        $created = $action->handle([
                            'student_id' => $resolution['student_id'],
                            'inscription_fee_id' => $feeId,
                            'cheque_id' => $cheque?->id,
                            'numero_cheque' => $cheque?->numero_cheque,
                            'banque' => $cheque?->banque,
                            'montant' => $montant,
                            'methode' => $data['methode'],
                            'date_paiement' => $data['date_paiement'],
                            'caisse_id' => $resolution['caisse_id'],
                            'note' => $this->importNote($row, $data, $feeId),
                            // Legacy refs restart at P1 in every centre's old
                            // CRM — the batch centre is the dedupe scope. A
                            // split keeps the ref on the FIRST part only: it
                            // is unique per centre, and the parts are one
                            // payment, so the others carry none.
                            'etablissement_id' => $batch->etablissement_id,
                            'legacy_ref' => $index === 0 ? $row->legacy_ref : null,
                            'legacy_source' => self::LEGACY_SOURCE,
                        ], $agent);

                        $encaissement ??= $created;
                    }

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
     * Prices an imported fee line from the payment that lands on it.
     *
     * The legacy inscriptions export has no amount columns, so
     * InscriptionImporter can only create fee lines at montant 0.00 (they
     * come from the group's group_frais pivot, which is itself created at
     * zero). The payments export is where the real figures live. Without
     * this the inscription detail page shows every line as
     * "Montant 0.00 / Payé 1300.00", and the inscription's total dû stays
     * at zero however much was actually paid.
     *
     * Deliberately narrow, so this can never disturb normal CRUD:
     *  - only fee lines still priced at exactly 0.00 are touched;
     *  - only fees belonging to an inscription that came from an import
     *    (legacy_source set) — a fee a user created and left at zero on
     *    purpose is left alone;
     *  - montant_initial is set alongside montant so no phantom remise
     *    appears (InscriptionFee::computeMontant()'s inputs stay coherent);
     *  - the parent inscription's montant_total is recomputed from its own
     *    fee lines afterwards.
     *
     * Repeat payments against the same fee ADD to its amount, so a fee paid
     * in several instalments ends up priced at the full sum rather than at
     * whichever instalment happened to be committed first.
     */
    private function backfillImportedFeeAmount(?int $inscriptionFeeId, string $montant): void
    {
        if ($inscriptionFeeId === null) {
            return; // avance — no fee line to price.
        }

        $fee = InscriptionFee::query()
            ->with('inscription')
            ->find($inscriptionFeeId);

        if ($fee === null || $fee->inscription?->legacy_source === null) {
            return; // not an imported inscription — untouched.
        }

        $alreadyPaid = (float) Encaissement::query()
            ->where('inscription_fee_id', $fee->id)
            ->sum('montant');

        // Only ever price a line the import left at zero. Once it carries a
        // real amount (here or from the UI) it is authoritative.
        if ((float) $fee->montant > 0.0) {
            return;
        }

        $newMontant = round($alreadyPaid + (float) $montant, 2);

        $fee->update([
            'montant_initial' => $newMontant,
            'montant' => $newMontant,
            // Import-created lines start masked (see InscriptionImporter's
            // buildFeeLines) precisely so only the ones actually charged
            // surface. A payment proves this one was charged.
            'masque_le' => null,
        ]);

        $inscription = $fee->inscription;
        $inscription->update([
            'montant_total' => $inscription->fees()->sum('montant'),
        ]);
    }

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
    private function importNote(ImportRow $row, array $data, ?int $feeId = null): string
    {
        $note = sprintf("Importé de l'ancien CRM (Réf. %s)", $row->legacy_ref);

        $loose = $row->resolution['fee_matched_loosely'] ?? null;

        if ($loose !== null) {
            // The line is chosen at commit time against live balances
            // (pickLooseFee), so the note names whichever fee actually
            // received the money — not a guess made during analyze.
            $nom = $loose['attached_to']
                ?? ($feeId !== null ? InscriptionFee::find($feeId)?->nom : null);

            $note .= sprintf(
                ' — frais "%s" rattaché à "%s" (libellé absent de cette inscription)',
                $loose['requested'],
                $nom ?? '?'
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

    private function alreadyImported(ImportRow $row, ImportBatch $batch): bool
    {
        if ($row->legacy_ref !== null && $row->legacy_ref !== '') {
            return Encaissement::query()
                ->where('etablissement_id', $batch->etablissement_id)
                ->where('legacy_ref', $row->legacy_ref)
                ->exists();
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

        // A literal "-" is the export's "no value" marker in EVERY column
        // (same convention CellNormalizer::parseDate already honours), so a
        // "-" Frais cell means the money arrived without being allocated to
        // a fee — an avance, exactly like an empty cell. Treating "-" as a
        // real label made 62 payments (44 320 DH) fail to match and land on
        // an unrelated fee line via the loose fallback (26/08/2026).
        if ($fraisLabel === '-') {
            $fraisLabel = '';
        }

        $fraisLabel = $this->canonicalFraisLabel($fraisLabel);

        $isAvance = $fraisLabel === '';
        $operateurLabel = CellNormalizer::text($rawRow['Opérateur'] ?? '');

        $collapsed = CellNormalizer::collapseDoubledName($payeurRaw);
        $payeurName = $collapsed['value'];
        $payerAmbiguous = ! $collapsed['collapsed'];

        // Not a clean doubled name (written once, tripled, halves swapped, a
        // typo in the copy…): before giving up, look the text up against the
        // centre's real students — a SINGLE matching student is a fact, not
        // a guess (see matchPayeurAgainstStudents). Audit 24/08/2026: 58 of
        // 76 conflicts on a real Marrakech file were exactly this.
        if ($payerAmbiguous) {
            $matched = $this->matchPayeurAgainstStudents($payeurRaw);

            if ($matched !== null) {
                $payeurName = $matched;
                $payerAmbiguous = false;
            }
        }

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
        $splitFeeIds = null;
        $looseFeeIds = null;

        // The account depends only on the mapped opérateur + the method (and
        // the batch's centre for non-cash rows), so it resolves regardless
        // of how the payer/frais lookups turn out — a row that conflicts on
        // the student must still keep its valid caisse.
        if ($agentId !== null && $methode !== null) {
            $caisseId = $this->resolveCaisseFor((int) $agentId, $methode, (int) $batch->etablissement_id);
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
                $splitFeeIds = $feeResolution['split_fee_ids'] ?? null;
                $looseFeeIds = $feeResolution['loose_fee_ids'] ?? null;
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
                'split_fee_ids' => $splitFeeIds,
                'loose_fee_ids' => $looseFeeIds,
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

    /**
     * Espèces → the opérateur's own till; TPE/Chèque/Virement → the method
     * account of the CENTRE BEING IMPORTED (the batch is centre-scoped, so
     * this is known — unlike the operator's primary centre, which may be
     * another branch). Memoised per (employee, method, centre).
     */
    private function resolveCaisseFor(int $employeeId, string $methode, int $etablissementId): int
    {
        // A forced caisse short-circuits every routing rule — the whole
        // point of the option (legacy import only, never normal entry).
        if ($this->caisseForceeId !== null) {
            return $this->caisseForceeId;
        }

        $key = "{$employeeId}|{$methode}|{$etablissementId}";

        if (isset($this->caisseIdByEmployeeId[$key])) {
            return $this->caisseIdByEmployeeId[$key];
        }

        $caisse = $this->caisseResolver->resolveFor(Employee::findOrFail($employeeId), $methode, $etablissementId);

        return $this->caisseIdByEmployeeId[$key] = $caisse->id;
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
                $index[$key][] = $student->id;
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
     * the caisse totals short. Priority when a student holds several:
     * Active > Annulée > Changement (decision 24/08/2026 — the old CRM
     * exports Annulée and Archivée as separate files, and a student
     * appearing in both gets his historical payments on the ANNULÉE
     * record; the Changement copy carries no money).
     *
     * @return array<int, Inscription> student_id => Inscription
     */
    private function preloadActiveInscriptions(ImportContext $context): array
    {
        $query = Inscription::query()
            ->where('etablissement_id', $context->etablissementId);

        if (! $context->includeInactiveInscriptions) {
            // Default: only a live enrolment may receive money.
            $query->where('statut', Inscription::STATUT_ACTIVE);
        }

        return $query
            // The BATCH's année first, then the centre's other années.
            //
            // A legacy payments export is ONE file per centre covering every
            // année (the old CRM has no per-year payments export), while
            // inscriptions come as separate Active / Annulé / Archive files
            // that land in different années. Scoping this index to
            // $context->anneeScolaireId meant a payment whose inscription
            // lives in the OTHER année had nothing to attach to and was
            // refused "no_inscription" — 414 Marrakech rows (2 755 across
            // the seven centres, 2,1 M DH) on the 25/08/2026 import.
            //
            // The money is a fact of the enrolment, not of the année the
            // operator happened to pick on the upload screen, so a payment
            // now resolves against every inscription the student holds in
            // this CENTRE. Centre scoping is never relaxed.
            ->orderByRaw(
                'case when annee_scolaire_id = ? then 0 else 1 end',
                [$context->anneeScolaireId]
            )
            // Statut priority next (Active > Annulée > Changement), most
            // recent date only as the tie-breaker WITHIN one statut.
            ->orderByRaw(
                'case statut when ? then 0 when ? then 1 when ? then 2 else 3 end',
                [Inscription::STATUT_ACTIVE, Inscription::STATUT_ANNULEE, Inscription::STATUT_CHANGEMENT]
            )
            ->orderByDesc('date_inscription')
            ->get(['id', 'student_id', 'statut', 'annee_scolaire_id'])
            // Every inscription of the student, best candidate first — the
            // fee lookup walks them in order and takes the first whose own
            // lines match the payment's Frais label.
            ->groupBy('student_id')
            ->map(fn (Collection $inscriptions): array => $inscriptions->values()->all())
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

    /**
     * Scoped to the batch centre: every centre's old CRM numbers its
     * payments from P1, so Rabat's « P3 » and Marrakech's « P3 » are two
     * different payments (the 24/08/2026 Rabat import lost 4 297 rows to a
     * global check).
     *
     * @return array<string, true>
     */
    private function preloadExistingLegacyRefs(int $etablissementId): array
    {
        return Encaissement::query()
            ->where('etablissement_id', $etablissementId)
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
    /**
     * Fallback for an "Élève / Payeur" cell that is not a clean doubled
     * name. Every prefix / suffix split of its tokens — plus the whole text
     * — is looked up in the centre's student index ("prenom nom",
     * normalized). Exactly ONE distinct student across all candidates ⇒ that
     * student's name is returned; none, several, or a same-name twin among
     * the candidates ⇒ null, and the row stays a conflict for a human.
     *
     *   "AHMED AMIMI"                       → whole text matches once
     *   "AYA ZAHIR ZAHIR ZAHIR"             → prefix "AYA ZAHIR"
     *   "JENNATE FIRDAOUS FIRDAOUS JENNATE" → prefix "JENNATE FIRDAOUS"
     *   "AMMAR OUACHOCH AMMAR OUACHOUCH"    → whichever spelling exists
     */
    private function matchPayeurAgainstStudents(string $payeurRaw): ?string
    {
        $normalized = mb_strtolower(CellNormalizer::text($payeurRaw));
        $tokens = $normalized === '' ? [] : explode(' ', $normalized);
        $count = count($tokens);

        if ($count === 0) {
            return null;
        }

        $candidates = [$normalized];
        for ($i = 1; $i < $count; $i++) {
            $candidates[] = implode(' ', array_slice($tokens, 0, $i));
            $candidates[] = implode(' ', array_slice($tokens, $i));
        }

        $matches = [];
        foreach (array_unique($candidates) as $candidate) {
            $ids = $this->studentsByNormalizedName[$candidate] ?? [];

            if (count($ids) > 1) {
                // A same-name twin sits among the candidates — never pick
                // here; resolveStudent() decides whether the inscription
                // scope can still tell the twins apart.
                return null;
            }

            if ($ids !== []) {
                $matches[$ids[0]] = $candidate;
            }
        }

        return count($matches) === 1 ? reset($matches) : null;
    }

    private function resolveStudent(string $payeurName): array
    {
        if ($payeurName === '') {
            return ['student_id' => null, 'conflicts' => [
                ['field' => 'student_id', 'code' => 'student_not_found', 'message' => 'Nom du payeur vide.'],
            ]];
        }

        $normalizedTarget = mb_strtolower(CellNormalizer::text($payeurName));
        $matches = $this->studentsByNormalizedName[$normalizedTarget] ?? [];

        if (count($matches) > 1) {
            // Homonyms (two students with the same name in the centre —
            // Casablanca has 19 such names, 171 payment rows). A payment
            // can only attach to an inscription of the batch's centre+année,
            // so when exactly ONE of the homonyms holds such an inscription
            // the payer is that one; the file carries nothing else (no
            // phone, no ref) to tell them apart. Still ambiguous otherwise.
            $enrolled = array_values(array_filter(
                $matches,
                fn (int $id): bool => isset($this->activeInscriptionByStudentId[$id]),
            ));

            if (count($enrolled) === 1) {
                return ['student_id' => $enrolled[0], 'conflicts' => []];
            }

            return ['student_id' => null, 'conflicts' => [
                ['field' => 'student_id', 'code' => 'ambiguous_student', 'message' => sprintf(
                    'Plusieurs étudiants correspondent à "%s".',
                    $payeurName
                )],
            ]];
        }

        if ($matches !== []) {
            return ['student_id' => $matches[0], 'conflicts' => []];
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
    /**
     * Rewrites a legacy Frais label to the catalogue name it really means
     * (see FRAIS_ALIASES). Handles the comma-separated multi-fee cell too,
     * part by part, so "Frais d'inscription 1, Frais de Juin" resolves as
     * a normal split. A label with no alias is returned untouched.
     */
    private function canonicalFraisLabel(string $label): string
    {
        if ($label === '') {
            return '';
        }

        $parts = array_map(
            function (string $part): string {
                $part = CellNormalizer::text($part);

                return self::FRAIS_ALIASES[mb_strtolower($part)] ?? $part;
            },
            explode(',', $label)
        );

        return implode(', ', array_filter($parts, fn (string $p): bool => $p !== ''));
    }

    /**
     * Picks the fee line a loosely-matched payment lands on, at COMMIT time
     * against LIVE balances: the first line that still owes something, in
     * the inscription's own order.
     *
     * analyze() cannot make this call — it reads a snapshot taken before any
     * row was written, so every unmatched payment of one student resolved to
     * the same "first unpaid" line and stacked on it (a 300 DH fee holding
     * 7 600 DH, 26/08/2026). Returns null when the row was not loosely
     * matched, or when every candidate line is already settled — the
     * remaining amount then falls through to the recorded fee, exactly as an
     * overpayment would through the UI.
     *
     * @param  array<string, mixed>  $resolution
     */
    private function pickLooseFee(array $resolution): ?int
    {
        $ids = $resolution['loose_fee_ids'] ?? null;

        if (! is_array($ids) || $ids === []) {
            return null;
        }

        $fees = InscriptionFee::query()
            ->whereIn('id', $ids)
            ->withSum('encaissements', 'montant')
            ->get()
            ->keyBy('id');

        foreach ($ids as $id) {
            $fee = $fees->get($id);

            if ($fee === null) {
                continue;
            }

            $du = (float) $fee->montant;
            $paye = (float) ($fee->encaissements_sum_montant ?? 0);

            // A fee priced 0.00 (the legacy inscriptions export carries no
            // amounts) has no known due — it is back-filled from the money
            // that arrives, so it is always a valid target.
            if ($du <= 0.0 || $paye + 0.01 < $du) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Splits one payment across the fees it settles.
     *
     * Each fee takes what it still owes (its montant minus what is already
     * paid), in the order the file lists them; the LAST fee takes whatever
     * is left, so the parts always re-sum to the exact amount received and
     * no centime is invented or lost. A fee priced at 0.00 (the legacy
     * inscriptions export carries no amounts) has no known due, so it is
     * skipped by the cap and settled by that final remainder.
     *
     * Returns [[feeId, "montant"], …] — a single pair for an ordinary row.
     *
     * @param  list<int>|null  $splitFeeIds
     * @return list<array{0: ?int, 1: string}>
     */
    private function allocateAcrossFees(?array $splitFeeIds, ?int $singleFeeId, string $montant): array
    {
        if ($splitFeeIds === null || count($splitFeeIds) < 2) {
            return [[$singleFeeId, $montant]];
        }

        $fees = InscriptionFee::query()
            ->whereIn('id', $splitFeeIds)
            ->get()
            ->keyBy('id');

        $reste = round((float) $montant, 2);
        $allocations = [];
        $last = count($splitFeeIds) - 1;

        foreach ($splitFeeIds as $index => $feeId) {
            if ($index === $last) {
                break;
            }

            $fee = $fees->get($feeId);
            $du = $fee === null ? 0.0 : round((float) $fee->montant - $fee->montantPaye(), 2);

            // Never more than what is left, never negative.
            $part = max(0.0, min($du, $reste));

            if ($part <= 0.0) {
                continue;
            }

            $allocations[] = [$feeId, number_format($part, 2, '.', '')];
            $reste = round($reste - $part, 2);
        }

        // The remainder lands on the last named fee — but ONLY if there is
        // one. When the earlier lines already absorbed the whole payment the
        // share is 0.00, and a caisse movement must be strictly positive:
        // emitting it made EnregistrerEncaissement refuse and took the WHOLE
        // payment down with it (149 rows, 169 950 DH, 26/08/2026).
        if ($reste > 0.0) {
            $allocations[] = [$splitFeeIds[$last], number_format($reste, 2, '.', '')];
        }

        // Every named fee was already settled: the money is still real, so it
        // goes to the last fee as an overpayment rather than being refused.
        // Losing a payment is never better than recording it on the fee the
        // file itself names.
        if ($allocations === []) {
            return [[$splitFeeIds[$last], $montant]];
        }

        return $allocations;
    }

    /**
     * A comma-separated Frais cell naming SEVERAL fees of one inscription.
     * Returns their ids in the order the cell lists them, or null when the
     * cell names a single fee (the normal case) or when the parts do not all
     * resolve inside ONE inscription — a partial match would silently drop
     * money, so that stays for the ordinary single-fee path to report.
     *
     * @param  list<Inscription>  $inscriptions
     * @return list<int>|null
     */
    private function resolveSplitFees(array $inscriptions, string $fraisLabel): ?array
    {
        if (! str_contains($fraisLabel, ',')) {
            return null;
        }

        $parts = array_values(array_filter(array_map(
            fn (string $part): string => mb_strtolower(CellNormalizer::text($part)),
            explode(',', $fraisLabel)
        ), fn (string $part): bool => $part !== ''));

        if (count($parts) < 2) {
            return null;
        }

        foreach ($inscriptions as $inscription) {
            $fees = $this->feesByInscriptionId[$inscription->id] ?? collect();
            $ids = [];

            foreach ($parts as $part) {
                $match = $fees->first(
                    fn (InscriptionFee $fee) => mb_strtolower(CellNormalizer::text($fee->nom)) === $part
                );

                if ($match === null) {
                    continue 2;
                }

                $ids[] = $match->id;
            }

            // Every named fee found on the SAME inscription.
            return array_values(array_unique($ids));
        }

        return null;
    }

    private function resolveInscriptionFee(int $studentId, string $fraisLabel): array
    {
        $inscriptions = $this->activeInscriptionByStudentId[$studentId] ?? [];

        if ($inscriptions === []) {
            return ['inscription_fee_id' => null, 'candidates' => [], 'conflicts' => [
                ['field' => 'inscription_fee_id', 'code' => 'no_inscription', 'message' => $this->includeInactiveInscriptions
                    ? "Aucune inscription pour cet étudiant dans ce centre (ni active, ni annulée, ni changement) — importer d'abord les inscriptions."
                    : "Aucune inscription active pour cet étudiant dans ce centre. Si son inscription a été annulée ou changée, cochez « Accepter les inscriptions annulées / changement » avant d'analyser."],
            ]];
        }

        $normalizedTarget = mb_strtolower(CellNormalizer::text($fraisLabel));

        // An EXACT fee-label match is the strongest signal of which
        // inscription this payment belongs to — stronger than the année the
        // operator picked — so every inscription is searched for one before
        // falling back. Ordered best-candidate-first by the preload, so the
        // batch's own année still wins a tie.
        foreach ($inscriptions as $inscription) {
            $fees = $this->feesByInscriptionId[$inscription->id] ?? collect();

            $exact = $fees->first(
                fn (InscriptionFee $fee) => mb_strtolower(CellNormalizer::text($fee->nom)) === $normalizedTarget
            );

            if ($exact !== null) {
                return ['inscription_fee_id' => $exact->id, 'candidates' => [], 'conflicts' => []];
            }
        }

        // ONE payment settling SEVERAL fees: the old CRM writes them
        // comma-separated in a single cell ("Frais d'inscription A1/A2/B1,
        // Frais de Juin" — 1 600 DH covering two 300/1 300 lines). Attaching
        // the whole amount to the first line inflated it far past its own
        // montant (a 300 DH fee showing 1 600 DH paid, 26/08/2026).
        // Resolved here into the list of fees actually named; commit() then
        // writes ONE encaissement per fee, capped at each line's remaining
        // due, so every line settles for what it is really owed.
        $split = $this->resolveSplitFees($inscriptions, $fraisLabel);

        if ($split !== null) {
            return [
                'inscription_fee_id' => $split[0],
                'split_fee_ids' => $split,
                'candidates' => [],
                'conflicts' => [],
            ];
        }

        // No exact label anywhere: fall back within the best-ranked
        // inscription only (see below — the first still-unpaid line).
        $inscription = $inscriptions[0];
        $fees = $this->feesByInscriptionId[$inscription->id] ?? collect();

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
        // ⚠ The candidate lines are recorded, but WHICH one receives the
        // money is decided at COMMIT time, not here. analyze() sees a
        // snapshot taken before a single row was written, so every unmatched
        // payment of one student picked the SAME "first unpaid" line and
        // stacked on it — one 300 DH inscription fee absorbed 7 600 DH over
        // 8 payments (334 fees overpaid across the seven centres,
        // 26/08/2026). Only commit() knows what each line still owes after
        // the rows before it landed.
        foreach ($inscriptions as $candidateInscription) {
            $candidateFees = $this->feesByInscriptionId[$candidateInscription->id] ?? collect();

            if ($candidateFees->isEmpty()) {
                continue;
            }

            return [
                // A placeholder so the row is resolvable; commit() re-picks
                // among loose_fee_ids against live balances.
                'inscription_fee_id' => $candidateFees->first()->id,
                'loose_fee_ids' => $candidateFees->pluck('id')->all(),
                'candidates' => $candidates,
                'conflicts' => [],
                'fee_matched_loosely' => [
                    'requested' => $fraisLabel,
                    'attached_to' => null,
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
                        "Déjà importé : %sla réf. %s existe déjà dans ce centre (import précédent) — le montant n'a pas été compté deux fois.",
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
