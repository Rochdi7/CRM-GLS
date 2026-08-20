<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Groups\Actions\ChangerEnseignantGroupe;
use App\Domain\Groups\Queries\GetGroupDetails;
use App\Domain\Groups\Queries\GetGroupFormOptions;
use App\Domain\Groups\Queries\GetGroupsList;
use App\Domain\Groups\Queries\GetGroupStudentsBySegment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Groups\ChangerEnseignantRequest;
use App\Http\Requests\Backoffice\Groups\StoreGroupRequest;
use App\Http\Requests\Backoffice\Groups\UpdateGroupEnseignantRequest;
use App\Http\Requests\Backoffice\Groups\UpdateGroupRequest;
use App\Domain\Settings\Support\FraisEcheanceResolver;
use App\Models\Frais;
use App\Models\Group;
use App\Models\GroupEnseignant;
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

    public function show(
        Request $request,
        Group $group,
        GetGroupDetails $getGroupDetails,
        GetGroupFormOptions $getGroupFormOptions,
    ): Response {
        $this->authorize('view', $group);

        return Inertia::render('Backoffice/Groups/Show', [
            'group' => $getGroupDetails($group, $request->user()),
            // Options for the "Changer d'enseignant" modal.
            'enseignants' => $getGroupFormOptions->enseignants(),
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
        $fraisLignes = $this->normalizedFraisLignes($request, $data['date_debut_formation'] ?? null);

        DB::transaction(function () use ($request, $data, $fraisLignes, &$group): void {
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

            // Opens the group's first teaching-assignment period so the
            // payroll trail starts at creation, not at the first change.
            app(ChangerEnseignantGroupe::class)->ouvrirInitiale(
                $group,
                $data['enseignant_id'] ?? null,
                $request->user()?->employee,
            );
        });

        return redirect()->route('backoffice.groups.index')
            ->with('success', __('Group created.'));
    }

    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);

        $data = $request->validated();
        $fraisLignes = $this->normalizedFraisLignes(
            $request,
            $data['date_debut_formation'] ?? $group->date_debut_formation?->toDateString(),
        );

        $changement = null;

        DB::transaction(function () use ($request, $data, $fraisLignes, $group, &$changement): void {
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
                'statut' => $statut,
                'date_debut_formation' => $data['date_debut_formation'] ?? null,
                'date_fin_formation' => $data['date_fin_formation'] ?? null,
            ]);

            // `enseignant_id` is NEVER written straight from the form: a
            // teacher change has to archive the outgoing assignment, open a
            // new one and stop the emploi du temps — all of which lives in
            // ChangerEnseignantGroupe. When the teacher is unchanged the
            // action is a no-op, so the plain edit modal stays harmless.
            //
            // When it IS a change, the modal has revealed the same two fields
            // as « Changer d'enseignant » on the detail page, so the archived
            // period carries a real changeover date and motif instead of a
            // silent "today" with no reason. A blank date still falls back to
            // today inside the action.
            $changement = app(ChangerEnseignantGroupe::class)(
                $group,
                isset($data['enseignant_id']) ? (int) $data['enseignant_id'] : null,
                $data['enseignant_date_debut'] ?? null,
                $data['enseignant_motif'] ?? null,
                $request->user()?->employee,
            );

            // Every catalog fee is assigned to the group (no checkbox): each
            // line carries the amount entered for this group — 0 DH included.
            $group->frais()->sync($fraisLignes);
        });

        $redirect = redirect()->route('backoffice.groups.index')
            ->with('success', __('Group updated.'));

        // A teacher swap made from the list page stops the emploi du temps
        // just like the detail-page flow does — tell the user what stopped and
        // where to rebuild it, with the very same flash payload.
        if ($changement !== null && $changement['changed']) {
            $redirect->with('emploiDuTempsArrete', [
                'creneaux' => $changement['creneauxFermes'],
                'seances' => $changement['seancesSupprimees'],
                'url' => route('backoffice.emploi-du-temps.index', ['group' => $group->id]),
            ]);
        }

        return $redirect;
    }

    /**
     * Explicit teacher changeover from the group detail page: archives the
     * outgoing assignment with its date_fin, opens the incoming one with its
     * date_debut, and STOPS the group's emploi du temps so a fresh schedule
     * is built for the new teacher (per-teacher séance separation → payroll).
     * Gated by groups.update, like every other edit of a group.
     */
    public function changerEnseignant(
        ChangerEnseignantRequest $request,
        Group $group,
        ChangerEnseignantGroupe $changerEnseignant,
    ): RedirectResponse {
        $this->authorize('update', $group);

        $data = $request->validated();

        $result = $changerEnseignant(
            $group,
            isset($data['enseignant_id']) ? (int) $data['enseignant_id'] : null,
            $data['date_debut'],
            $data['motif'] ?? null,
            $request->user()?->employee,
        );

        if (! $result['changed']) {
            return back()->with('info', __('This teacher is already assigned to the group.'));
        }

        return redirect()->route('backoffice.groups.show', $group)
            ->with('success', __('Teacher changed. Create a new timetable for the new teacher.'))
            ->with('emploiDuTempsArrete', [
                'creneaux' => $result['creneauxFermes'],
                'seances' => $result['seancesSupprimees'],
                'url' => route('backoffice.emploi-du-temps.index', ['group' => $group->id]),
            ]);
    }

    /**
     * Corrects an already-recorded assignment period (dates / motif) from
     * the "Historique des affectations" table — the typing mistake escape
     * hatch, since a changeover stamps date_fin with "today" and the real
     * handover may have happened on another day.
     *
     * Deliberately narrow: only date_debut / date_fin / motif are writable.
     * The row's teacher is NEVER swapped here (that has to archive the old
     * period, open a new one and stop the emploi du temps —
     * ChangerEnseignantGroupe), and `statut` stays derived from the row's
     * place in the chain. Assignment rows are still never deleted.
     *
     * The chain must stay ordered, so an edit may not make a period overlap
     * its neighbours; the Actif row keeps its open date_fin (NULL).
     */
    public function updateEnseignantAffectation(
        UpdateGroupEnseignantRequest $request,
        Group $group,
        GroupEnseignant $affectation,
    ): RedirectResponse {
        $this->authorize('update', $group);

        abort_unless($affectation->group_id === $group->id, 404);

        $data = $request->validated();

        // The single Actif period is by definition still running — it can
        // never be given an end date from this form.
        $dateFin = $affectation->isActif() ? null : ($data['date_fin'] ?? null);

        $affectation->update([
            'date_debut' => $data['date_debut'],
            'date_fin' => $dateFin,
            'motif' => $data['motif'] ?? null,
        ]);

        // groups.enseignant_id mirrors the Actif row only, and the teacher
        // is untouched here — so nothing else needs re-syncing.
        return redirect()->route('backoffice.groups.show', $group)
            ->with('success', __('Assignment period updated.'));
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
            ->with('success', __('Group archived (Fin de formation).'));
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
     * Quick row-menu action from the list — reverses activer(), bringing a
     * group back to "En inscription". Only offered from the En formation tab
     * in the UI, and only ever fires from that status.
     */
    public function retournerEnInscription(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('archive', $group);

        if ($group->statut !== Group::STATUT_EN_FORMATION) {
            return back();
        }

        $group->retournerEnInscription();

        return redirect()->route('backoffice.groups.index')
            ->with('success', __('Group returned to En inscription.'));
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
     * A blank amount/échéance falls back to the catalog's own default
     * (frais.montant_defaut) and to the month-derived due date — see
     * FraisEcheanceResolver — so a group only has to type the values that
     * actually differ from the standard.
     *
     * @return array<int, array{montant: float, date_echeance: ?string, classification: ?string}>
     */
    private function normalizedFraisLignes(Request $request, ?string $dateDebutFormation = null): array
    {
        // Whole catalog rows, not just ids: each one carries the default
        // amount a fee is worth unless this group says otherwise.
        $catalogue = Frais::query()
            ->where('statut', Frais::STATUT_ACTIF)
            ->get(['id', 'nom', 'montant_defaut']);

        $submitted = (array) $request->input('fraisLignes', []);

        $sync = [];
        foreach ($catalogue as $frais) {
            $ligne = $submitted[$frais->id] ?? [];

            // Blank means "use the catalog default" — not "free". A zero the
            // user typed on purpose is still respected, since '0' !== ''.
            $montant = ($ligne['montant'] ?? '') !== ''
                ? (float) $ligne['montant']
                : (float) $frais->montant_defaut;

            $echeance = ($ligne['date_echeance'] ?? '') !== ''
                ? $ligne['date_echeance']
                : FraisEcheanceResolver::defaultFor($frais->nom, $dateDebutFormation);

            $sync[$frais->id] = [
                'montant' => $montant,
                'date_echeance' => $echeance,
                'classification' => ($ligne['classification'] ?? '') !== '' ? $ligne['classification'] : null,
            ];
        }

        return $sync;
    }
}
