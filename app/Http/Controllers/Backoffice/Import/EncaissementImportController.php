<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Import;

use App\Http\Controllers\Backoffice\Concerns\ResolvesActingEmployee;
use App\Http\Controllers\Backoffice\Import\Concerns\BuildsImportPreview;
use App\Http\Controllers\Backoffice\Import\Concerns\ResolvesImportScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Import\AnalyzeEncaissementImportRequest;
use App\Http\Requests\Backoffice\Import\CommitImportRequest;
use App\Http\Requests\Backoffice\Import\PeekEncaissementOperateursRequest;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\Inscription;
use App\Models\Student;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\EncaissementImporter;
use App\Services\Import\SheetReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EncaissementImportController extends Controller
{
    use BuildsImportPreview;
    use ResolvesActingEmployee;
    use ResolvesImportScope;

    public function create(Request $request, CenterAccessService $centerAccess): Response
    {
        $this->authorize('create', ImportBatch::class);

        return Inertia::render('Backoffice/Import/Encaissements/Upload', [
            'etablissements' => $this->etablissementOptions($request, $centerAccess),
            'centerLocked' => ! app(CurrentContext::class)->isAllCenters(),
        ]);
    }

    /**
     * Peeks the file's distinct "Opérateur" labels — the mapping screen the
     * user fills in (Opérateur -> Employee, scoped to the chosen Centre)
     * before the real analyze() call. Read-only, no batch created yet.
     */
    public function peekOperateurs(PeekEncaissementOperateursRequest $request, SheetReader $sheetReader, CenterAccessService $centerAccess): JsonResponse
    {
        $this->authorize('create', ImportBatch::class);
        $request->validated();
        [$etablissementId, $anneeScolaireId] = $this->importScope($request, $centerAccess);

        $labels = $sheetReader->distinctColumnValues($request->file('file')->getRealPath(), 'Opérateur');

        // availableForCenter(), not a bare etablissement_id filter: an
        // employee may work in several centers, and the direction accounts
        // (super-admin / centers.access-all) sign payments in every center
        // while being attached to a single primary one.
        $employees = Employee::query()
            ->availableForCenter($etablissementId)
            ->orderBy('nom')
            ->get(['id', 'nom', 'prenom']);

        return response()->json([
            'operateurLabels' => $labels,
            'employees' => $employees,
            'readiness' => $this->inscriptionReadiness($etablissementId, $anneeScolaireId),
        ]);
    }

    /**
     * A payment can only attach to a fee line of an ACTIVE inscription, so a
     * centre whose students mostly have no inscription yet will produce a
     * wall of conflicts. Surfacing that here — before analysing thousands of
     * rows — is far kinder than discovering it on the result screen.
     *
     * @return array{students: int, withInscription: int, missing: int}
     */
    private function inscriptionReadiness(int $etablissementId, int $anneeScolaireId): array
    {
        $students = Student::query()->where('etablissement_id', $etablissementId)->count();

        $withInscription = Inscription::query()
            ->where('etablissement_id', $etablissementId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('statut', Inscription::STATUT_ACTIVE)
            ->distinct('student_id')
            ->count('student_id');

        return [
            'students' => $students,
            'withInscription' => $withInscription,
            'missing' => max(0, $students - $withInscription),
        ];
    }

    public function analyze(AnalyzeEncaissementImportRequest $request, EncaissementImporter $importer, CenterAccessService $centerAccess): RedirectResponse
    {
        $this->authorize('create', ImportBatch::class);
        $data = $request->validated();
        [$etablissementId, $anneeScolaireId] = $this->importScope($request, $centerAccess);

        $admin = $this->actingEmployee($request);

        $operateurMapping = [];
        foreach ($data['operateur_mapping'] as $entry) {
            $operateurMapping[$entry['label']] = (int) $entry['employee_id'];
        }

        $context = new ImportContext(
            etablissementId: $etablissementId,
            anneeScolaireId: $anneeScolaireId,
            operateurMapping: $operateurMapping,
            includeInactiveInscriptions: (bool) ($data['include_inactive_inscriptions'] ?? false),
        );

        $batch = $importer->analyze($request->file('file'), $context, $admin);

        return redirect()->route('backoffice.import.encaissements.preview', $batch);
    }

    public function preview(Request $request, ImportBatch $batch): Response
    {
        $this->authorize('view', $batch);
        abort_unless($batch->module === ImportBatch::MODULE_ENCAISSEMENTS, 404);

        return Inertia::render('Backoffice/Import/Encaissements/Preview', [
            ...$this->previewProps($request, $batch),
            'excelTotal' => $this->rawMontantTotal($batch),
        ]);
    }

    /**
     * Commits one small chunk of the selection per call — the Preview
     * screen calls this in a loop, updating a progress bar between calls,
     * instead of one long synchronous request with no feedback.
     */
    public function commit(CommitImportRequest $request, ImportBatch $batch, EncaissementImporter $importer): JsonResponse
    {
        $this->authorize('create', ImportBatch::class);
        abort_unless($batch->module === ImportBatch::MODULE_ENCAISSEMENTS, 404);

        $admin = $this->actingEmployee($request);

        $result = $importer->commit($batch, $request->validated('selected_row_ids'), $admin);

        return response()->json([
            'inserted' => $result->insertedCount,
            'errors' => $result->errorCount,
            'remaining' => $result->remaining,
            'batch' => $batch->fresh(),
        ]);
    }

    public function result(Request $request, ImportBatch $batch): Response
    {
        $this->authorize('view', $batch);
        abort_unless($batch->module === ImportBatch::MODULE_ENCAISSEMENTS, 404);

        return Inertia::render('Backoffice/Import/Encaissements/Result', [
            ...$this->resultProps($request, $batch),
            'excelTotal' => $this->rawMontantTotal($batch),
            'importedTotal' => $this->importedMontantTotal($batch),
        ]);
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

    /**
     * Re-queues previously-failed rows so the next commit pass picks them up.
     * A failed row keeps ECHEC_COMMIT and is intentionally not commit-
     * eligible (that made the progress loop spin forever), so retrying is an
     * explicit action that flips it back to CONFLIT — where the resolution
     * guard and duplicate check run again exactly as before.
     */
    public function retryFailed(Request $request, ImportBatch $batch): RedirectResponse
    {
        $this->authorize('create', ImportBatch::class);
        abort_unless($batch->module === ImportBatch::MODULE_ENCAISSEMENTS, 404);

        $requeued = $batch->rows()
            ->whereIn('status', ImportRow::RETRYABLE_STATUTS)
            ->update(['status' => ImportRow::STATUT_CONFLIT, 'errors' => null]);

        return back()->with('success', __(':count rows queued for retry.', ['count' => $requeued]));
    }

}
