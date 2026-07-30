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
use App\Services\Context\CurrentContext;
use App\Support\Phone\Countries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $ageSort = (string) $request->string('ageSort');
        $perPage = (int) $request->integer('perPage', GetStudentsList::DEFAULT_PER_PAGE);

        return Inertia::render('Backoffice/Students/Index', [
            'students' => $getStudentsList($request->user(), $search, $niveauFilter, $ageSort, $perPage),
            'filters' => [
                'search' => $search,
                'niveauFilter' => $niveauFilter,
                'ageSort' => $ageSort,
                'perPage' => in_array($perPage, GetStudentsList::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetStudentsList::DEFAULT_PER_PAGE,
            ],
            'perPageOptions' => GetStudentsList::PER_PAGE_OPTIONS,
            'niveaux' => Student::NIVEAUX,
            'domaines' => Student::DOMAINES,
            'examenTypes' => Student::EXAMEN_TYPES,
            'sexes' => Student::SEXES,
            'parentRelations' => Student::PARENT_RELATIONS,
            'niveauxAvecDomaine' => Student::NIVEAUX_AVEC_DOMAINE,
            'niveauStudium' => Student::NIVEAU_STUDIUM,
            'countries' => Countries::all(),
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

        $student->update($payload);
        $this->storePhoto($student, $request);

        return redirect()->route('backoffice.students.index')
            ->with('success', __('Student updated.'));
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        $student->loadCount(['inscriptions', 'encaissements', 'remboursements']);

        if ($student->inscriptions_count || $student->encaissements_count || $student->remboursements_count) {
            throw ValidationException::withMessages([
                'delete' => __('This student has activity history and cannot be deleted.'),
            ]);
        }

        $student->delete();

        return redirect()->route('backoffice.students.index')
            ->with('success', __('Student deleted.'));
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
            'etablissement_id' => $data['etablissement_id'] ?? null,
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
