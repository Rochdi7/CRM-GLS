<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Attendance\Actions\EnregistrerPresences;
use App\Domain\Attendance\Queries\GetSeanceDetails;
use App\Domain\Attendance\Queries\GetSeanceFormOptions;
use App\Domain\Attendance\Queries\GetSeancesList;
use App\Http\Controllers\Backoffice\Concerns\AssertsContextScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Attendance\AnnulerSeanceRequest;
use App\Http\Requests\Backoffice\Attendance\SavePresencesRequest;
use App\Http\Requests\Backoffice\Attendance\StoreSeanceRequest;
use App\Http\Requests\Backoffice\Attendance\UpdateSeanceRequest;
use App\Models\Group;
use App\Models\Presence;
use App\Models\Seance;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Attendance (Présences) — séances list + modal add/edit, and the per-séance
 * fiche de présence (Show) where the roll call is saved. Center + academic
 * year are always inherited from the séance's group, never form inputs.
 */
final class SeanceController extends Controller
{
    use AssertsContextScope;

    public function index(
        Request $request,
        GetSeancesList $getSeancesList,
        GetSeanceFormOptions $formOptions,
    ): Response {
        $this->authorize('viewAny', Seance::class);

        $filters = [
            'search' => (string) $request->string('search'),
            'groupFilter' => (string) $request->string('groupFilter'),
            'statutFilter' => (string) $request->string('statutFilter'),
            'enseignantFilter' => (string) $request->string('enseignantFilter'),
            'dateFrom' => (string) $request->string('dateFrom'),
            'dateTo' => (string) $request->string('dateTo'),
        ];

        if (! in_array($filters['statutFilter'], Seance::STATUTS, true)) {
            $filters['statutFilter'] = '';
        }

        $perPage = (int) $request->integer('perPage', GetSeancesList::DEFAULT_PER_PAGE);
        $user = $request->user();

        return Inertia::render('Backoffice/Seances/Index', [
            'seances' => $getSeancesList($user, $filters, $perPage),
            'filters' => $filters + [
                'perPage' => in_array($perPage, GetSeancesList::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetSeancesList::DEFAULT_PER_PAGE,
            ],
            'perPageOptions' => GetSeancesList::PER_PAGE_OPTIONS,
            'groupOptions' => $formOptions->groups($user),
            'enseignants' => $formOptions->enseignants(),
            'statuts' => Seance::STATUTS,
            'permissions' => [
                'create' => $user->can('create', Seance::class),
                'update' => $user->can('attendance.update'),
                'delete' => $user->can('attendance.delete'),
                'mark' => $user->can('attendance.mark'),
            ],
        ]);
    }

    public function show(
        Request $request,
        Seance $seance,
        GetSeanceDetails $getSeanceDetails,
        GetSeanceFormOptions $formOptions,
        GetSeancesList $getSeancesList,
    ): Response {
        $this->authorize('view', $seance);

        return $this->renderFicheDePresence(
            $request,
            $seance,
            $getSeanceDetails,
            $formOptions,
            $getSeancesList,
            pageUrl: route('backoffice.seances.show', $seance),
        );
    }

    /**
     * "Saisir l'absence" tab entry point from the Index list — no séance is
     * pre-selected there. Renders the same fiche de présence as show(),
     * defaulting to today's earliest séance when one exists; otherwise an
     * empty roll call with the Date/Employé/Séances pickers so the user can
     * pick another day right there — never redirects or blocks.
     */
    public function presences(
        Request $request,
        GetSeanceDetails $getSeanceDetails,
        GetSeanceFormOptions $formOptions,
        GetSeancesList $getSeancesList,
        CenterAccessService $centerAccess,
        CurrentContext $context,
    ): Response {
        $this->authorize('viewAny', Seance::class);

        $user = $request->user();
        $requestedDate = (string) $request->string('date');
        $hasExplicitDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate) === 1;

        $seance = $request->integer('seance') !== 0
            ? Seance::find($request->integer('seance'))
            : Seance::query()
                ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, $user))
                ->tap(function ($q) use ($context): void {
                    $etablissementId = $context->etablissementId();

                    if ($etablissementId !== null) {
                        $q->where(fn ($sub) => $sub->whereNull('etablissement_id')->orWhere('etablissement_id', $etablissementId));
                    }
                })
                ->when($context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
                ->whereDate('date_seance', $hasExplicitDate ? $requestedDate : now()->toDateString())
                ->when($request->filled('enseignant'), fn ($q) => $q->where('enseignant_id', $request->integer('enseignant')))
                ->orderBy('heure_debut')
                ->orderBy('id')
                ->first();

        if ($seance !== null && ! $user->can('view', $seance)) {
            $seance = null;
        }

        if ($seance === null) {
            // Non-blocking — the page still renders below with the pickers
            // and an empty roll call so the user can pick another date.
            $request->session()->flash('info', __('No session is scheduled for this date.'));
        }

        return $this->renderFicheDePresence(
            $request,
            $seance,
            $getSeanceDetails,
            $formOptions,
            $getSeancesList,
            fallbackDate: $hasExplicitDate ? $requestedDate : now()->toDateString(),
            pageUrl: route('backoffice.seances.presences'),
        );
    }

    /**
     * Shared render for the fiche de présence page (both tabs): the roll
     * call for $seance (or an empty one when null — "Saisir l'absence"
     * opened with nothing scheduled) plus the "Séances" tab's own list.
     */
    private function renderFicheDePresence(
        Request $request,
        ?Seance $seance,
        GetSeanceDetails $getSeanceDetails,
        GetSeanceFormOptions $formOptions,
        GetSeancesList $getSeancesList,
        ?string $fallbackDate = null,
        string $pageUrl = '',
    ): Response {
        $user = $request->user();

        // The Date / Employé selectors above the roll call drive the séance
        // picker; they default to the open séance's own date and teacher,
        // or today when no séance is loaded at all.
        $filterDate = (string) $request->string('date');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate) !== 1) {
            $filterDate = $seance?->date_seance->toDateString() ?? $fallbackDate ?? now()->toDateString();
        }

        $filterEnseignant = $request->has('enseignant')
            ? ($request->integer('enseignant') ?: null)
            : $seance?->enseignant_id;

        // "Séances" tab — same list/filters as Index, scoped to this page so
        // switching tabs never navigates away from the fiche de présence.
        $listFilters = [
            'search' => (string) $request->string('search'),
            'groupFilter' => (string) $request->string('groupFilter'),
            'statutFilter' => (string) $request->string('statutFilter'),
            'enseignantFilter' => (string) $request->string('enseignantFilter'),
            'dateFrom' => (string) $request->string('dateFrom'),
            'dateTo' => (string) $request->string('dateTo'),
        ];

        if (! in_array($listFilters['statutFilter'], Seance::STATUTS, true)) {
            $listFilters['statutFilter'] = '';
        }

        $perPage = (int) $request->integer('perPage', GetSeancesList::DEFAULT_PER_PAGE);

        return Inertia::render('Backoffice/Seances/Show', [
            'seance' => $seance !== null ? $getSeanceDetails($seance) : null,
            'pageUrl' => $pageUrl,
            'presenceStatuts' => Presence::STATUTS,
            'canMark' => $seance !== null && $user->can('mark', $seance),
            'canValidate' => $seance !== null && $user->can('validate', $seance),
            'canCancel' => $seance !== null && $user->can('cancel', $seance),
            'filters' => [
                'date' => $filterDate,
                'enseignant' => $filterEnseignant,
            ],
            'enseignantOptions' => $formOptions->enseignants(),
            'seanceOptions' => $formOptions->seancesFor($user, $filterDate, $filterEnseignant),
            'seances' => $getSeancesList($user, $listFilters, $perPage),
            'listFilters' => $listFilters + [
                'perPage' => in_array($perPage, GetSeancesList::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetSeancesList::DEFAULT_PER_PAGE,
            ],
            'perPageOptions' => GetSeancesList::PER_PAGE_OPTIONS,
            'groupOptions' => $formOptions->groups($user),
            'statuts' => Seance::STATUTS,
            'listPermissions' => [
                'create' => $user->can('create', Seance::class),
                'update' => $user->can('attendance.update'),
                'delete' => $user->can('attendance.delete'),
                'mark' => $user->can('attendance.mark'),
            ],
        ]);
    }

    public function store(StoreSeanceRequest $request): RedirectResponse
    {
        $this->authorize('create', Seance::class);

        $data = $request->validated();
        $group = Group::findOrFail((int) $data['group_id']);

        // The group defines where (center) and when (academic year) the
        // séance belongs — so it must itself be reachable and inside the
        // active context, or a forged group_id writes the séance into a
        // foreign centre/year (AssertsContextScope).
        $this->assertGroupInContext($request, $group);

        // Its teacher is the default when none is picked.
        Seance::create([
            'group_id' => $group->id,
            'date_seance' => $data['date_seance'],
            'heure_debut' => $data['heure_debut'] ?? null,
            'heure_fin' => $data['heure_fin'] ?? null,
            'enseignant_id' => $data['enseignant_id'] ?? $group->enseignant_id,
            'etablissement_id' => $group->etablissement_id,
            'annee_scolaire_id' => $group->annee_scolaire_id,
            'statut' => $data['statut'],
            'note' => $data['note'] ?? null,
            'created_by' => $request->user()?->employee?->id,
        ]);

        return redirect()->route('backoffice.seances.index')
            ->with('success', __('Session created.'));
    }

    public function update(UpdateSeanceRequest $request, Seance $seance): RedirectResponse
    {
        $this->authorize('update', $seance);
        $this->assertRecordInContext(
            $request,
            'statut',
            $seance->etablissement_id,
            $seance->annee_scolaire_id,
            __('This session belongs to another centre than the active one.'),
            __('This session belongs to another academic year than the active one.'),
        );

        $data = $request->validated();

        $seance->update([
            'date_seance' => $data['date_seance'],
            'heure_debut' => $data['heure_debut'] ?? null,
            'heure_fin' => $data['heure_fin'] ?? null,
            'enseignant_id' => $data['enseignant_id'] ?? null,
            'statut' => $data['statut'],
            'note' => $data['note'] ?? null,
        ]);

        return redirect()->back()
            ->with('success', __('Session updated.'));
    }

    public function destroy(Request $request, Seance $seance): RedirectResponse
    {
        $this->authorize('delete', $seance);
        $this->assertRecordInContext(
            $request,
            'statut',
            $seance->etablissement_id,
            $seance->annee_scolaire_id,
            __('This session belongs to another centre than the active one.'),
            __('This session belongs to another academic year than the active one.'),
        );

        // Roll-call lines go with the séance (FK cascade) — attendance is
        // operational data, not money; deletion is allowed by permission.
        $seance->delete();

        return redirect()->route('backoffice.seances.index')
            ->with('success', __('Session deleted.'));
    }

    public function savePresences(
        SavePresencesRequest $request,
        Seance $seance,
        EnregistrerPresences $enregistrerPresences,
    ): HttpResponse|RedirectResponse {
        $this->authorize('mark', $seance);
        $this->assertRecordInContext(
            $request,
            'statut',
            $seance->etablissement_id,
            $seance->annee_scolaire_id,
            __('This session belongs to another centre than the active one.'),
            __('This session belongs to another academic year than the active one.'),
        );

        $enregistrerPresences($seance, $request->validated('presences'));

        // The roll-call toggles auto-save via a background XHR — no Inertia
        // visit, so no redirect/flash: just acknowledge silently.
        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->route('backoffice.seances.show', $seance)
            ->with('success', __('Attendance saved.'));
    }

    public function valider(Request $request, Seance $seance): RedirectResponse
    {
        $this->authorize('validate', $seance);
        $this->assertRecordInContext(
            $request,
            'statut',
            $seance->etablissement_id,
            $seance->annee_scolaire_id,
            __('This session belongs to another centre than the active one.'),
            __('This session belongs to another academic year than the active one.'),
        );

        $seance->valider();

        return redirect()->back()
            ->with('success', __('Session confirmed.'));
    }

    public function annuler(AnnulerSeanceRequest $request, Seance $seance): RedirectResponse
    {
        $this->authorize('cancel', $seance);
        $this->assertRecordInContext(
            $request,
            'statut',
            $seance->etablissement_id,
            $seance->annee_scolaire_id,
            __('This session belongs to another centre than the active one.'),
            __('This session belongs to another academic year than the active one.'),
        );

        $seance->annuler((string) $request->validated('motif'));

        return redirect()->back()
            ->with('success', __('Session cancelled.'));
    }
}
