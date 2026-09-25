<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Backoffice\Concerns\RedirectsPreservingFilters;
use App\Domain\Attendance\Queries\GetSeanceFormOptions;
use App\Domain\Groups\Support\PorteeEnseignant;
use App\Domain\Settings\Queries\GetAccessibleCenterOptions;
use App\Domain\Shared\Support\ReferenceGenerator;
use App\Domain\Students\Queries\GetStudentDetails;
use App\Domain\Students\Queries\GetStudentsList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Students\StoreStudentRequest;
use App\Http\Requests\Backoffice\Students\UpdateStudentRequest;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\Student;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use App\Support\Phone\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Real HTTP endpoints mirroring App\Livewire\Backoffice\Students\StudentsIndex
 * one-for-one (Phase 8, docs/rapports/migration-inertia/phase-8-students-groups-inventory.md), following
 * the Phase 7 Employees pattern. The Livewire component and its view are left
 * completely untouched as unreferenced fallback code.
 */
final class StudentController extends Controller
{
    use RedirectsPreservingFilters;

    public function index(
        Request $request,
        GetStudentsList $getStudentsList,
        GetAccessibleCenterOptions $accessibleCenters,
        GetSeanceFormOptions $seanceOptions,
    ): Response {
        $this->authorize('viewAny', Student::class);

        $context = app(CurrentContext::class);

        $search = (string) $request->string('search');
        $niveauFilter = (string) $request->string('niveauFilter');
        $sexeFilter = (string) $request->string('sexeFilter');
        $etablissementFilter = (string) $request->string('etablissementFilter');
        $ageSort = (string) $request->string('ageSort');
        $referenceFilter = (string) $request->string('referenceFilter');
        $nomFilter = (string) $request->string('nomFilter');
        $prenomFilter = (string) $request->string('prenomFilter');
        $telephoneFilter = (string) $request->string('telephoneFilter');
        $inscriptionFilter = (string) $request->string('inscriptionFilter');
        $perPage = (int) $request->integer('perPage', GetStudentsList::DEFAULT_PER_PAGE);

        // Portée enseignant : sa propre vue en cartes (consultation seule) —
        // les étudiants de SES groupes, filtrables par groupe.
        if (PorteeEnseignant::estRestreint($request->user())) {
            $groupeFilter = (string) $request->string('groupeFilter');

            return Inertia::render('Backoffice/Students/MesEtudiants', [
                'students' => $getStudentsList(
                    $request->user(), $search, '', '', '', '', 25,
                    '', '', '', '', '', $groupeFilter,
                ),
                'filters' => ['search' => $search, 'groupeFilter' => $groupeFilter],
                'groupOptions' => $seanceOptions->allGroups($request->user()),
            ]);
        }

        return Inertia::render('Backoffice/Students/Index', [
            'students' => $getStudentsList(
                $request->user(),
                $search,
                $niveauFilter,
                $sexeFilter,
                $etablissementFilter,
                $ageSort,
                $perPage,
                $referenceFilter,
                $nomFilter,
                $prenomFilter,
                $telephoneFilter,
                $inscriptionFilter,
            ),
            'filters' => [
                'search' => $search,
                'niveauFilter' => $niveauFilter,
                'sexeFilter' => $sexeFilter,
                'etablissementFilter' => $etablissementFilter,
                'ageSort' => $ageSort,
                'referenceFilter' => $referenceFilter,
                'nomFilter' => $nomFilter,
                'prenomFilter' => $prenomFilter,
                'telephoneFilter' => $telephoneFilter,
                'inscriptionFilter' => $inscriptionFilter,
                'perPage' => in_array($perPage, GetStudentsList::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetStudentsList::DEFAULT_PER_PAGE,
            ],
            'perPageOptions' => GetStudentsList::PER_PAGE_OPTIONS,
            'niveauxInteret' => Student::NIVEAUX_TRACKS,
            'domaines' => Student::DOMAINES,
            'examenTypes' => Student::EXAMEN_TYPES,
            'sexes' => Student::SEXES,
            'parentRelations' => Student::PARENT_RELATIONS,
            'niveauxAvecDomaine' => Student::NIVEAUX_AVEC_DOMAINE,
            'niveauStudium' => Student::NIVEAU_STUDIUM,
            'defaultCountry' => Countries::DEFAULT,
            // Centre reach governs this dropdown — never the full table
            // (audit 07/09/2026, C-2). An unfiltered `Etablissement::query()`
            // listed all 7 branches to the 25 staff accounts confined to one,
            // both as a filter and as a create/edit target. `allowedIds()` is
            // the same funnel Settings/Salles/Frais already use, so the list
            // stays in sync with « Centres affectés » (§16: the pivot is the
            // one authority on reach — never a role, never a permission).
            'etablissements' => Etablissement::query()
                ->whereIn('id', $accessibleCenters->allowedIds($request->user()))
                ->orderBy('nom_centre')
                ->get(['id', 'nom_centre']),
            'centerLocked' => ! $context->isAllCenters(),
            'contextCenterId' => $context->etablissementId(),
            // Cibles possibles d'un transfert d'étudiant (25/09/2026) : TOUT
            // le réseau, volontairement hors de la portée « Centres affectés »
            // — le guichet de Rabat envoie vers Casablanca qu'il n'ouvre pas.
            // Servi en closure : seul le modal de transfert en a besoin.
            'transferCentres' => fn () => Etablissement::query()
                ->orderBy('nom_centre')
                ->get(['id', 'nom_centre'])
                ->map(fn (Etablissement $e): array => ['id' => $e->id, 'nomCentre' => $e->nom_centre])
                ->all(),
            // Confort d'UI seulement (§5) : la page dessinait Ajouter /
            // Modifier / Supprimer pour tout le monde, le serveur ne refusant
            // qu'à la soumission. `view` = la fiche détaillée (paiements) —
            // un enseignant (portée `groups.view-own`) ne l'ouvre pas.
            'permissions' => [
                'create' => $request->user()->can('students.create'),
                'update' => $request->user()->can('students.update'),
                'delete' => $request->user()->can('students.delete'),
                'view' => $request->user()->can('students.view'),
                'transfer' => $request->user()->can('student-transfers.create'),
            ],
        ]);
    }

    public function show(Student $student, GetStudentDetails $getStudentDetails): Response
    {
        $this->authorize('view', $student);

        return Inertia::render('Backoffice/Students/Show', [
            'student' => $getStudentDetails($student),
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $this->authorize('create', Student::class);

        $data = $request->validated();
        $payload = $this->buildPayload($data);
        $payload['etablissement_id'] = $this->resolveEtablissementId($request, $data, null);

        $student = Student::create([
            ...$payload,
            'reference' => ReferenceGenerator::make('ETU', 'students'),
        ]);

        $this->storePhoto($student, $request);

        return $this->backToListPreservingFilters($request, 'backoffice.students.index')
            ->with('success', __('Student created.'));
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $data = $request->validated();
        $payload = $this->buildPayload($data);
        $payload['etablissement_id'] = $this->resolveEtablissementId($request, $data, $student->etablissement_id);

        $student->update($payload);
        $this->storePhoto($student, $request);

        return $this->backToListPreservingFilters($request, 'backoffice.students.index')
            ->with('success', __('Student updated.'));
    }

    /**
     * ⚠ Une fiche qui porte de l'ARGENT ou un DOSSIER ne se supprime jamais
     * — super-admin compris. Seules les lignes d'APPEL orphelines cèdent.
     *
     * Le cas réel (21/09/2026) : une fiche créée par erreur au guichet,
     * appelée deux jours dans un groupe où elle n'a jamais été inscrite,
     * puis abandonnée. Elle n'a ni inscription, ni paiement, ni chèque —
     * seulement deux « Absent » qui ne décrivent personne. La garde la
     * bloquait au même titre qu'un dossier vivant, si bien que la seule
     * issue était la console.
     *
     * Un super-admin peut donc FORCER, et le forçage retire les présences
     * dans la même transaction. Trois bornes qui ne bougent pas :
     *   1. les quatre autres verrous (inscriptions, encaissements,
     *      remboursements, chèques) restent ABSOLUS — le forçage ne les
     *      regarde même pas, il ne sait qu'effacer des appels ;
     *   2. l'écran NOMME ce qui va être détruit (date, statut, groupe)
     *      avant de demander confirmation : une suppression en aveugle
     *      n'est pas une décision ;
     *   3. les lignes retirées sont journalisées AVANT de disparaître —
     *      c'est la seule trace qui restera de ce qu'on a effacé.
     */
    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        $student->loadCount(['inscriptions', 'encaissements', 'remboursements']);

        $chequesCount = DB::table('cheques')->where('student_id', $student->id)->count();

        // Ces quatre-là ne cèdent JAMAIS : de l'argent et un dossier ne se
        // suppriment pas, ils se corrigent par écriture compensatoire (§11).
        if ($student->inscriptions_count || $student->encaissements_count
            || $student->remboursements_count || $chequesCount) {
            throw ValidationException::withMessages([
                'delete' => __('This student has activity history and cannot be deleted.'),
            ]);
        }

        $presences = $this->presencesOrphelines($student);

        if ($presences->isNotEmpty()) {
            // Seul un super-admin force, et seulement s'il l'a demandé
            // explicitement après avoir vu la liste.
            if (! $request->user()?->can('delete', $student) || ! $request->boolean('force')
                || ! $request->user()->hasRole(Role::SUPER_ADMIN)) {
                throw ValidationException::withMessages([
                    'delete' => __('This student has activity history and cannot be deleted.'),
                ]);
            }

            activity('student')
                ->performedOn($student)
                ->event('presences_orphelines_supprimees')
                ->withProperties([
                    'student_id' => $student->id,
                    'reference' => $student->reference,
                    'nom_complet' => $student->nomComplet(),
                    'presences' => $presences->all(),
                ])
                ->log(sprintf(
                    '%d ligne(s) de présence orpheline(s) supprimée(s) avec la fiche %s %s (aucune inscription dans ces groupes)',
                    $presences->count(),
                    $student->reference,
                    $student->nomComplet(),
                ));

            DB::table('presences')->where('student_id', $student->id)->delete();
        }

        $student->delete();

        return $this->backToListPreservingFilters($request, 'backoffice.students.index')
            ->with('success', __('Student deleted.'));
    }

    /**
     * Les lignes d'appel de cette fiche, avec de quoi les RECONNAÎTRE à
     * l'écran : une date et un statut nus ne disent pas à l'utilisateur ce
     * qu'il s'apprête à effacer, le groupe si.
     *
     * Appelé par `destroy()` (qui les supprime) ET par `deleteBlockers()`
     * (qui les montre) — une seule définition, sinon le modal finirait par
     * annoncer autre chose que ce que le serveur retire.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function presencesOrphelines(Student $student)
    {
        return DB::table('presences as p')
            ->join('seances as se', 'se.id', '=', 'p.seance_id')
            ->leftJoin('groups as g', 'g.id', '=', 'se.group_id')
            ->where('p.student_id', $student->id)
            ->orderBy('se.date_seance')
            ->get(['p.id', 'se.date_seance', 'p.statut', 'se.group_id', 'g.nom as groupe'])
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'date' => (string) $r->date_seance,
                'statut' => (string) $r->statut,
                'groupe' => $r->groupe ?? ('#'.$r->group_id),
            ]);
    }

    /**
     * Ce qui empêche la suppression, pour que le modal le DISE au lieu de
     * répéter « historique d'activité » — un message qui ne nomme rien
     * laisse l'utilisateur sans la moindre idée de quoi faire ensuite.
     *
     * Lecture seule : la décision reste à `destroy()`, qui revérifie tout
     * (les ids arrivent du navigateur, §16).
     */
    public function deleteBlockers(Request $request, Student $student): \Illuminate\Http\JsonResponse
    {
        $this->authorize('delete', $student);

        $student->loadCount(['inscriptions', 'encaissements', 'remboursements']);

        $presences = $this->presencesOrphelines($student);

        return response()->json([
            'inscriptions' => $student->inscriptions_count,
            'encaissements' => $student->encaissements_count,
            'remboursements' => $student->remboursements_count,
            'cheques' => DB::table('cheques')->where('student_id', $student->id)->count(),
            'presences' => $presences->all(),
            // Le forçage n'efface QUE des présences : dès qu'un autre
            // verrou est levé, il n'y a rien à proposer.
            'forcable' => $presences->isNotEmpty()
                && ! $student->inscriptions_count
                && ! $student->encaissements_count
                && ! $student->remboursements_count
                && DB::table('cheques')->where('student_id', $student->id)->doesntExist()
                && (bool) $request->user()?->hasRole(Role::SUPER_ADMIN),
        ]);
    }

    /**
     * The centre a student is written to follows §11's context rule: a
     * centre active in the top bar always wins over anything the client
     * posts (the form doesn't even show the select then — but the server
     * must not trust that). Only a global user in « Tous les centres »
     * picks the centre on the form, and even then only one they can access.
     * On edit, an absent/ignored choice keeps the student's current centre.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveEtablissementId(Request $request, array $data, ?int $current): ?int
    {
        $context = app(CurrentContext::class);

        if (! $context->isAllCenters()) {
            // Create: the active centre. Edit: keep the record's own centre
            // (a multi-centre employee working in centre A must never
            // silently MOVE a centre-B student by saving the modal), only
            // adopting the active centre when the record has none.
            return $current ?? $context->etablissementId();
        }

        $posted = isset($data['etablissement_id']) && $data['etablissement_id'] !== null && $data['etablissement_id'] !== ''
            ? (int) $data['etablissement_id']
            : null;

        if ($posted !== null) {
            abort_unless(app(CenterAccessService::class)->canAccessCenter($request->user(), $posted), 403);

            return $posted;
        }

        return $current;
    }

    /**
     * Builds the mass-assignment payload shared by store/update: combines
     * the phone country + national parts for telephone/whatsapp/parent_*
     * into the stored "+212…" form and normalizes empty strings to null,
     * exactly like StudentsIndex::save().
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildPayload(array $data): array
    {
        $phonePays = $data['phone_pays'] ?? Countries::DEFAULT;

        return [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'sexe' => $data['sexe'] ?? null,
            'date_naissance' => $data['date_naissance'] ?? null,
            'cin' => $data['cin'] ?? null,
            'telephone' => Countries::join($phonePays, $data['telephone'] ?? null),
            'whatsapp' => Countries::join($phonePays, $data['whatsapp'] ?? null),
            'email' => $data['email'] ?? null,
            'adresse' => $data['adresse'] ?? null,
            'niveau' => $data['niveau'] ?? null,
            'domaine' => $data['domaine'] ?? null,
            'examen_type' => $data['examen_type'] ?? null,
            'parent_nom' => $data['parent_nom'] ?? null,
            'parent_relation' => $data['parent_relation'] ?? null,
            'parent_sexe' => $data['parent_sexe'] ?? null,
            'parent_cin' => $data['parent_cin'] ?? null,
            'parent_telephone' => Countries::join($phonePays, $data['parent_telephone'] ?? null),
            'parent_whatsapp' => Countries::join($phonePays, $data['parent_whatsapp'] ?? null),
            'note' => $data['note'] ?? null,
        ];
    }

    /**
     * Attach the uploaded picture to the single-file "photo" collection (a
     * new upload replaces the previous one) — same as
     * StudentsIndex::save()'s media handling.
     */
    private function storePhoto(Student $student, Request $request): void
    {
        if (! $request->hasFile('photo')) {
            return;
        }

        $photo = $request->file('photo');

        $student->addMedia($photo->getRealPath())
            ->usingFileName('photo-'.$student->id.'.'.$photo->getClientOriginalExtension())
            ->toMediaCollection('photo');
    }
}
