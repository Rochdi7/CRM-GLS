<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Import;

use App\Http\Controllers\Backoffice\Concerns\ResolvesActingEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Import\AnalyzeInscriptionImportRequest;
use App\Http\Requests\Backoffice\Import\CommitImportRequest;
use App\Http\Requests\Backoffice\Import\PeekInscriptionGroupesRequest;
use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\ImportBatch;
use App\Services\Authorization\CenterAccessService;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\InscriptionImporter;
use App\Services\Import\SheetReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class InscriptionImportController extends Controller
{
    use ResolvesActingEmployee;

    public function create(Request $request, CenterAccessService $centerAccess): Response
    {
        $this->authorize('create', ImportBatch::class);

        return Inertia::render('Backoffice/Import/Inscriptions/Upload', [
            'etablissements' => $this->etablissementOptions($request, $centerAccess),
            'anneesScolaires' => AnneeScolaire::query()->orderByDesc('date_debut')->get(['id', 'nom', 'par_defaut']),
        ]);
    }

    /**
     * Peeks the file's distinct "Groupe" labels, scoped to the chosen
     * Centre+Année's existing groups — the mapping screen the user fills in
     * before the real analyze() call. Read-only, no batch created yet.
     */
    public function peekGroupes(PeekInscriptionGroupesRequest $request, SheetReader $sheetReader, CenterAccessService $centerAccess): JsonResponse
    {
        $this->authorize('create', ImportBatch::class);
        $data = $request->validated();

        abort_unless($centerAccess->canAccessCenter($request->user(), (int) $data['etablissement_id']), 403);

        $labels = $sheetReader->distinctColumnValues($request->file('file')->getRealPath(), 'Groupe');

        $groups = Group::query()
            ->where('etablissement_id', $data['etablissement_id'])
            ->where('annee_scolaire_id', $data['annee_scolaire_id'])
            ->orderBy('nom')
            ->get(['id', 'nom']);

        return response()->json(['groupeLabels' => $labels, 'existingGroups' => $groups, 'niveaux' => Group::NIVEAUX]);
    }

    public function analyze(AnalyzeInscriptionImportRequest $request, InscriptionImporter $importer, CenterAccessService $centerAccess): RedirectResponse
    {
        $this->authorize('create', ImportBatch::class);
        $data = $request->validated();

        abort_unless($centerAccess->canAccessCenter($request->user(), (int) $data['etablissement_id']), 403);

        $admin = $this->actingEmployee($request);

        $groupeMapping = $this->resolveGroupeMapping(
            $importer,
            $data['groupe_mapping'],
            (int) $data['etablissement_id'],
            (int) $data['annee_scolaire_id'],
        );

        $context = new ImportContext(
            etablissementId: (int) $data['etablissement_id'],
            anneeScolaireId: (int) $data['annee_scolaire_id'],
            groupeMapping: $groupeMapping,
        );

        $batch = $importer->analyze($request->file('file'), $context, $admin);

        return redirect()->route('backoffice.import.inscriptions.preview', $batch);
    }

    public function preview(ImportBatch $batch): Response
    {
        $this->authorize('view', $batch);
        abort_unless($batch->module === ImportBatch::MODULE_INSCRIPTIONS, 404);

        return Inertia::render('Backoffice/Import/Inscriptions/Preview', [
            'batch' => $batch->load(['etablissement', 'anneeScolaire']),
            'rows' => $batch->rows()->orderBy('source_row_number')->get(),
        ]);
    }

    public function commit(CommitImportRequest $request, ImportBatch $batch, InscriptionImporter $importer): RedirectResponse
    {
        $this->authorize('create', ImportBatch::class);
        abort_unless($batch->module === ImportBatch::MODULE_INSCRIPTIONS, 404);

        $admin = $this->actingEmployee($request);

        $result = $importer->commit($batch, $request->validated('selected_row_ids'), $admin);

        return redirect()->route('backoffice.import.inscriptions.result', $batch)
            ->with('success', __(':count rows imported.', ['count' => $result->insertedCount]));
    }

    public function result(ImportBatch $batch): Response
    {
        $this->authorize('view', $batch);
        abort_unless($batch->module === ImportBatch::MODULE_INSCRIPTIONS, 404);

        return Inertia::render('Backoffice/Import/Inscriptions/Result', [
            'batch' => $batch->load(['etablissement', 'anneeScolaire']),
            'rows' => $batch->rows()->orderBy('source_row_number')->get(),
        ]);
    }

    /**
     * Resolves the posted mapping choices into ImportContext's shape,
     * eagerly creating any "action: create" group (with its full
     * catalog-wide group_frais sync) right now so every row referencing
     * that label sees a consistent, already-resolved group_id.
     *
     * @param  array<int, array{label: string, action: string, group_id?: int, nom?: string, niveau?: string}>  $mapping
     * @return array<string, array{action: string, group_id?: int}>
     */
    private function resolveGroupeMapping(InscriptionImporter $importer, array $mapping, int $etablissementId, int $anneeScolaireId): array
    {
        return DB::transaction(function () use ($importer, $mapping, $etablissementId, $anneeScolaireId): array {
            $resolved = [];

            foreach ($mapping as $entry) {
                if ($entry['action'] === 'map') {
                    $resolved[$entry['label']] = ['action' => 'map', 'group_id' => (int) $entry['group_id']];

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
