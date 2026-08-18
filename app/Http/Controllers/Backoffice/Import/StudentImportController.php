<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Import;

use App\Http\Controllers\Backoffice\Concerns\ResolvesActingEmployee;
use App\Http\Controllers\Backoffice\Import\Concerns\BuildsImportPreview;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Import\AnalyzeStudentImportRequest;
use App\Http\Requests\Backoffice\Import\CommitImportRequest;
use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\ImportBatch;
use App\Services\Authorization\CenterAccessService;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\StudentImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class StudentImportController extends Controller
{
    use BuildsImportPreview;
    use ResolvesActingEmployee;

    public function create(Request $request, CenterAccessService $centerAccess): Response
    {
        $this->authorize('create', ImportBatch::class);

        return Inertia::render('Backoffice/Import/Students/Upload', [
            'etablissements' => $this->etablissementOptions($request, $centerAccess),
            'anneesScolaires' => AnneeScolaire::query()->orderByDesc('date_debut')->get(['id', 'nom', 'par_defaut']),
        ]);
    }

    public function analyze(AnalyzeStudentImportRequest $request, StudentImporter $importer, CenterAccessService $centerAccess): RedirectResponse
    {
        $this->authorize('create', ImportBatch::class);

        $data = $request->validated();

        abort_unless(
            $centerAccess->canAccessCenter($request->user(), (int) $data['etablissement_id']),
            403,
        );

        $admin = $this->actingEmployee($request);

        $context = new ImportContext(
            etablissementId: (int) $data['etablissement_id'],
            anneeScolaireId: (int) $data['annee_scolaire_id'],
        );

        $batch = $importer->analyze($request->file('file'), $context, $admin);

        return redirect()->route('backoffice.import.students.preview', $batch);
    }

    public function preview(Request $request, ImportBatch $batch): Response
    {
        $this->authorize('view', $batch);
        abort_unless($batch->module === ImportBatch::MODULE_STUDENTS, 404);

        return Inertia::render('Backoffice/Import/Students/Preview', $this->previewProps($request, $batch));
    }

    /**
     * Commits one small chunk of the selection per call — the Preview
     * screen calls this in a loop, updating a progress bar between calls,
     * instead of one long synchronous request with no feedback.
     */
    public function commit(CommitImportRequest $request, ImportBatch $batch, StudentImporter $importer): JsonResponse
    {
        $this->authorize('create', ImportBatch::class);
        abort_unless($batch->module === ImportBatch::MODULE_STUDENTS, 404);

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
        abort_unless($batch->module === ImportBatch::MODULE_STUDENTS, 404);

        return Inertia::render('Backoffice/Import/Students/Result', $this->resultProps($request, $batch));
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
