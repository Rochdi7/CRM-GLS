<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Registrations\Actions\AssignerLivresInscription;
use App\Domain\Registrations\Actions\BasculerVisibiliteFraisInscription;
use App\Domain\Registrations\Actions\ChangerGroupeInscription;
use App\Domain\Registrations\Actions\MettreAJourFraisInscription;
use App\Domain\Registrations\Queries\GetGroupInscriptionFees;
use App\Domain\Registrations\Queries\GetInscriptionDetails;
use App\Domain\Registrations\Queries\GetInscriptionFormOptions;
use App\Domain\Registrations\Queries\GetInscriptionsList;
use App\Domain\Shared\Support\ReferenceGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Inscriptions\ChangeGroupInscriptionRequest;
use App\Http\Requests\Backoffice\Inscriptions\StoreInscriptionRequest;
use App\Http\Requests\Backoffice\Inscriptions\UpdateInscriptionFeesRequest;
use App\Http\Requests\Backoffice\Inscriptions\UpdateInscriptionLivresRequest;
use App\Http\Requests\Backoffice\Inscriptions\UpdateInscriptionRequest;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\StockArticle;
use App\Models\StockType;
use App\Services\Context\CurrentContext;
use App\Support\Phone\Countries;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Registrations (inscriptions) list + modal add/edit with manual fee lines
 * (Phase 9, docs/phase-9-inscriptions-audit.md +
 * docs/phase-9-inscriptions-mapping.md) — mirrors
 * App\Livewire\Backoffice\Inscriptions\InscriptionsIndex one-for-one for the
 * base fields (group-derived dates only apply on create; edit's own
 * update() still only ever touches its original 6 columns). Fee lines are
 * a later addition on top of that base behavior: create-time fee lines
 * still work exactly as before, and editing an existing registration's fees
 * is now a separate, explicit action (updateFees(), gated by
 * registrations.manage-fees) rather than silently unsupported. The Livewire
 * component and its view are left completely untouched as unreferenced
 * fallback code.
 */
final class InscriptionController extends Controller
{
    public function index(
        Request $request,
        GetInscriptionsList $getInscriptionsList,
        GetInscriptionFormOptions $getInscriptionFormOptions,
    ): Response {
        $this->authorize('viewAny', Inscription::class);

        $search = (string) $request->string('search');
        $statutFilter = (string) $request->string('statutFilter');
        $groupFilter = (string) $request->string('groupFilter');
        $referenceFilter = (string) $request->string('referenceFilter');
        $studentFilter = (string) $request->string('studentFilter');
        $perPage = (int) $request->integer('perPage', GetInscriptionsList::DEFAULT_PER_PAGE);

        return Inertia::render('Backoffice/Inscriptions/Index', [
            'inscriptions' => $getInscriptionsList($request->user(), $search, $statutFilter, $groupFilter, $perPage, $referenceFilter, $studentFilter),
            'filters' => [
                'search' => $search,
                'statutFilter' => $statutFilter,
                'groupFilter' => $groupFilter,
                'referenceFilter' => $referenceFilter,
                'studentFilter' => $studentFilter,
                'perPage' => in_array($perPage, GetInscriptionsList::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetInscriptionsList::DEFAULT_PER_PAGE,
            ],
            'perPageOptions' => GetInscriptionsList::PER_PAGE_OPTIONS,
            'statuts' => Inscription::STATUTS,
            'niveaux' => Student::NIVEAUX,
            'niveauxInteret' => Student::NIVEAUX_TRACKS,
            'domaines' => Student::DOMAINES,
            'examenTypes' => Student::EXAMEN_TYPES,
            'sexes' => Student::SEXES,
            'parentRelations' => Student::PARENT_RELATIONS,
            'niveauxAvecDomaine' => Student::NIVEAUX_AVEC_DOMAINE,
            'niveauStudium' => Student::NIVEAU_STUDIUM,
            'defaultCountry' => Countries::DEFAULT,
            'students' => $getInscriptionFormOptions->students($request->user()),
            'groups' => $getInscriptionFormOptions->groups($request->user()),
            'frais' => $getInscriptionFormOptions->frais(),
            'canManageFees' => $request->user()->can('registrations.manage-fees'),
            'canChangeGroup' => $request->user()->can('registrations.change-group'),
        ]);
    }

    public function show(Inscription $inscription, GetInscriptionDetails $getInscriptionDetails): Response
    {
        $this->authorize('view', $inscription);

        return Inertia::render('Backoffice/Inscriptions/Show', [
            'inscription' => $getInscriptionDetails($inscription),
        ]);
    }

    /**
     * Editable fee list for the edit modal — raw amounts/ids (not the
     * French-display-formatted shape GetInscriptionDetails builds for the
     * read-only Show page), so the client can prefill an editable form and
     * PUT it straight back to updateFees() below. Same `view` gate as
     * show() — seeing the list only needs the parent Inscription's view
     * permission; actually saving changes needs registrations.manage-fees.
     */
    public function fees(Inscription $inscription): JsonResponse
    {
        $this->authorize('view', $inscription);

        return response()->json([
            'fees' => $inscription->fees()->whereNull('masque_le')->get()->map(fn (InscriptionFee $fee): array => [
                'id' => $fee->id,
                'fraisId' => $fee->frais_id,
                'nom' => $fee->nom,
                'montantInitial' => (string) $fee->montant_initial,
                'remisePct' => $fee->remise_pct !== null ? (string) $fee->remise_pct : '',
                'remiseMontant' => $fee->remise_montant !== null ? (string) $fee->remise_montant : '',
                'note' => $fee->note ?? '',
                'dateEcheance' => $fee->date_echeance?->toDateString() ?? '',
                'statut' => $fee->statut,
                // Informational only (never submitted back) — drives the
                // "Reste à payer" column in the edit table.
                'paye' => number_format($fee->montantPaye(), 2, '.', ''),
            ])->values(),
            // Hidden fees — feeds the edit modal's "Frais masqués" list, the
            // only place a hidden fee can be restored from.
            'hiddenFees' => $inscription->fees()->whereNotNull('masque_le')->get()->map(fn (InscriptionFee $fee): array => [
                'id' => $fee->id,
                'nom' => $fee->nom,
                'montant' => (string) $fee->montant,
                'dateEcheance' => $fee->date_echeance?->toDateString() ?? '',
            ])->values(),
        ]);
    }

    /**
     * Hides a fee line instead of deleting it — replaces the old hard-delete
     * that used to happen implicitly through updateFees()'s "omitted from
     * the payload = delete" sweep. The row and its payment history stay
     * intact; only masque_le is set (BasculerVisibiliteFraisInscription).
     */
    public function hideFee(
        Inscription $inscription,
        InscriptionFee $fee,
        BasculerVisibiliteFraisInscription $action,
    ): JsonResponse {
        $this->authorize('view', $inscription);

        // Guarded here rather than letting the action throw a
        // ValidationException: bootstrap/app.php only renders JSON error
        // responses for `api/*`, so a validation failure on this JSON
        // endpoint would be rendered down the HTML/Inertia redirect path and
        // blow up. The fee simply not belonging to the inscription is a
        // tampered request, not a user-correctable form error — 404 is the
        // honest answer.
        abort_unless($fee->inscription_id === $inscription->id, 404);

        $action->hide($inscription, $fee);

        // JSON, not back(): this fires from INSIDE the open edit modal, whose
        // React state already reflects the change optimistically. An Inertia
        // redirect (even a `back()` with preserveState) re-runs index() and
        // rebuilds the whole page payload — the paginated list plus the
        // students / groups / frais option catalogs — just to hide one row,
        // which is what made removing a fee feel slow. Nothing on the page
        // behind the modal depends on this, so the client only needs the
        // acknowledgement.
        return response()->json([
            'ok' => true,
            'montantTotal' => $inscription->fresh()->montant_total,
        ]);
    }

    public function restoreFee(
        Inscription $inscription,
        InscriptionFee $fee,
        BasculerVisibiliteFraisInscription $action,
    ): JsonResponse {
        $this->authorize('view', $inscription);

        // Guarded here rather than letting the action throw a
        // ValidationException: bootstrap/app.php only renders JSON error
        // responses for `api/*`, so a validation failure on this JSON
        // endpoint would be rendered down the HTML/Inertia redirect path and
        // blow up. The fee simply not belonging to the inscription is a
        // tampered request, not a user-correctable form error — 404 is the
        // honest answer.
        abort_unless($fee->inscription_id === $inscription->id, 404);

        $action->restore($inscription, $fee);

        // JSON — see hideFee(). The restored line's full shape comes back
        // here too, so the client can splice it straight into the table
        // instead of re-fetching the whole fee list afterwards.
        return response()->json([
            'ok' => true,
            'montantTotal' => $inscription->fresh()->montant_total,
            'fee' => [
                'id' => $fee->id,
                'fraisId' => $fee->frais_id,
                'nom' => $fee->nom,
                'montantInitial' => (string) $fee->montant_initial,
                'remisePct' => $fee->remise_pct !== null ? (string) $fee->remise_pct : '',
                'remiseMontant' => $fee->remise_montant !== null ? (string) $fee->remise_montant : '',
                'note' => $fee->note ?? '',
                'dateEcheance' => $fee->date_echeance?->toDateString() ?? '',
                'statut' => $fee->statut,
                'paye' => number_format($fee->montantPaye(), 2, '.', ''),
            ],
        ]);
    }

    /**
     * Full replacement of an existing inscription's fee lines — the
     * registrations.manage-fees permission's first live use (previously
     * only checked by a dead controller, see docs/phase-9-inscriptions-
     * audit.md §12 point 1). Deliberately unrestricted: a fee already paid
     * or partially paid can still be edited or removed (product decision);
     * removing a line with payments is refused by MettreAJourFraisInscription
     * via the encaissements FK-restrict, surfaced as a field error.
     */
    public function updateFees(
        UpdateInscriptionFeesRequest $request,
        Inscription $inscription,
        MettreAJourFraisInscription $action,
    ): RedirectResponse {
        // Center-scoping still applies (must be able to see this
        // inscription at all) but the actual permission to mutate fees is
        // registrations.manage-fees — already enforced by the route
        // middleware, deliberately NOT also requiring registrations.update.
        $this->authorize('view', $inscription);

        $action->handle($inscription, $request->validated('fee_lines', []));

        return redirect()->route('backoffice.inscriptions.index')
            ->with('success', __('Registration fees updated.'));
    }

    /**
     * "Frais disponibles" for a group — the create form's live group-fee
     * lookup (docs/phase-9-inscriptions-mapping.md's confirmed decision: a
     * dedicated endpoint, not embedding every group's fees in the initial
     * options payload). Gated the same as creating a registration
     * (`registrations.create` only — mirrors InscriptionsIndex::
     * updatedGroupId(), which loads a group's fees with no separate
     * groups.view check; the `groups` options list passed to the page is
     * already center-scoped by GetInscriptionFormOptions::groups(), so
     * requiring groups.view here too would be a stricter gate than the
     * Livewire source of truth, not a matching one), not
     * `registrations.manage-fees` (audit doc §12 point 1 — that permission
     * is not part of the live create workflow).
     */
    public function groupFees(Group $group, GetGroupInscriptionFees $getGroupInscriptionFees): JsonResponse
    {
        $this->authorize('create', Inscription::class);

        return response()->json([
            'fees' => $getGroupInscriptionFees($group),
            ...$getGroupInscriptionFees->trainingDates($group),
        ]);
    }

    /**
     * "Livre" stock articles at a group's own center — feeds the create
     * form's book multi-select. Same permission gate as groupFees() (both
     * are pure lookups for the create flow, not a mutation).
     */
    public function groupLivres(Group $group): JsonResponse
    {
        $this->authorize('create', Inscription::class);

        return response()->json([
            'livres' => $this->availableLivresQuery($group->etablissement_id)->get()
                ->map(fn (StockArticle $a): array => ['id' => $a->id, 'nom' => $a->nom, 'quantite' => $a->quantite])
                ->values(),
        ]);
    }

    /**
     * Books already assigned to an existing registration, plus the same
     * center's available "Livre" stock — feeds the edit modal's multi-select
     * (pre-selected + pickable). Same `view` gate as fees()/show().
     */
    public function livres(Inscription $inscription): JsonResponse
    {
        $this->authorize('view', $inscription);

        return response()->json([
            'assignedIds' => $inscription->livres()->pluck('stock_article_id')->values(),
            'livres' => $this->availableLivresQuery($inscription->etablissement_id)->get()
                ->map(fn (StockArticle $a): array => ['id' => $a->id, 'nom' => $a->nom, 'quantite' => $a->quantite])
                ->values(),
        ]);
    }

    /**
     * Syncs an existing registration's assigned books to the submitted set
     * (AssignerLivresInscription — additive/subtractive, never destroy-and-
     * recreate, so a book already given is never re-decremented). Gated by
     * registrations.manage-fees, same permission as the fee-line editor —
     * both are "adjust what this registration owes/receives after the fact"
     * actions.
     */
    public function updateLivres(
        UpdateInscriptionLivresRequest $request,
        Inscription $inscription,
        AssignerLivresInscription $action,
    ): RedirectResponse {
        $this->authorize('view', $inscription);

        $data = $request->validated();
        $livreIds = array_map('intval', $data['livre_ids'] ?? []);

        $action->validateAvailability($livreIds, $inscription->livres()->pluck('stock_article_id')->all());
        $action->handle($inscription, $livreIds, $request->user()?->employee);

        // back() — see hideFee(): redirecting to index closed the edit modal,
        // which is why saving books looked like it "did nothing" (the stock
        // movement did happen, but the modal vanished before the refreshed
        // quantities could be shown).
        return back()->with('success', __('Registration books updated.'));
    }

    /**
     * Active "Livre"-type stock articles for a given center — shared by
     * groupLivres()/livres() so both stay center-scoped the same way.
     */
    private function availableLivresQuery(?int $etablissementId)
    {
        return StockArticle::query()
            ->whereHas('stockType', fn ($q) => $q->where('nom', StockType::SYSTEM_LIVRE))
            ->where('statut', StockArticle::STATUT_ACTIF)
            ->where('etablissement_id', $etablissementId)
            ->orderBy('nom');
    }

    public function store(StoreInscriptionRequest $request, AssignerLivresInscription $assignerLivres): RedirectResponse
    {
        $this->authorize('create', Inscription::class);

        $data = $request->validated();
        $group = Group::findOrFail($data['group_id']);
        $creatingStudent = $data['inscription_mode'] === 'new';
        $livreIds = array_map('intval', $data['livre_ids'] ?? []);

        $assignerLivres->validateAvailability($livreIds, []);

        DB::transaction(function () use ($data, $group, $creatingStudent, $request, $livreIds, $assignerLivres): void {
            if ($creatingStudent) {
                $phonePays = $data['phone_pays'] ?? Countries::DEFAULT;
                $niveau = $data['new_niveau'] ?? null;

                $student = Student::create([
                    'reference' => ReferenceGenerator::make('ETU', 'students'),
                    'nom' => $data['new_nom'],
                    'prenom' => $data['new_prenom'],
                    'sexe' => $data['new_sexe'] ?? null,
                    'date_naissance' => $data['new_date_naissance'] ?? null,
                    'cin' => $data['new_cin'] ?? null,
                    'niveau' => $niveau,
                    'domaine' => Student::niveauDemandeDomaine($niveau) ? ($data['new_domaine'] ?? null) : null,
                    'examen_type' => Student::niveauDemandeExamen($niveau) ? ($data['new_examen_type'] ?? null) : null,
                    'email' => $data['new_email'] ?? null,
                    'telephone' => Countries::join($phonePays, $data['new_telephone'] ?? null),
                    'whatsapp' => Countries::join($phonePays, $data['new_whatsapp'] ?? null),
                    'adresse' => $data['new_adresse'] ?? null,
                    'parent_relation' => $data['new_parent_relation'] ?? null,
                    'parent_nom' => $data['new_parent_nom'] ?? null,
                    'parent_sexe' => $data['new_parent_sexe'] ?? null,
                    'parent_cin' => $data['new_parent_cin'] ?? null,
                    'parent_telephone' => Countries::join($phonePays, $data['new_parent_telephone'] ?? null),
                    'parent_whatsapp' => Countries::join($phonePays, $data['new_parent_whatsapp'] ?? null),
                    'etablissement_id' => $group->etablissement_id,
                ]);
                $studentId = $student->id;
            } else {
                $studentId = $data['student_id'];
            }

            $lines = collect($data['fee_lines'] ?? [])->map(function (array $line): array {
                $initial = (float) ($line['montant_initial'] ?? 0);
                $remisePct = isset($line['remise_pct']) && $line['remise_pct'] !== '' ? (float) $line['remise_pct'] : null;
                $remiseMontant = isset($line['remise_montant']) && $line['remise_montant'] !== '' ? (float) $line['remise_montant'] : null;
                $final = InscriptionFee::computeMontant($initial, $remisePct, $remiseMontant);

                return [
                    'frais_id' => $line['frais_id'] ?? null,
                    'nom' => $line['nom'],
                    'montant_initial' => $initial,
                    'remise_pct' => $remisePct,
                    'remise_montant' => $remiseMontant,
                    'montant' => $final,
                    'note' => $line['note'] ?? null,
                    'date_echeance' => $line['date_echeance'] ?? now()->toDateString(),
                    'statut' => InscriptionFee::STATUT_NON_PAYE,
                ];
            });

            $total = $lines->sum('montant');

            $inscription = Inscription::create([
                'reference' => ReferenceGenerator::make('INS', 'inscriptions'),
                'student_id' => $studentId,
                'group_id' => $group->id,
                // Center + academic year are inherited from the SELECTED
                // GROUP (not the acting user's context) — same as
                // InscriptionsIndex::save().
                'etablissement_id' => $group->etablissement_id,
                'annee_scolaire_id' => $group->annee_scolaire_id ?? app(CurrentContext::class)->anneeScolaireId(),
                // Forced server-side: a new registration always starts
                // Active, whatever the client sends (there is no `statut`
                // field in StoreInscriptionRequest at all).
                'statut' => Inscription::STATUT_ACTIVE,
                'date_inscription' => $data['date_inscription'],
                // Dates are the group's training period (read-only in the
                // UI) — re-derived here so a tampered field can't override
                // them (unlike update(), which trusts the submitted dates).
                'date_debut' => $group->date_debut_formation?->toDateString(),
                'date_fin' => $group->date_fin_formation?->toDateString(),
                'montant_total' => $total > 0 ? $total : null,
                'note' => $data['note'] ?? null,
                'created_by' => $request->user()->employee?->id,
            ]);

            foreach ($lines as $line) {
                $inscription->fees()->create($line);
            }

            if ($livreIds !== []) {
                $assignerLivres->handle($inscription, $livreIds, $request->user()->employee);
            }
        });

        return redirect()->route('backoffice.inscriptions.index')
            ->with('success', __('Registration created.'));
    }

    /**
     * "Changement de groupe" — archives this inscription into
     * inscriptions_historique and creates a new Active one in the target
     * group (ChangerGroupeInscription), instead of editing group_id in
     * place like update() does. Kept as its own gated action
     * (registrations.change-group) rather than folded into update() since
     * it mutates money allocation (unpaid fees dropped from the old
     * inscription become unallocated avances) and always creates a second
     * row.
     */
    public function changeGroup(
        ChangeGroupInscriptionRequest $request,
        Inscription $inscription,
        ChangerGroupeInscription $action,
    ): RedirectResponse {
        $this->authorize('changeGroup', $inscription);

        $data = $request->validated();
        $newGroup = Group::findOrFail($data['new_group_id']);

        $action->handle(
            $inscription,
            $newGroup,
            $data['date_fin'],
            $data['date_debut'],
            $data['unpaid_fees_scope'] ?? null,
            $data['note'] ?? null,
            $request->user()->employee,
            array_map('intval', $data['transfer_fee_ids'] ?? []),
        );

        return redirect()->route('backoffice.inscriptions.index')
            ->with('success', __('Registration moved to the new group.'));
    }

    public function update(UpdateInscriptionRequest $request, Inscription $inscription): RedirectResponse
    {
        $this->authorize('update', $inscription);

        $data = $request->validated();

        // Only 5 columns are ever updated — fees/totals/center/year/group are
        // never touched on edit, matching InscriptionsIndex::save()'s
        // $editing branch exactly. date_debut/date_fin come straight from
        // the request (NOT re-derived from the group, unlike create — a
        // confirmed asymmetry, see docs/phase-9-inscriptions-audit.md §12).
        // group_id is deliberately never accepted here — moving a student to
        // another group only ever happens through changeGroup()
        // (ChangerGroupeInscription: fee migration + archival snapshot +
        // registrations.change-group gate), never a silent field swap.
        $inscription->update([
            'student_id' => $data['student_id'],
            'statut' => $data['statut'],
            'date_inscription' => $data['date_inscription'],
            'date_debut' => $data['date_debut'] ?? null,
            'date_fin' => $data['date_fin'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        return redirect()->route('backoffice.inscriptions.index')
            ->with('success', __('Registration updated.'));
    }

    /**
     * Quick status action from the list's row menu — "Annuler" (Active ->
     * Annulée) and "Réactiver" (Changement/Annulée -> Active, the reverse
     * move, so a mistaken cancel doesn't require opening the full edit
     * modal to undo). Reaching "Changement" is deliberately NOT offered
     * here — that status is only ever set by the dedicated changeGroup()
     * flow, which also migrates fees and creates a replacement Active
     * inscription; a bare Active -> Changement with no successor would
     * leave the student's enrollment history looking like a change that
     * never actually happened. Every other transition (e.g. an already-
     * Annulée row being annulled again) is refused.
     */
    public function updateStatut(Request $request, Inscription $inscription): RedirectResponse
    {
        $this->authorize('update', $inscription);

        $statut = $request->string('statut')->toString();

        if (! in_array($statut, [Inscription::STATUT_ACTIVE, Inscription::STATUT_ANNULEE], true)) {
            abort(422, __('Invalid status.'));
        }

        $isReactivation = $statut === Inscription::STATUT_ACTIVE;
        $currentIsActive = $inscription->statut === Inscription::STATUT_ACTIVE;

        if ($isReactivation === $currentIsActive) {
            throw ValidationException::withMessages([
                'statut' => __('This status change is not allowed from the current status.'),
            ]);
        }

        $inscription->update(['statut' => $statut]);

        return redirect()->route('backoffice.inscriptions.index')
            ->with('success', __('Registration status updated.'));
    }

    /**
     * Fees with payments are blocked by the DB restrict FK on
     * encaissements.inscription_fee_id — the cascade from
     * inscription_fees.inscription_id fails, surfacing as a
     * QueryException. This is the SAME mechanism as
     * InscriptionsIndex::delete() (a try/catch around the raw delete, not
     * a pre-count guard) — preserved exactly, not "upgraded" (see audit
     * doc §4.8 for why a pre-count guard would be a subtle behavior
     * change, not just a refactor). The delete is wrapped in its own
     * DB::transaction() so PostgreSQL uses a savepoint: without it, the
     * constraint violation aborts the whole request-scoped transaction
     * (RefreshDatabase's outer transaction in tests, or the connection's
     * implicit transaction in production), leaving the catch block
     * running against a connection Postgres refuses further queries on.
     * This same fix is worth carrying back to the Livewire component if
     * it is ever revisited — it has the identical latent gap.
     */
    public function destroy(Inscription $inscription): RedirectResponse
    {
        $this->authorize('delete', $inscription);

        try {
            DB::transaction(fn () => $inscription->delete());
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'delete' => __('This registration has payments and cannot be deleted.'),
            ]);
        }

        return redirect()->route('backoffice.inscriptions.index')
            ->with('success', __('Registration deleted.'));
    }
}
