<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Import;

use App\Domain\Groups\Actions\ReaffecterGroupeVersAnnee;
use App\Http\Controllers\Backoffice\Concerns\ResolvesActingEmployee;
use App\Http\Controllers\Backoffice\Import\Concerns\BuildsImportPreview;
use App\Http\Controllers\Backoffice\Import\Concerns\ResolvesImportScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Import\AnalyzeInscriptionImportRequest;
use App\Http\Requests\Backoffice\Import\CommitImportRequest;
use App\Http\Requests\Backoffice\Import\PeekInscriptionGroupesRequest;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\InscriptionImporter;
use App\Services\Import\SheetReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class InscriptionImportController extends Controller
{
    use BuildsImportPreview;
    use ResolvesActingEmployee;
    use ResolvesImportScope;

    public function create(Request $request, CenterAccessService $centerAccess): Response
    {
        $this->authorize('create', ImportBatch::class);

        return Inertia::render('Backoffice/Import/Inscriptions/Upload', [
            'etablissements' => $this->etablissementOptions($request, $centerAccess),
            'centerLocked' => ! app(CurrentContext::class)->isAllCenters(),
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
        $request->validated();
        [$etablissementId, $anneeScolaireId] = $this->importScope($request, $centerAccess);

        $labels = $sheetReader->distinctColumnValues($request->file('file')->getRealPath(), 'Groupe');

        return response()->json([
            'groupeLabels' => $labels,
            'existingGroups' => $this->existingGroupOptions($etablissementId, $anneeScolaireId),
            'niveaux' => Group::NIVEAUX,
        ]);
    }

    public function analyze(AnalyzeInscriptionImportRequest $request, InscriptionImporter $importer, CenterAccessService $centerAccess): RedirectResponse
    {
        $this->authorize('create', ImportBatch::class);
        $data = $request->validated();
        [$etablissementId, $anneeScolaireId] = $this->importScope($request, $centerAccess);

        $admin = $this->actingEmployee($request);

        $groupeMapping = $this->resolveGroupeMapping(
            $importer,
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

        $batch = $importer->analyze($request->file('file'), $context, $admin);

        return redirect()->route('backoffice.import.inscriptions.preview', $batch);
    }

    public function preview(Request $request, ImportBatch $batch): Response
    {
        $this->authorize('view', $batch);
        abort_unless($batch->module === ImportBatch::MODULE_INSCRIPTIONS, 404);

        return Inertia::render('Backoffice/Import/Inscriptions/Preview', $this->previewProps($request, $batch));
    }

    /**
     * Commits one small chunk of the selection per call — the Preview
     * screen calls this in a loop, updating a progress bar between calls,
     * instead of one long synchronous request with no feedback.
     */
    public function commit(CommitImportRequest $request, ImportBatch $batch, InscriptionImporter $importer): JsonResponse
    {
        $this->authorize('create', ImportBatch::class);
        // The batch itself must be within reach: a batch of another centre
        // is never committed/retried from here (audit SEC-05).
        $this->authorize('view', $batch);
        abort_unless($batch->module === ImportBatch::MODULE_INSCRIPTIONS, 404);

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
        abort_unless($batch->module === ImportBatch::MODULE_INSCRIPTIONS, 404);

        return Inertia::render('Backoffice/Import/Inscriptions/Result', $this->resultProps($request, $batch));
    }

    /**
     * The mapping screen's "Groupe existant" options: the centre's groups
     * from EVERY année, each labeled with its year. Mapping onto an
     * other-year group re-affects it (with its inscriptions/séances) to the
     * import's selected year — see resolveGroupeMapping. Restricting the
     * list to the selected year forced the operator to CREATE a duplicate
     * group whenever the data had been imported under the wrong year, which
     * is how the data ended up split across two years.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, nom: string, anneeNom: ?string, horsAnnee: bool}>
     */
    private function existingGroupOptions(int $etablissementId, int $anneeScolaireId)
    {
        return Group::query()
            ->where('etablissement_id', $etablissementId)
            ->with('anneeScolaire:id,nom')
            ->orderBy('nom')
            ->get(['id', 'nom', 'annee_scolaire_id'])
            ->map(fn (Group $g): array => [
                'id' => $g->id,
                'nom' => $g->nom,
                'anneeNom' => $g->anneeScolaire?->nom,
                'horsAnnee' => (int) $g->annee_scolaire_id !== $anneeScolaireId,
            ])
            ->values();
    }

    /**
     * Resolves the posted mapping choices into ImportContext's shape,
     * eagerly creating any "action: create" group (with its full
     * catalog-wide group_frais sync) right now so every row referencing
     * that label sees a consistent, already-resolved group_id.
     *
     * A "map" onto a group from ANOTHER année re-affects that group — with
     * its inscriptions and séances — to the import's selected year
     * (ReaffecterGroupeVersAnnee), so one import never leaves the same
     * group's data split across two years. The group must belong to the
     * batch's centre; a cross-centre id is refused.
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
        // The batch itself must be within reach: a batch of another centre
        // is never committed/retried from here (audit SEC-05).
        $this->authorize('view', $batch);
        abort_unless($batch->module === ImportBatch::MODULE_INSCRIPTIONS, 404);

        $requeued = $batch->rows()
            ->whereIn('status', ImportRow::RETRYABLE_STATUTS)
            ->update(['status' => ImportRow::STATUT_CONFLIT, 'errors' => null]);

        return back()->with('success', __(':count rows queued for retry.', ['count' => $requeued]));
    }

}
