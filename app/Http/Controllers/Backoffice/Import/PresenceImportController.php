<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Import;

use App\Domain\Groups\Actions\ReaffecterGroupeVersAnnee;
use App\Http\Controllers\Backoffice\Concerns\ResolvesActingEmployee;
use App\Http\Controllers\Backoffice\Import\Concerns\BuildsImportPreview;
use App\Http\Controllers\Backoffice\Import\Concerns\ResolvesImportScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Import\AnalyzePresenceImportRequest;
use App\Http\Requests\Backoffice\Import\CommitImportRequest;
use App\Http\Requests\Backoffice\Import\PeekPresenceGroupesRequest;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\InscriptionImporter;
use App\Services\Import\PresenceImporter;
use App\Services\Import\SheetReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Registre des présences" import — séances + absences. Same multi-step flow
 * as the other modules (scope -> associer les groupes -> analyser ->
 * aperçu -> insérer -> résultat); the séances themselves are derived from
 * the file by PresenceImporter, never mapped by hand.
 */
final class PresenceImportController extends Controller
{
    use BuildsImportPreview;
    use ResolvesActingEmployee;
    use ResolvesImportScope;

    public function create(Request $request, CenterAccessService $centerAccess): Response
    {
        $this->authorize('create', ImportBatch::class);

        return Inertia::render('Backoffice/Import/Presences/Upload', [
            'etablissements' => $this->etablissementOptions($request, $centerAccess),
            'centerLocked' => ! app(CurrentContext::class)->isAllCenters(),
        ]);
    }

    /**
     * Peeks the file's distinct "Groupe" labels, scoped to the chosen
     * Centre+Année's existing groups — the mapping screen the user fills in
     * before the real analyze() call. Read-only, no batch created yet.
     */
    public function peekGroupes(PeekPresenceGroupesRequest $request, SheetReader $sheetReader, CenterAccessService $centerAccess): JsonResponse
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

    public function analyze(
        AnalyzePresenceImportRequest $request,
        PresenceImporter $importer,
        InscriptionImporter $groupCreator,
        CenterAccessService $centerAccess
    ): RedirectResponse {
        $this->authorize('create', ImportBatch::class);
        $data = $request->validated();
        [$etablissementId, $anneeScolaireId] = $this->importScope($request, $centerAccess);

        $admin = $this->actingEmployee($request);

        $groupeMapping = $this->resolveGroupeMapping(
            $groupCreator,
            $data['groupe_mapping'],
            $etablissementId,
            $anneeScolaireId,
        );

        $context = new ImportContext(
            etablissementId: $etablissementId,
            anneeScolaireId: $anneeScolaireId,
            groupeMapping: $groupeMapping,
        );

        $batch = $importer->analyze($request->file('file'), $context, $admin);

        return redirect()->route('backoffice.import.presences.preview', $batch);
    }

    public function preview(Request $request, ImportBatch $batch): Response
    {
        $this->authorize('view', $batch);
        abort_unless($batch->module === ImportBatch::MODULE_PRESENCES, 404);

        return Inertia::render('Backoffice/Import/Presences/Preview', $this->previewProps($request, $batch));
    }

    /**
     * Commits one small chunk of the selection per call — the Preview
     * screen calls this in a loop, updating a progress bar between calls,
     * instead of one long synchronous request with no feedback.
     */
    public function commit(CommitImportRequest $request, ImportBatch $batch, PresenceImporter $importer): JsonResponse
    {
        $this->authorize('create', ImportBatch::class);
        // The batch itself must be within reach: a batch of another centre
        // is never committed/retried from here (audit SEC-05).
        $this->authorize('view', $batch);
        abort_unless($batch->module === ImportBatch::MODULE_PRESENCES, 404);

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
        abort_unless($batch->module === ImportBatch::MODULE_PRESENCES, 404);

        return Inertia::render('Backoffice/Import/Presences/Result', $this->resultProps($request, $batch));
    }

    /**
     * Re-queues previously-failed rows so the next commit pass picks them up
     * — identical to the other modules: a failed row keeps ECHEC_COMMIT and
     * is deliberately not commit-eligible, so retrying is an explicit action
     * that flips it back to CONFLIT.
     */
    public function retryFailed(Request $request, ImportBatch $batch): RedirectResponse
    {
        $this->authorize('create', ImportBatch::class);
        // The batch itself must be within reach: a batch of another centre
        // is never committed/retried from here (audit SEC-05).
        $this->authorize('view', $batch);
        abort_unless($batch->module === ImportBatch::MODULE_PRESENCES, 404);

        $requeued = $batch->rows()
            ->whereIn('status', ImportRow::RETRYABLE_STATUTS)
            ->update(['status' => ImportRow::STATUT_CONFLIT, 'errors' => null]);

        return back()->with('success', __(':count rows queued for retry.', ['count' => $requeued]));
    }

    /**
     * Resolves the posted mapping choices into ImportContext's shape,
     * eagerly creating any "action: create" group right now so every line
     * referencing that label sees a consistent, already-resolved group_id.
     *
     * Group creation goes through InscriptionImporter's
     * createGroupWithFullCatalogSync() rather than Group::create() so an
     * imported group gets the SAME full catalog-wide group_frais sync as one
     * created through the normal UI — a group born here must be
     * indistinguishable from any other, or later enrolments on it would find
     * no fees to bill.
     *
     * @param  array<int, array{label: string, action: string, group_id?: int, nom?: string, niveau?: string}>  $mapping
     * @return array<string, array{action: string, group_id?: int}>
     */
    private function resolveGroupeMapping(InscriptionImporter $groupCreator, array $mapping, int $etablissementId, int $anneeScolaireId): array
    {
        return DB::transaction(function () use ($groupCreator, $mapping, $etablissementId, $anneeScolaireId): array {
            $reaffecter = app(ReaffecterGroupeVersAnnee::class);
            $resolved = [];

            foreach ($mapping as $entry) {
                if ($entry['action'] === 'map') {
                    // Mapping onto a group from another année re-affects it
                    // (with its inscriptions/séances) to the selected year —
                    // same rule as the Inscriptions import, so one import
                    // never leaves data split across two years.
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

                $group = $groupCreator->createGroupWithFullCatalogSync([
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
     * The centre's groups from EVERY année, labeled with their year — same
     * options (and same re-affectation contract) as the Inscriptions import.
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
}
