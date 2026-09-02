<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Groups\Actions\ChangerEnseignantGroupe;
use App\Domain\Groups\Actions\ReaffecterGroupeVersAnnee;
use App\Domain\Groups\Actions\RetirerFraisGroupe;
use App\Domain\Groups\Actions\SupprimerGroupe;
use App\Domain\Groups\Queries\GetGroupDetails;
use App\Domain\Groups\Queries\GetGroupFormOptions;
use App\Domain\Groups\Queries\GetGroupPaymentMatrix;
use App\Domain\Groups\Queries\GetGroupsList;
use App\Domain\Groups\Queries\GetGroupStudentsBySegment;
use App\Domain\Settings\Support\FraisEcheanceResolver;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Backoffice\Concerns\AssertsContextScope;
use App\Http\Requests\Backoffice\Groups\ChangerEnseignantRequest;
use App\Http\Requests\Backoffice\Groups\RestoreGroupFraisRequest;
use App\Http\Requests\Backoffice\Groups\StoreGroupRequest;
use App\Http\Requests\Backoffice\Groups\UpdateGroupEnseignantRequest;
use App\Http\Requests\Backoffice\Groups\UpdateGroupRequest;
use App\Models\AnneeScolaire;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Encaissement;
use App\Models\GroupEnseignant;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Seance;
use App\Services\Context\CurrentContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Groups list + modal add/edit (Phase 8, docs/phase-8-students-groups-
 * inventory.md) + the read-only detail page and "Fin de formation" archive
 * action (Phase 5, unchanged). The list + create/update mutations mirror
 * App\Livewire\Backoffice\Groups\GroupsIndex one-for-one — migrated exactly
 * as the current Livewire form exists, no room/capacity/schedule fields
 * (confirmed absent from the live UI). Groups sont normalement JAMAIS
 * supprimés (schema §6) : destroy() est l'exception super-admin ajoutée le
 * 31/08/2026 pour les groupes créés par erreur — voir SupprimerGroupe.
 */
final class GroupController extends Controller
{
    use AssertsContextScope;

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

    /**
     * "Détails paiement" — the group's payment matrix (students × fees),
     * fetched by the list page's kebab menu into a modal.
     */
    public function paymentMatrix(Request $request, Group $group, GetGroupPaymentMatrix $getGroupPaymentMatrix): JsonResponse
    {
        $this->authorize('view', $group);

        $sort = (string) $request->string('sort');

        if (! in_array($sort, GetGroupPaymentMatrix::SORTS, true)) {
            $sort = GetGroupPaymentMatrix::SORT_NOM;
        }

        return response()->json([
            'group' => ['nom' => $group->nom, 'niveau' => $group->niveau],
            'matrix' => $getGroupPaymentMatrix($group, $sort),
        ]);
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $this->authorize('create', Group::class);
        // A new group inherits the ACTIVE year, so a closed one must refuse it.
        $this->assertContextAnneeOuverte('nom');

        $data = $request->validated();
        // A new group starts with the WHOLE active catalog, minus the fees
        // the user removed with the trash icon before saving (`fraisRetires`).
        // Nothing submitted ⇒ null ⇒ every fee, as it always has been.
        $retires = array_map('intval', (array) ($data['fraisRetires'] ?? []));
        $existant = $retires === []
            ? null
            : Frais::query()
                ->where('statut', Frais::STATUT_ACTIF)
                ->whereNotIn('id', $retires)
                ->pluck('id')
                ->all();

        // A new group inherits the active center (see the transaction
        // below), so that is the center whose fee prices apply.
        $fraisLignes = $this->normalizedFraisLignes(
            $request,
            $data['date_debut_formation'] ?? null,
            app(CurrentContext::class)->etablissementId(),
            $existant,
            $this->debutAnneeScolaireActive(),
        );

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
        $this->assertGroupInContext($request, $group, 'nom');

        $data = $request->validated();
        $fraisLignes = $this->normalizedFraisLignes(
            $request,
            $data['date_debut_formation'] ?? $group->date_debut_formation?->toDateString(),
            $group->etablissement_id,
            // Only the fees this group still carries — see the helper's
            // docblock: a removed fee must not come back on the next save.
            $group->frais()->pluck('frais.id')->all(),
            $group->anneeScolaire?->date_debut?->toDateString(),
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

            // And the other way round: a group that reached a terminal
            // status is history (groups_historique snapshot written); the
            // edit modal never brings it back to life (audit CRUD-F14).
            if (in_array($group->statut, Group::STATUTS_HISTORIQUE, true)) {
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
     * Gated by its OWN ability `groups.change-teacher`, which every role
     * holds (31/08/2026) — fixing a wrong or departed enseignant must not
     * require the full `groups.update` right.
     */
    public function changerEnseignant(
        ChangerEnseignantRequest $request,
        Group $group,
        ChangerEnseignantGroupe $changerEnseignant,
    ): RedirectResponse {
        $this->authorize('changeTeacher', $group);
        $this->assertGroupInContext($request, $group, 'enseignant_id');

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
        // Same ability as the changeover itself: correcting the dates of an
        // assignment period is part of managing a group's teacher, not of
        // editing the group.
        $this->authorize('changeTeacher', $group);
        $this->assertGroupInContext($request, $group, 'date_debut');

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
    /**
     * Moves the group — with every inscription, séance and (through the
     * fees) payment hanging off it — to another année scolaire. Nothing is
     * copied or dropped: the same rows change year, so every count is
     * identical before and after (ReaffecterGroupeVersAnnee, journaled row
     * by row). Super-admin only via `groups.move-year`.
     */
    public function moveYear(Request $request, Group $group, ReaffecterGroupeVersAnnee $reaffecter): RedirectResponse
    {
        $this->authorize('moveYear', $group);
        $this->assertGroupInContext($request, $group, 'annee_scolaire_id');

        $data = $request->validate([
            'annee_scolaire_id' => ['required', 'integer', 'exists:annees_scolaires,id'],
            // Optional: the statut the group should carry in its new year.
            // Applied through the sanctioned lifecycle transitions ONLY (see
            // transitionnerStatut) — never a raw statut update (CLAUDE.md §11).
            'statut' => ['nullable', 'string', \Illuminate\Validation\Rule::in(Group::STATUTS)],
        ]);

        $annee = AnneeScolaire::query()->findOrFail((int) $data['annee_scolaire_id']);
        $statutCible = $data['statut'] ?? null;

        if ((int) $group->annee_scolaire_id === $annee->id && ($statutCible === null || $statutCible === $group->statut)) {
            throw ValidationException::withMessages([
                'annee_scolaire_id' => __('The group is already in this academic year.'),
            ]);
        }

        DB::transaction(function () use ($reaffecter, $group, $annee, $statutCible, $request): void {
            if ((int) $group->annee_scolaire_id !== $annee->id) {
                // force: an operator explicitly moving THIS group on the
                // Groupes screen is a decision, unlike the import mapping
                // step where the année is just whichever file was uploaded
                // last (see ReaffecterGroupeVersAnnee).
                $reaffecter->handle($group, $annee->id, force: true);
            }

            if ($statutCible !== null && $statutCible !== $group->statut) {
                $this->transitionnerStatut($group, $statutCible, $request->user()?->employee);
            }
        });

        return back()->with('success', __('Group moved to :annee with its registrations, sessions and payments.', [
            'annee' => $annee->nom,
        ]));
    }

    /**
     * Reaches the requested statut from the current one using ONLY the
     * model's own transitions (archiverCommeTermine / annuler write their
     * groups_historique snapshot; activer / reactiver /
     * retournerEnInscription are the sanctioned reversals). "Fin de
     * formation" is final: nothing leaves it.
     */
    private function transitionnerStatut(Group $group, string $cible, ?\App\Models\Employee $par): void
    {
        // « Fin de formation » reste fermé ICI, pour tout le monde.
        //
        // La réouverture existe depuis le 01/09/2026, mais elle a son propre
        // chemin — GroupController@rouvrir, gardé par `groups.reopen`
        // (super-admin). Elle ne se glisse volontairement PAS dans les
        // changements de statut incidents comme celui-ci : « Déplacer vers
        // une autre année » déplace des inscriptions, des séances et des
        // paiements, et rouvrir un groupe au passage serait une décision
        // majeure prise dans un formulaire qui parle d'autre chose.
        // Rouvrir d'abord, déplacer ensuite — deux actes explicites.
        if ($group->statut === Group::STATUT_FIN_FORMATION) {
            throw ValidationException::withMessages([
                'statut' => __('This group is "Fin de formation" — that status is final and cannot be changed.'),
            ]);
        }

        match ($cible) {
            Group::STATUT_FIN_FORMATION => $group->archiverCommeTermine($par),
            Group::STATUT_ANNULEE => $group->annuler($par),
            Group::STATUT_EN_INSCRIPTION => $group->statut === Group::STATUT_ANNULEE
                ? $group->reactiver()
                : $group->retournerEnInscription(),
            Group::STATUT_EN_FORMATION => (function () use ($group): void {
                if ($group->statut === Group::STATUT_ANNULEE) {
                    $group->reactiver();
                }
                $group->activer();
            })(),
            default => throw ValidationException::withMessages(['statut' => __('Unknown status.')]),
        };
    }

    public function archive(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('archive', $group);
        $this->assertGroupInContext($request, $group, 'statut');

        if ($group->statut === Group::STATUT_FIN_FORMATION) {
            return back();
        }

        $group->archiverCommeTermine($request->user()?->employee);

        return redirect()->route('backoffice.groups.show', $group)
            ->with('success', __('Group archived (Fin de formation).'));
    }

    /**
     * « Rouvrir le groupe » — la seule sortie d'un statut terminal
     * (« Fin de formation » ou « Annulée »), ajoutée le 01/09/2026.
     *
     * Réservée au super-admin (`groups.reopen` est dans
     * PermissionRegistry::superAdminOnly()), parce qu'elle rouvre un dossier
     * que l'établissement considérait comme clos : le groupe ressort de
     * l'onglet Historique, revient dans les listes actives et redevient
     * inscriptible.
     *
     * ⚠ Ne touche QUE `statut` (Group::rouvrir) : aucun encaissement,
     * aucune inscription, aucune séance, aucun frais n'est modifié, et le
     * snapshot groups_historique est conservé. Une clôture faite par erreur
     * se corrige donc sans rien détruire — et surtout sans UPDATE SQL
     * direct en production, qui échapperait au journal d'audit.
     */
    public function rouvrir(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('reopen', $group);
        $this->assertGroupInContext($request, $group, 'statut');

        if (! in_array($group->statut, Group::STATUTS_HISTORIQUE, true)) {
            return back();
        }

        $cible = (string) $request->string('statut');

        if (! in_array($cible, [Group::STATUT_EN_INSCRIPTION, Group::STATUT_EN_FORMATION], true)) {
            $cible = Group::STATUT_EN_FORMATION;
        }

        $group->rouvrir($cible);

        return redirect()->route('backoffice.groups.show', $group)
            ->with('success', __('Group reopened.'));
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
        $this->assertGroupInContext($request, $group, 'statut');

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
        $this->assertGroupInContext($request, $group, 'statut');

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
        $this->assertGroupInContext($request, $group, 'statut');

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
        $this->assertGroupInContext($request, $group, 'statut');

        if ($group->statut !== Group::STATUT_EN_FORMATION) {
            return back();
        }

        $group->retournerEnInscription();

        return redirect()->route('backoffice.groups.index')
            ->with('success', __('Group returned to En inscription.'));
    }

    /**
     * Removes ONE catalog fee from the group and cascades that removal to
     * every inscription of the group — the modal's trash icon on a « Frais
     * du groupe » row, mirroring the Inscriptions edit modal's own hide
     * action (InscriptionController::hideFee).
     *
     * Nothing is deleted: the inscription fee lines are HIDDEN and the money
     * already collected on them is released back into re-applicable avances
     * (RetirerFraisGroupe). Gated by groups.update, like every other edit of
     * a group.
     */
    public function removeFee(Request $request, Group $group, Frais $frai, RetirerFraisGroupe $action): JsonResponse
    {
        $this->authorize('update', $group);
        $this->assertGroupInContext($request, $group, 'frais');

        $result = $action->handle($group, $frai->id);

        // JSON, not back(): this fires from INSIDE the open edit modal, which
        // updates its own fee table from local state (same pattern as
        // InscriptionController::hideFee). An Inertia redirect — even a
        // `back()` with preserveState — re-runs index() and rebuilds the whole
        // page payload (paginated list + frais/enseignants catalogs) just to
        // drop one row, which is what made the trash icon feel slow. Nothing
        // on the page behind the modal depends on the result.
        return response()->json([
            'ok' => true,
            'feesMasques' => $result['feesMasques'],
            'encaissementsConvertis' => $result['encaissementsConvertis'],
            'message' => $result['encaissementsConvertis'] > 0
                ? __('Fee removed from the group. :count registration fee line(s) hidden and :montant DH returned as advances, ready to be re-applied.', [
                    'count' => $result['feesMasques'],
                    'montant' => number_format($result['encaissementsConvertis'], 2, ',', ' '),
                ])
                : __('Fee removed from the group. :count registration fee line(s) hidden.', [
                    'count' => $result['feesMasques'],
                ]),
        ]);
    }

    /**
     * Reverse of removeFee() — re-attaches the fee to the group (at the
     * center's price / the month-derived échéance unless the modal sent its
     * own) and un-hides it on every inscription. Freed avances stay avances:
     * re-allocating money is always an explicit AppliquerAvance decision,
     * never a side effect of restoring a line.
     */
    public function restoreFee(RestoreGroupFraisRequest $request, Group $group, Frais $frai, RetirerFraisGroupe $action): JsonResponse
    {
        $this->authorize('update', $group);
        $this->assertGroupInContext($request, $group, 'frais');

        $montant = $request->validated('montant');
        $echeance = $request->validated('date_echeance');

        $montantFinal = $montant !== null && $montant !== ''
            ? (float) $montant
            : $frai->montantPourCentre($group->etablissement_id);
        $echeanceFinale = $echeance !== null && $echeance !== ''
            ? $echeance
            : FraisEcheanceResolver::defaultFor(
                $frai->nom,
                $group->date_debut_formation?->toDateString(),
                $group->anneeScolaire?->date_debut?->toDateString(),
            );
        $classification = ($c = $request->input('classification')) !== null && $c !== '' ? (string) $c : null;

        $count = $action->restore($group, $frai->id, $montantFinal, $echeanceFinale, $classification);

        // JSON — see removeFee(). The response carries the restored row's full
        // shape so the modal can splice it straight back into its table
        // instead of re-fetching the page.
        return response()->json([
            'ok' => true,
            'count' => $count,
            'ligne' => [
                'id' => $frai->id,
                'nom' => $frai->nom,
                'montant' => number_format($montantFinal, 2, '.', ''),
                'date_echeance' => $echeanceFinale,
                'classification' => $classification ?? '',
            ],
            'message' => __(':count registration fee line(s) restored.', ['count' => $count]),
        ]);
    }

    /**
     * Builds the group_frais sync payload from the request's `fraisLignes`
     * array — validated server-side against the real ACTIVE catalog rather
     * than trusted from the client (a stale/forged frais_id key is silently
     * ignored).
     *
     * ⚠ On UPDATE this only covers fees the group STILL carries: a fee the
     * user removed through removeFee() is gone from `group_frais`, and
     * re-adding it here would silently resurrect it on the next save —
     * re-attaching a line that removeFee() deliberately hid on every
     * inscription, while the money it released stayed in avances. Passing
     * `$existant` (the group's current frais ids) restricts the sync to
     * those; a CREATE passes null and gets the whole catalog, which is the
     * behavior a brand-new group has always had.
     *
     * A blank amount/échéance falls back to what THIS GROUP'S CENTER
     * charges for the fee (frais_etablissement.montant, else
     * frais.montant_defaut) and to the month-derived due date — see
     * FraisEcheanceResolver — so a group only has to type the values that
     * actually differ from its branch's standard.
     *
     * @return array<int, array{montant: float, date_echeance: ?string, classification: ?string}>
     */
    /**
     * The active académic year's start date — the fallback anchor a monthly
     * fee's due date takes its YEAR from when the group carries no
     * date_debut_formation of its own (see FraisEcheanceResolver).
     */
    private function debutAnneeScolaireActive(): ?string
    {
        $anneeId = app(CurrentContext::class)->anneeScolaireId();

        if ($anneeId === null) {
            return null;
        }

        return AnneeScolaire::find($anneeId)?->date_debut?->toDateString();
    }

    private function normalizedFraisLignes(
        Request $request,
        ?string $dateDebutFormation = null,
        ?int $etablissementId = null,
        ?array $existant = null,
        ?string $debutAnneeScolaire = null,
    ): array {
        // Whole catalog rows, not just ids: each one carries the amount a
        // fee is worth in this center unless this group says otherwise.
        // On EDIT the group's current pivot is the authority — a fee since
        // set Inactif in the catalog stays assigned (its lines stay owed);
        // only RetirerFraisGroupe removes a fee from a group (audit CRUD-F6).
        $catalogue = Frais::query()
            ->when($existant === null, fn ($q) => $q->where('statut', Frais::STATUT_ACTIF))
            ->when($existant !== null, fn ($q) => $q->whereIn('id', $existant))
            ->with('etablissements:id')
            ->get(['id', 'nom', 'montant_defaut']);

        $submitted = (array) $request->input('fraisLignes', []);

        $sync = [];
        foreach ($catalogue as $frais) {
            $ligne = $submitted[$frais->id] ?? [];

            // Blank means "use the catalog default" — not "free". A zero the
            // user typed on purpose is still respected, since '0' !== ''.
            $montant = ($ligne['montant'] ?? '') !== ''
                ? (float) $ligne['montant']
                : $frais->montantPourCentre($etablissementId);

            $echeance = ($ligne['date_echeance'] ?? '') !== ''
                ? $ligne['date_echeance']
                : FraisEcheanceResolver::defaultFor($frais->nom, $dateDebutFormation, $debutAnneeScolaire);

            $sync[$frais->id] = [
                'montant' => $montant,
                'date_echeance' => $echeance,
                'classification' => ($ligne['classification'] ?? '') !== '' ? $ligne['classification'] : null,
            ];
        }

        return $sync;
    }

    /**
     * Chiffres réels affichés dans l'avertissement de suppression (ce que la
     * destruction va emporter). Lecture seule, même garde que destroy() : un
     * non-super-admin ne doit même pas pouvoir sonder le groupe.
     */
    public function deletionImpact(Request $request, Group $group): JsonResponse
    {
        $this->authorize('delete', $group);

        $inscriptionIds = Inscription::query()->where('group_id', $group->id)->pluck('id');
        $feeIds = InscriptionFee::query()->whereIn('inscription_id', $inscriptionIds)->pluck('id');
        $encaissements = Encaissement::query()->whereIn('inscription_fee_id', $feeIds);

        return response()->json([
            'nom' => $group->nom,
            'inscriptions' => $inscriptionIds->count(),
            'etudiants' => Inscription::query()->where('group_id', $group->id)
                ->distinct()->count('student_id'),
            'frais' => $feeIds->count(),
            // > 0 ⇒ suppression refusée (invariant monétaire, SupprimerGroupe).
            'encaissements' => (clone $encaissements)->count(),
            'montantEncaisse' => (float) (clone $encaissements)->sum('montant'),
            'seances' => Seance::query()->where('group_id', $group->id)->count(),
        ]);
    }

    /**
     * ⚠ Suppression DÉFINITIVE du groupe et de ses inscriptions — super-admin
     * uniquement (`groups.delete` ∈ superAdminOnly()). REFUSÉE si le groupe
     * porte le moindre encaissement ou la moindre séance : ce chemin ne sert
     * qu'aux groupes créés par erreur (SupprimerGroupe).
     */
    public function destroy(Request $request, Group $group, SupprimerGroupe $supprimer): RedirectResponse
    {
        $this->authorize('delete', $group);
        $this->assertGroupInContext($request, $group, 'group');

        // Double confirmation serveur : le nom exact du groupe doit être
        // retapé, pour qu'un POST forgé ou un double-clic ne détruise rien.
        $data = $request->validate([
            'confirmation' => ['required', 'string'],
        ]);

        if (trim((string) $data['confirmation']) !== trim((string) $group->nom)) {
            throw ValidationException::withMessages([
                'confirmation' => __('Type the exact group name to confirm the deletion.'),
            ]);
        }

        $nom = (string) $group->nom;
        $resultat = $supprimer->handle($group);

        $message = __('Group :name and its :count registrations were permanently deleted.', [
            'name' => $nom,
            'count' => (string) $resultat['inscriptions'],
        ]);

        return redirect()->route('backoffice.groups.index')->with('success', $message);
    }
}
