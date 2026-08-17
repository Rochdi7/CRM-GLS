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
    private const string LEGACY_SOURCE = 'ancien-crm';

    private const array EXPECTED_COLUMNS = ['Réf', 'Étudiant', 'Groupe', 'Statut', "Date d'inscription"];

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
                DB::transaction(function () use ($row, $batch, $importingAdmin): void {
                    $data = $row->raw;
                    $resolution = $row->resolution ?? [];

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
        $statut = CellNormalizer::text($rawRow['Statut'] ?? '');

        $parseErrors = [];
        try {
            $dateInscription = CellNormalizer::parseDate($rawRow["Date d'inscription"] ?? null)?->toDateString();
        } catch (ImportCellParseException $e) {
            $dateInscription = null;
            $parseErrors[] = ['field' => 'date_inscription', 'code' => 'unparseable', 'message' => $e->getMessage()];
        }

        $studentResolution = $this->resolveStudent($batch, $etudiant);
        $groupResolution = $this->resolveGroup($context, $groupe);

        $raw = [
            'legacy_ref' => $legacyRef,
            'etudiant' => $etudiant,
            'groupe' => $groupe,
            'statut' => $statut,
            'date_inscription' => $dateInscription,
            'student_id' => $studentResolution['student_id'],
            'group_id' => $groupResolution['group_id'],
        ];

        $duplicate = $this->findDuplicate($batch, $legacyRef, $studentResolution['student_id'], $groupResolution['group_id'], $dateInscription);

        if ($duplicate !== null) {
            ImportRow::create([
                'import_batch_id' => $batch->id,
                'source_row_number' => $rowNumber,
                'raw' => $raw,
                'status' => ImportRow::STATUT_DOUBLON,
                'legacy_ref' => $legacyRef ?: null,
                'resolution' => ['matched_inscription_id' => $duplicate->id],
            ]);

            return;
        }

        $conflitReasons = [...$studentResolution['conflicts'], ...$groupResolution['conflicts']];

        if ($conflitReasons !== []) {
            ImportRow::create([
                'import_batch_id' => $batch->id,
                'source_row_number' => $rowNumber,
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
            'student_id' => $studentResolution['student_id'],
            'group_id' => $groupResolution['group_id'],
            'date_inscription' => $dateInscription,
            'statut' => $statut,
        ];

        $errors = [...$parseErrors, ...$this->validator->validateInscription($normalized)];

        ImportRow::create([
            'import_batch_id' => $batch->id,
            'source_row_number' => $rowNumber,
            'raw' => $raw,
            'status' => $errors === [] ? ImportRow::STATUT_NOUVEAU : ImportRow::STATUT_ERREUR,
            'errors' => $errors === [] ? null : $errors,
            'legacy_ref' => $legacyRef ?: null,
            'resolution' => ['student_id' => $studentResolution['student_id'], 'group_id' => $groupResolution['group_id']],
        ]);
    }

    /**
     * @return array{student_id: ?int, candidates: array<int, array{id: int, nom: string}>, conflicts: array<int, array{field: string, code: string, message: string}>}
     */
    private function resolveStudent(ImportBatch $batch, string $etudiant): array
    {
        if ($etudiant === '') {
            return ['student_id' => null, 'candidates' => [], 'conflicts' => [
                ['field' => 'student_id', 'code' => 'student_not_found', 'message' => 'Étudiant vide.'],
            ]];
        }

        $normalizedTarget = mb_strtolower(CellNormalizer::text($etudiant));

        $matches = Student::query()
            ->where('etablissement_id', $batch->etablissement_id)
            ->whereRaw(
                "lower(regexp_replace(trim(concat(prenom, ' ', nom)), '\\s+', ' ', 'g')) = ?",
                [$normalizedTarget]
            )
            ->get(['id', 'prenom', 'nom']);

        if ($matches->count() === 1) {
            return ['student_id' => $matches->first()->id, 'candidates' => [], 'conflicts' => []];
        }

        if ($matches->count() > 1) {
            return [
                'student_id' => null,
                'candidates' => $matches->map(fn (Student $s): array => ['id' => $s->id, 'nom' => "{$s->prenom} {$s->nom}"])->all(),
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
            $group = Group::query()
                ->where('id', $mapping['group_id'])
                ->where('etablissement_id', $context->etablissementId)
                ->where('annee_scolaire_id', $context->anneeScolaireId)
                ->first();

            if ($group === null) {
                return ['group_id' => null, 'candidates' => [], 'conflicts' => [
                    ['field' => 'group_id', 'code' => 'group_out_of_scope', 'message' => sprintf(
                        'Le groupe associé à "%s" ne correspond pas au centre/année sélectionné.',
                        $groupe
                    )],
                ]];
            }

            return ['group_id' => $group->id, 'candidates' => [], 'conflicts' => []];
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
            ];
        })->all();
    }

    private function findDuplicate(ImportBatch $batch, string $legacyRef, ?int $studentId, ?int $groupId, ?string $dateInscription): ?Inscription
    {
        if ($legacyRef !== '') {
            $existing = Inscription::query()
                ->where('etablissement_id', $batch->etablissement_id)
                ->where('legacy_ref', $legacyRef)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        if ($studentId === null || $groupId === null || $dateInscription === null) {
            return null;
        }

        return Inscription::query()
            ->where('student_id', $studentId)
            ->where('group_id', $groupId)
            ->where('date_inscription', $dateInscription)
            ->first();
    }
}
