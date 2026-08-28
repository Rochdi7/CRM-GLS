<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Domain\Students\Queries\GetStudentDetails;
use App\Domain\Students\Queries\GetStudentsList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Students\StoreStudentRequest;
use App\Http\Requests\Backoffice\Students\UpdateStudentRequest;
use App\Models\Etablissement;
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
 * one-for-one (Phase 8, docs/phase-8-students-groups-inventory.md), following
 * the Phase 7 Employees pattern. The Livewire component and its view are left
 * completely untouched as unreferenced fallback code.
 */
final class StudentController extends Controller
{
    public function index(Request $request, GetStudentsList $getStudentsList): Response
    {
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
            'etablissements' => Etablissement::query()->orderBy('nom_centre')->get(['id', 'nom_centre']),
            'centerLocked' => ! $context->isAllCenters(),
            'contextCenterId' => $context->etablissementId(),
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

        return redirect()->route('backoffice.students.index')
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

        return redirect()->route('backoffice.students.index')
            ->with('success', __('Student updated.'));
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        $student->loadCount(['inscriptions', 'encaissements', 'remboursements']);

        if ($student->inscriptions_count || $student->encaissements_count || $student->remboursements_count
            || DB::table('cheques')->where('student_id', $student->id)->exists()
            || DB::table('presences')->where('student_id', $student->id)->exists()) {
            throw ValidationException::withMessages([
                'delete' => __('This student has activity history and cannot be deleted.'),
            ]);
        }

        $student->delete();

        return redirect()->route('backoffice.students.index')
            ->with('success', __('Student deleted.'));
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
