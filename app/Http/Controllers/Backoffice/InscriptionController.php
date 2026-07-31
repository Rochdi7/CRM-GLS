<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Registrations\Queries\GetGroupInscriptionFees;
use App\Domain\Registrations\Queries\GetInscriptionDetails;
use App\Domain\Registrations\Queries\GetInscriptionFormOptions;
use App\Domain\Registrations\Queries\GetInscriptionsList;
use App\Domain\Shared\Support\ReferenceGenerator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Inscriptions\StoreInscriptionRequest;
use App\Http\Requests\Backoffice\Inscriptions\UpdateInscriptionRequest;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
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
 * App\Livewire\Backoffice\Inscriptions\InscriptionsIndex one-for-one,
 * including its create-vs-edit asymmetry (fee lines and group-derived
 * dates only apply on create; edit only ever touches 6 columns). The
 * Livewire component and its view are left completely untouched as
 * unreferenced fallback code.
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
        $perPage = (int) $request->integer('perPage', GetInscriptionsList::DEFAULT_PER_PAGE);

        return Inertia::render('Backoffice/Inscriptions/Index', [
            'inscriptions' => $getInscriptionsList($request->user(), $search, $statutFilter, $perPage),
            'filters' => [
                'search' => $search,
                'statutFilter' => $statutFilter,
                'perPage' => in_array($perPage, GetInscriptionsList::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetInscriptionsList::DEFAULT_PER_PAGE,
            ],
            'perPageOptions' => GetInscriptionsList::PER_PAGE_OPTIONS,
            'statuts' => Inscription::STATUTS,
            'niveaux' => Student::NIVEAUX,
            'domaines' => Student::DOMAINES,
            'examenTypes' => Student::EXAMEN_TYPES,
            'sexes' => Student::SEXES,
            'parentRelations' => Student::PARENT_RELATIONS,
            'niveauxAvecDomaine' => Student::NIVEAUX_AVEC_DOMAINE,
            'niveauStudium' => Student::NIVEAU_STUDIUM,
            'countries' => Countries::all(),
            'defaultCountry' => Countries::DEFAULT,
            'students' => $getInscriptionFormOptions->students($request->user()),
            'groups' => $getInscriptionFormOptions->groups($request->user()),
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

    public function store(StoreInscriptionRequest $request): RedirectResponse
    {
        $this->authorize('create', Inscription::class);

        $data = $request->validated();
        $group = Group::findOrFail($data['group_id']);
        $creatingStudent = $data['inscription_mode'] === 'new';

        DB::transaction(function () use ($data, $group, $creatingStudent, $request): void {
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
        });

        return redirect()->route('backoffice.inscriptions.index')
            ->with('success', __('Registration created.'));
    }

    public function update(UpdateInscriptionRequest $request, Inscription $inscription): RedirectResponse
    {
        $this->authorize('update', $inscription);

        $data = $request->validated();

        // Only 6 columns are ever updated — fees/totals/center/year are
        // never touched on edit, matching InscriptionsIndex::save()'s
        // $editing branch exactly. date_debut/date_fin come straight from
        // the request (NOT re-derived from the group, unlike create — a
        // confirmed asymmetry, see docs/phase-9-inscriptions-audit.md §12).
        $inscription->update([
            'student_id' => $data['student_id'],
            'group_id' => $data['group_id'],
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
