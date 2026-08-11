<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Groups\Queries\GetGroupDetails;
use App\Domain\Groups\Queries\GetGroupFormOptions;
use App\Domain\Groups\Queries\GetGroupsList;
use App\Domain\Groups\Queries\GetGroupStudentsBySegment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Groups\StoreGroupRequest;
use App\Http\Requests\Backoffice\Groups\UpdateGroupRequest;
use App\Models\Frais;
use App\Models\Group;
use App\Services\Context\CurrentContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Groups list + modal add/edit (Phase 8, docs/phase-8-students-groups-
 * inventory.md) + the read-only detail page and "Fin de formation" archive
 * action (Phase 5, unchanged). The list + create/update mutations mirror
 * App\Livewire\Backoffice\Groups\GroupsIndex one-for-one — migrated exactly
 * as the current Livewire form exists, no room/capacity/schedule fields
 * (confirmed absent from the live UI). Groups are NEVER deleted (schema §6)
 * — no destroy route exists, by design.
 */
final class GroupController extends Controller
{
    public function index(
        Request $request,
        GetGroupsList $getGroupsList,
        GetGroupFormOptions $getGroupFormOptions,
    ): Response {
        $this->authorize('viewAny', Group::class);

        $search = (string) $request->string('search');
        $statutFilter = (string) $request->string('statutFilter', Group::STATUT_EN_FORMATION);
        $enseignantFilter = (string) $request->string('enseignantFilter');
        $dateFrom = (string) $request->string('dateFrom');
        $dateTo = (string) $request->string('dateTo');
        $perPage = (int) $request->integer('perPage', GetGroupsList::DEFAULT_PER_PAGE);

        if (! in_array($statutFilter, Group::STATUTS, true)) {
            $statutFilter = Group::STATUT_EN_FORMATION;
        }

        return Inertia::render('Backoffice/Groups/Index', [
            'groups' => $getGroupsList($request->user(), $search, $statutFilter, $perPage, $enseignantFilter, $dateFrom, $dateTo),
            'statutCounts' => $getGroupsList->statutCounts($request->user()),
            'filters' => [
                'search' => $search,
                'statutFilter' => $statutFilter,
                'enseignantFilter' => $enseignantFilter,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'perPage' => in_array($perPage, GetGroupsList::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetGroupsList::DEFAULT_PER_PAGE,
            ],
            'perPageOptions' => GetGroupsList::PER_PAGE_OPTIONS,
            'niveaux' => Group::NIVEAUX,
            'statuts' => Group::STATUTS,
            'enseignants' => $getGroupFormOptions->enseignants(),
            'fraisCatalog' => $getGroupFormOptions->fraisCatalog(),
        ]);
    }

    public function show(Request $request, Group $group, GetGroupDetails $getGroupDetails): Response
    {
        $this->authorize('view', $group);

        return Inertia::render('Backoffice/Groups/Show', [
            'group' => $getGroupDetails($group, $request->user()),
        ]);
    }

    /**
     * Drills into one of the list's "Statistique" badges — the student rows
     * behind the total/active/annulée/distinct-students count that was
     * clicked (GetGroupStudentsBySegment).
     */
    public function studentsBySegment(Request $request, Group $group, GetGroupStudentsBySegment $getGroupStudentsBySegment): JsonResponse
    {
        $this->authorize('view', $group);

        $segment = (string) $request->string('segment');

        if (! in_array($segment, GetGroupStudentsBySegment::SEGMENTS, true)) {
            $segment = GetGroupStudentsBySegment::SEGMENT_TOTAL;
        }

        return response()->json(['students' => $getGroupStudentsBySegment($group, $segment)]);
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $this->authorize('create', Group::class);

        $data = $request->validated();
        $fraisLignes = $this->normalizedFraisLignes($request);

        DB::transaction(function () use ($data, $fraisLignes, &$group): void {
            // Center + academic year are inherited from the active working
            // context (top-bar switchers), never picked in the form — same
            // as GroupsIndex::save().
            $context = app(CurrentContext::class);

            $group = Group::create([
                'nom' => $data['nom'],
                'niveau' => $data['niveau'],
                'enseignant_id' => $data['enseignant_id'] ?? null,
                'statut' => $data['statut'],
                'date_debut_formation' => $data['date_debut_formation'] ?? null,
                'date_fin_formation' => $data['date_fin_formation'] ?? null,
                'etablissement_id' => $context->etablissementId(),
                'annee_scolaire_id' => $context->anneeScolaireId(),
            ]);

            $group->frais()->sync($fraisLignes);
        });

        return redirect()->route('backoffice.groups.index')
            ->with('success', __('Group created.'));
    }

    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);

        $data = $request->validated();
        $fraisLignes = $this->normalizedFraisLignes($request);

        DB::transaction(function () use ($data, $fraisLignes, $group): void {
            $statut = $data['statut'];

            // A raw statut change to either terminal status (Fin de
            // formation or Annulée) must go through archive()/annuler() —
            // block it here, exactly as GroupsIndex::save() does for Fin de
            // formation, so the groups_historique snapshot always stays in
            // sync with the live statut.
            if (in_array($statut, Group::STATUTS_HISTORIQUE, true) && ! in_array($group->statut, Group::STATUTS_HISTORIQUE, true)) {
                $statut = $group->statut;
            }

            $group->update([
                'nom' => $data['nom'],
                'niveau' => $data['niveau'],
                'enseignant_id' => $data['enseignant_id'] ?? null,
                'statut' => $statut,
                'date_debut_formation' => $data['date_debut_formation'] ?? null,
                'date_fin_formation' => $data['date_fin_formation'] ?? null,
            ]);

            // Every catalog fee is assigned to the group (no checkbox): each
            // line carries the amount entered for this group — 0 DH included.
            $group->frais()->sync($fraisLignes);
        });

        return redirect()->route('backoffice.groups.index')
            ->with('success', __('Group updated.'));
    }

    /**
     * Transition to "Fin de formation" — writes the groups_historique
     * snapshot in the same transaction (Group::archiverCommeTermine).
     */
    public function archive(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('archive', $group);

        if ($group->statut === Group::STATUT_FIN_FORMATION) {
            return back();
        }

        $group->archiverCommeTermine($request->user()?->employee);

        return redirect()->route('backoffice.groups.show', $group)
            ->with('status', __('Group archived (Fin de formation).'));
    }

    /**
     * Quick row-menu action from the list — cancels a group (never actually
     * ran), moving it into the Historique tab alongside "Fin de formation"
     * groups. Only offered from the "En formation" tab in the UI (never a
     * bare "En inscription" group, and never an already-terminal one), same
     * groups.archive permission gate as the existing Terminer action.
     */
    public function annuler(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('archive', $group);

        if (in_array($group->statut, Group::STATUTS_HISTORIQUE, true)) {
            return back();
        }

        $group->annuler($request->user()?->employee);

        return redirect()->route('backoffice.groups.index')
            ->with('success', __('Group cancelled.'));
    }

    /**
     * Quick row-menu action from the list — reverses annuler(), bringing a
     * cancelled group back to "En inscription". Only offered from the
     * Historique tab in the UI, and only ever fires from Annulée (never
     * from Fin de formation, which stays a one-way transition).
     */
    public function reactiver(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('archive', $group);

        if ($group->statut !== Group::STATUT_ANNULEE) {
            return back();
        }

        $group->reactiver();

        return redirect()->route('backoffice.groups.index')
            ->with('success', __('Group reactivated.'));
    }

    /**
     * Quick row-menu action from the list — starts the training, moving a
     * group from "En inscription" to "En formation". Only offered from the
     * En inscription tab in the UI, and only ever fires from that status.
     */
    public function activer(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('archive', $group);

        if ($group->statut !== Group::STATUT_EN_INSCRIPTION) {
            return back();
        }

        $group->activer();

        return redirect()->route('backoffice.groups.index')
            ->with('success', __('Group started.'));
    }

    /**
     * Builds the group_frais sync payload from the request's `fraisLignes`
     * array — one entry per ACTIVE catalog fee (matching
     * GroupsIndex::initFraisLignes()'s full-catalog assignment), validated
     * server-side against the real catalog rather than trusted from the
     * client (a stale/forged frais_id key is silently ignored, same net
     * effect as the Livewire version which only ever renders active-catalog
     * keys in the first place).
     *
     * @return array<int, array{montant: float, date_echeance: ?string, classification: ?string}>
     */
    private function normalizedFraisLignes(Request $request): array
    {
        $activeIds = Frais::query()->where('statut', Frais::STATUT_ACTIF)->pluck('id');
        $submitted = (array) $request->input('fraisLignes', []);

        $sync = [];
        foreach ($activeIds as $fraisId) {
            $ligne = $submitted[$fraisId] ?? [];
            $sync[$fraisId] = [
                'montant' => ($ligne['montant'] ?? '') !== '' ? (float) $ligne['montant'] : 0,
                'date_echeance' => ($ligne['date_echeance'] ?? '') !== '' ? $ligne['date_echeance'] : null,
                'classification' => ($ligne['classification'] ?? '') !== '' ? $ligne['classification'] : null,
            ];
        }

        return $sync;
    }
}
