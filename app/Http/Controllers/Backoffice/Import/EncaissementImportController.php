<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Import;

use App\Http\Controllers\Backoffice\Concerns\ResolvesActingEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Import\AnalyzeEncaissementImportRequest;
use App\Http\Requests\Backoffice\Import\CommitImportRequest;
use App\Http\Requests\Backoffice\Import\PeekEncaissementOperateursRequest;
use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\ImportBatch;
use App\Services\Authorization\CenterAccessService;
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
    use ResolvesActingEmployee;

    public function create(Request $request, CenterAccessService $centerAccess): Response
    {
        $this->authorize('create', ImportBatch::class);

        return Inertia::render('Backoffice/Import/Encaissements/Upload', [
            'etablissements' => $this->etablissementOptions($request, $centerAccess),
            'anneesScolaires' => AnneeScolaire::query()->orderByDesc('date_debut')->get(['id', 'nom', 'par_defaut']),
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
        $data = $request->validated();

        abort_unless($centerAccess->canAccessCenter($request->user(), (int) $data['etablissement_id']), 403);

        $labels = $sheetReader->distinctColumnValues($request->file('file')->getRealPath(), 'Opérateur');

        $employees = Employee::query()
            ->where('etablissement_id', $data['etablissement_id'])
            ->orderBy('nom')
            ->get(['id', 'nom', 'prenom']);

        return response()->json(['operateurLabels' => $labels, 'employees' => $employees]);
    }

    public function analyze(AnalyzeEncaissementImportRequest $request, EncaissementImporter $importer, CenterAccessService $centerAccess): RedirectResponse
    {
        $this->authorize('create', ImportBatch::class);
        $data = $request->validated();

        abort_unless($centerAccess->canAccessCenter($request->user(), (int) $data['etablissement_id']), 403);

        $admin = $this->actingEmployee($request);

        $operateurMapping = [];
        foreach ($data['operateur_mapping'] as $entry) {
            $operateurMapping[$entry['label']] = (int) $entry['employee_id'];
        }

        $context = new ImportContext(
            etablissementId: (int) $data['etablissement_id'],
            anneeScolaireId: (int) $data['annee_scolaire_id'],
            operateurMapping: $operateurMapping,
        );

        $batch = $importer->analyze($request->file('file'), $context, $admin);

        return redirect()->route('backoffice.import.encaissements.preview', $batch);
    }

    public function preview(ImportBatch $batch): Response
    {
        $this->authorize('view', $batch);
        abort_unless($batch->module === ImportBatch::MODULE_ENCAISSEMENTS, 404);

        return Inertia::render('Backoffice/Import/Encaissements/Preview', [
            'batch' => $batch->load(['etablissement', 'anneeScolaire']),
            'rows' => $batch->rows()->orderBy('source_row_number')->get(),
        ]);
    }

    public function commit(CommitImportRequest $request, ImportBatch $batch, EncaissementImporter $importer): RedirectResponse
    {
        $this->authorize('create', ImportBatch::class);
        abort_unless($batch->module === ImportBatch::MODULE_ENCAISSEMENTS, 404);

        $admin = $this->actingEmployee($request);

        $result = $importer->commit($batch, $request->validated('selected_row_ids'), $admin);

        return redirect()->route('backoffice.import.encaissements.result', $batch)
            ->with('success', __(':count rows imported.', ['count' => $result->insertedCount]));
    }

    public function result(ImportBatch $batch): Response
    {
        $this->authorize('view', $batch);
        abort_unless($batch->module === ImportBatch::MODULE_ENCAISSEMENTS, 404);

        return Inertia::render('Backoffice/Import/Encaissements/Result', [
            'batch' => $batch->load(['etablissement', 'anneeScolaire']),
            'rows' => $batch->rows()->orderBy('source_row_number')->get(),
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
}
