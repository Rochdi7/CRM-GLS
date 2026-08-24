<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Import;

use App\Domain\Groups\Actions\ReaffecterGroupeVersAnnee;
use App\Http\Controllers\Backoffice\Concerns\ResolvesActingEmployee;
use App\Http\Controllers\Backoffice\Import\Concerns\ResolvesImportScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Import\AnalyzeCombinedImportRequest;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\InscriptionImporter;
use App\Services\Import\StudentImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Combined « Étudiants + Inscriptions » import — one wizard, both legacy
 * files: (1) scope + the two files + statut filter, group-mapping peek on
 * the inscriptions file (the standalone peek endpoint is reused by the
 * page); (2) analyze imports the STUDENTS first (auto-committing every
 * clean row — the student import is idempotent and never updates an
 * existing student, so there is nothing to preview there; doublons and
 * conflicts stay recorded on the student batch), then analyzes the
 * INSCRIPTIONS against the freshly imported students and hands over to the
 * standard inscriptions preview/commit flow.
 *
 * Why: run separately, the inscriptions import can only match students
 * that already exist — an operator who skipped or half-finished the
 * student step got walls of "étudiant introuvable" conflicts. Here the
 * resolution happens in the same run by construction.
 */
final class CombinedImportController extends Controller
{
    use ResolvesActingEmployee;
    use ResolvesImportScope;

    public function create(Request $request, CenterAccessService $centerAccess): Response
    {
        $this->authorize('create', ImportBatch::class);

        return Inertia::render('Backoffice/Import/Combine/Upload', [
            'etablissements' => $this->etablissementOptions($request, $centerAccess),
            'centerLocked' => ! app(CurrentContext::class)->isAllCenters(),
        ]);
    }

    public function analyze(
        AnalyzeCombinedImportRequest $request,
        StudentImporter $studentImporter,
        InscriptionImporter $inscriptionImporter,
        CenterAccessService $centerAccess,
    ): RedirectResponse {
        $this->authorize('create', ImportBatch::class);
        $data = $request->validated();
        [$etablissementId, $anneeScolaireId] = $this->importScope($request, $centerAccess);

        $admin = $this->actingEmployee($request);

        // Two files, thousands of rows, plus the student auto-commit — a
        // plain web request's 30s budget is not enough on real exports.
        set_time_limit(300);

        // ---- 1. Students: analyze + auto-commit every clean row ---------
        $studentContext = new ImportContext(
            etablissementId: $etablissementId,
            anneeScolaireId: $anneeScolaireId,
        );

        $studentBatch = $studentImporter->analyze($request->file('students_file'), $studentContext, $admin);
        $studentTotals = $this->commitAllStudents($studentImporter, $studentBatch, $admin);

        // ---- 2. Inscriptions: mapping, then analyze against the fresh students
        $groupeMapping = $this->resolveGroupeMapping(
            $inscriptionImporter,
            $data['groupe_mapping'],
            $etablissementId,
            $anneeScolaireId,
        );

        $context = new ImportContext(
            etablissementId: $etablissementId,
            anneeScolaireId: $anneeScolaireId,
            groupeMapping: $groupeMapping,
            statutsRetenus: array_values($data['statuts'] ?? []),
        );

        $batch = $inscriptionImporter->analyzeMany(
            array_values($request->file('inscriptions_files')),
            $context,
            $admin,
        );

        return redirect()
            ->route('backoffice.import.inscriptions.preview', $batch)
            ->with('success', __('Students imported: :inserted added, :skipped skipped, :pending in conflict (batch #:batch). Now review the registrations below.', [
                'inserted' => $studentTotals['inserted'],
                'skipped' => $studentTotals['skipped'],
                'pending' => $studentTotals['pending'],
                'batch' => $studentBatch->id,
            ]));
    }

    /**
     * Server-side equivalent of the Preview page's commit loop: pushes every
     * selectable student row through StudentImporter::commit() until none
     * remains. Rows left in conflict/error stay on the student batch (visible
     * in « Imports récents ») — they are counted, never lost.
     *
     * @return array{inserted: int, skipped: int, pending: int}
     */
    private function commitAllStudents(StudentImporter $importer, ImportBatch $batch, $admin): array
    {
        $rowIds = $batch->rows()->whereIn('status', ImportRow::SELECTABLE_STATUTS)->pluck('id')->all();

        $inserted = 0;
        $skipped = 0;
        $guard = 0;

        if ($rowIds !== []) {
            do {
                $result = $importer->commit($batch, $rowIds, $admin, 100);
                $inserted += $result->insertedCount;
                $skipped += $result->skippedCount;

                if (++$guard > 200) {
                    throw ValidationException::withMessages([
                        'students_file' => __('The student import stopped making progress — check the batch in Recent imports.'),
                    ]);
                }
            } while ($result->remaining > 0);
        }

        $pending = $batch->rows()
            ->whereIn('status', [ImportRow::STATUT_CONFLIT, ImportRow::STATUT_ERREUR, ImportRow::STATUT_ECHEC_COMMIT])
            ->count();

        return ['inserted' => $inserted, 'skipped' => $skipped, 'pending' => $pending];
    }

    /**
     * Same contract as InscriptionImportController::resolveGroupeMapping —
     * "map" onto another année's group re-affects it to the selected year,
     * a cross-centre group is refused, "create" builds the group with the
     * full catalog sync.
     *
     * @param  array<int, array{label: string, action: string, group_id?: int, nom?: string, niveau?: string}>  $mapping
     * @return array<string, array{action: string, group_id?: int}>
     */
    private function resolveGroupeMapping(InscriptionImporter $importer, array $mapping, int $etablissementId, int $anneeScolaireId): array
    {
        return DB::transaction(function () use ($importer, $mapping, $etablissementId, $anneeScolaireId): array {
            $reaffecter = app(ReaffecterGroupeVersAnnee::class);
            $resolved = [];

            foreach ($mapping as $entry) {
                if ($entry['action'] === 'map') {
                    $group = Group::findOrFail((int) $entry['group_id']);

                    if ((int) $group->etablissement_id !== $etablissementId) {
                        throw ValidationException::withMessages([
                            'groupe_mapping' => __('The mapped group belongs to another centre.'),
                        ]);
                    }

                    $reaffecter->handle($group, $anneeScolaireId);

                    $resolved[$entry['label']] = ['action' => 'map', 'group_id' => $group->id];

                    continue;
                }

                $group = $importer->createGroupWithFullCatalogSync([
                    'nom' => $entry['nom'],
                    'niveau' => $entry['niveau'],
                    'statut' => Group::STATUT_EN_FORMATION,
                    'etablissement_id' => $etablissementId,
                    'annee_scolaire_id' => $anneeScolaireId,
                ]);

                $resolved[$entry['label']] = ['action' => 'create', 'group_id' => $group->id];
            }

            return $resolved;
        });
    }

    /** @return \Illuminate\Support\Collection<int, array{id: int, nom_centre: string}> */
    private function etablissementOptions(Request $request, CenterAccessService $centerAccess)
    {
        $query = Etablissement::query()->orderBy('nom_centre');

        if (! $centerAccess->hasGlobalAccess($request->user())) {
            $query->whereIn('id', $centerAccess->accessibleCenterIds($request->user()));
        }

        return $query->get(['id', 'nom_centre']);
    }
}
