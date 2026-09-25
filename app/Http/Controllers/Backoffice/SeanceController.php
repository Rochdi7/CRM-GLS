<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Backoffice\Concerns\RedirectsPreservingFilters;
use App\Domain\Attendance\Actions\EnregistrerPresences;
use App\Domain\Attendance\Exports\ExporterMatriceAbsences;
use App\Domain\Attendance\Queries\GetAbsencesParGroupe;
use App\Domain\Attendance\Queries\GetSeanceDetails;
use App\Domain\Attendance\Queries\GetSeanceFormOptions;
use App\Domain\Attendance\Queries\GetSeancesList;
use App\Domain\Groups\Support\PorteeEnseignant;
use App\Http\Controllers\Backoffice\Concerns\AssertsContextScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Attendance\AnnulerSeanceRequest;
use App\Http\Requests\Backoffice\Attendance\SavePresencesRequest;
use App\Http\Requests\Backoffice\Attendance\StoreSeanceRequest;
use App\Http\Requests\Backoffice\Attendance\UpdateSeanceRequest;
use App\Models\Group;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Attendance (Présences) — séances list + modal add/edit, and the per-séance
 * fiche de présence (Show) where the roll call is saved. Center + academic
 * year are always inherited from the séance's group, never form inputs.
 */
final class SeanceController extends Controller
{
    use AssertsContextScope, RedirectsPreservingFilters;

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

        // Portée enseignant : sa propre vue en cartes par jour — il fait
        // l'appel, rien d'autre (aucun menu, aucun modal de création).
        if (PorteeEnseignant::estRestreint($user)) {
            $filters['enseignantFilter'] = '';

            return Inertia::render('Backoffice/Seances/MesSeances', [
                'seances' => $getSeancesList($user, $filters, 25),
                'filters' => $filters,
                'groupOptions' => $formOptions->allGroups($user),
                'statuts' => Seance::STATUTS,
                'today' => now()->toDateString(),
            ]);
        }

        return Inertia::render('Backoffice/Seances/Index', [
            'seances' => $getSeancesList($user, $filters, $perPage),
            'filters' => $filters + [
                'perPage' => in_array($perPage, GetSeancesList::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetSeancesList::DEFAULT_PER_PAGE,
            ],
            'perPageOptions' => GetSeancesList::PER_PAGE_OPTIONS,
            'groupOptions' => $formOptions->groups($user),
            'enseignants' => $formOptions->enseignants($user),
            'statuts' => Seance::STATUTS,
            // Cancellation reasons for the "Annuler la séance" form, read from
            // the managed catalog (Paramètres → Raisons d'annulation ou
            // archivage) — never a hard-coded list. A closure so a partial
            // reload that doesn't ask for it skips the query (CLAUDE.md §17).
            'motifsAnnulation' => fn (): array => AnnulerSeanceRequest::motifs(),
            'permissions' => [
                'create' => $user->can('create', Seance::class),
                'update' => $user->can('attendance.update'),
                'delete' => $user->can('attendance.delete'),
                'mark' => $user->can('attendance.mark'),
                // Même règle que SeancePolicy@cancel (UI seulement, §5).
                'cancel' => $user->can('attendance.mark') && $user->can('attendance.update'),
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
                // Portée enseignant : un prof arrive sur SA séance du jour.
                ->tap(fn ($q) => PorteeEnseignant::scopeSeances($q, $user))
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
     * « Absence par groupe » tab — the presence MATRIX of one group: students
     * in rows, the séances of the date window in columns, P/A in the cells.
     * Read-only, so no pagination and no context WRITE guard; the group is
     * still scoped by centre reach + active context inside the query.
     */
    public function absenceParGroupe(
        Request $request,
        GetAbsencesParGroupe $getAbsencesParGroupe,
        GetSeanceFormOptions $formOptions,
    ): Response|RedirectResponse {
        $this->authorize('viewAny', Seance::class);

        $user = $request->user();
        $filters = $this->absenceFilters($request);

        // Fenêtre par défaut (24/09/2026) : un groupe choisi SANS aucune clé
        // de date (la page les retire au changement de groupe ; lien « Absences »
        // d'une carte de groupe) ⇒ redirection vers l'URL canonique portant
        // explicitement « 22 dernières séances → aujourd'hui ». Une date que
        // l'utilisateur a EFFACÉE arrive, elle, en clé vide et reste effacée —
        // jamais de défaut réinjecté en lecture (voir EncaissementController).
        if ($filters['groupFilter'] !== '' && ! $request->has('dateFrom') && ! $request->has('dateTo')) {
            return redirect()->route('backoffice.seances.absence-par-groupe', [
                ...$filters,
                'dateFrom' => $getAbsencesParGroupe->debutFenetreParDefaut($user, (int) $filters['groupFilter']) ?? '',
                'dateTo' => now()->toDateString(),
            ]);
        }

        return Inertia::render('Backoffice/Seances/AbsenceParGroupe', [
            'matrice' => $getAbsencesParGroupe($user, $filters),
            'filters' => $filters,
            // allGroups(), not groups(): reading a finished group's attendance
            // is exactly when one consults this matrix, so « Fin de formation »
            // and « Annulée » belong in the dropdown here — unlike the séance
            // modal, which may only schedule into a live group.
            'groupOptions' => $formOptions->allGroups($user),
            'statuts' => Seance::STATUTS,
            'presenceStatuts' => Presence::STATUTS,
        ]);
    }

    /**
     * Same matrix as a downloadable .xlsx — one column per séance, exactly
     * what the page shows for the CURRENT filters (they travel in the query
     * string), so the file can never disagree with the screen.
     */
    public function absenceParGroupeExport(
        Request $request,
        GetAbsencesParGroupe $getAbsencesParGroupe,
        ExporterMatriceAbsences $exporter,
    ): StreamedResponse {
        $this->authorize('viewAny', Seance::class);

        $filters = $this->absenceFilters($request);

        if ($filters['groupFilter'] === '') {
            throw ValidationException::withMessages([
                'groupFilter' => __('Please choose a group first.'),
            ]);
        }

        // Centre reach, not `groups.view`: this is an attendance screen, and
        // its reader holds attendance.* — the group is only the axis of the
        // matrix. assertGroupInContext also refuses a group belonging to a
        // year/centre the current screen does not show (AssertsContextScope).
        $group = Group::findOrFail((int) $filters['groupFilter']);
        $this->assertGroupInContext($request, $group);

        return $exporter(
            $getAbsencesParGroupe($request->user(), $filters),
            $group,
            $filters,
        );
    }

    /**
     * @return array{groupFilter: string, dateFrom: string, dateTo: string, statutFilter: string}
     */
    private function absenceFilters(Request $request): array
    {
        $filters = [
            'groupFilter' => (string) $request->string('groupFilter'),
            'dateFrom' => (string) $request->string('dateFrom'),
            'dateTo' => (string) $request->string('dateTo'),
            'statutFilter' => (string) $request->string('statutFilter'),
        ];

        if (! in_array($filters['statutFilter'], Seance::STATUTS, true)) {
            $filters['statutFilter'] = '';
        }

        foreach (['dateFrom', 'dateTo'] as $key) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters[$key]) !== 1) {
                $filters[$key] = '';
            }
        }

        return $filters;
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

        // ⚠ La séance AFFICHÉE doit toujours correspondre aux filtres, sinon
        // l'écran se contredit (signalé le 14/09/2026, capture à l'appui).
        //
        // Sur `show()` la séance vient du paramètre de ROUTE et n'était jamais
        // ré-résolue : changer « Employé » rechargeait la même URL avec un
        // `?enseignant=` différent, le sélecteur était reconstruit par
        // `seancesFor($filterDate, $filterEnseignant)` — donc SANS la séance
        // chargée — et la page affichait « Choisir une séance… » au-dessus de
        // l'appel d'un AUTRE enseignant. La liste d'étudiants ne changeait
        // pas, puisque GetSeanceDetails la construit depuis le groupe de la
        // séance restée en place.
        //
        // On ré-résout donc vers la première séance qui satisfait les filtres
        // (même tri que le sélecteur : heure_debut puis id, pour que « la
        // séance affichée » soit toujours la première option proposée).
        // `presences()` faisait déjà ce travail ; la règle vit désormais ICI,
        // à l'endroit partagé par les DEUX entrées, pour qu'elles ne puissent
        // plus diverger.
        if ($seance !== null && ! $this->seanceMatchesFilters($seance, $filterDate, $filterEnseignant)) {
            $seance = $this->premiereSeancePour($user, $filterDate, $filterEnseignant);
        }

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
            // Same managed catalog as the list page's cancel modal.
            'motifsAnnulation' => fn (): array => AnnulerSeanceRequest::motifs(),
            'filters' => [
                'date' => $filterDate,
                'enseignant' => $filterEnseignant,
            ],
            'enseignantOptions' => $formOptions->enseignants($user),
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
                // Même règle que SeancePolicy@cancel (UI seulement, §5).
                'cancel' => $user->can('attendance.mark') && $user->can('attendance.update'),
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

        return $this->backToListPreservingFilters($request, 'backoffice.seances.index')
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

        // A validated séance, or one with a roll-call, is attendance
        // history: refused rather than cascaded away (audit CRUD-F15).
        if ($seance->statut === Seance::STATUT_EFFECTUEE || $seance->presences()->exists()) {
            throw ValidationException::withMessages([
                'delete' => __('This session has been validated or has attendance recorded and cannot be deleted.'),
            ]);
        }

        $seance->delete();

        return $this->backToListPreservingFilters($request, 'backoffice.seances.index')
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

    /**
     * La séance chargée satisfait-elle les filtres Date / Employé ?
     *
     * « Aucun enseignant choisi » (null) n'exclut rien : c'est le cas où
     * l'utilisateur regarde toutes les séances de la journée.
     */
    private function seanceMatchesFilters(Seance $seance, string $date, ?int $enseignantId): bool
    {
        if ($seance->date_seance->toDateString() !== $date) {
            return false;
        }

        return $enseignantId === null || $seance->enseignant_id === $enseignantId;
    }

    /**
     * La première séance correspondant aux filtres, dans le MÊME ordre que
     * le sélecteur (`GetSeanceFormOptions::seancesFor`) — sans quoi la séance
     * affichée ne serait pas celle que l'utilisateur voit en tête de liste.
     *
     * Mêmes bornes que partout ailleurs : portée de l'utilisateur, centre
     * actif, année active (§11), puis la policy `view` — une ré-résolution ne
     * doit jamais ouvrir une séance que l'utilisateur n'aurait pas le droit
     * d'ouvrir en tapant son URL.
     */
    private function premiereSeancePour(User $user, string $date, ?int $enseignantId): ?Seance
    {
        $centerAccess = app(CenterAccessService::class);
        $context = app(CurrentContext::class);

        $seance = Seance::query()
            ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(function ($q) use ($context): void {
                $etablissementId = $context->etablissementId();

                if ($etablissementId !== null) {
                    $q->where(fn ($sub) => $sub->whereNull('etablissement_id')->orWhere('etablissement_id', $etablissementId));
                }
            })
            ->when($context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->whereDate('date_seance', $date)
            ->when($enseignantId, fn ($q, $id) => $q->where('enseignant_id', $id))
            ->orderBy('heure_debut')
            ->orderBy('id')
            ->first();

        return $seance !== null && $user->can('view', $seance) ? $seance : null;
    }
}
