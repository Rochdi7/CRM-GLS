<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Students;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Livewire\Backoffice\Concerns\WithCenterContext;
use App\Livewire\Backoffice\Concerns\WithPhoneCountry;
use App\Models\Etablissement;
use App\Models\Student;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Students list + modal add/edit with photo upload (media library).
 * Center-scoped through CenterAccessService and StudentPolicy.
 */
final class StudentsIndex extends Component
{
    use AuthorizesRequests;
    use WithCenterContext;
    use WithFileUploads;
    use WithPagination;
    use WithPhoneCountry;

    private const PHONE_FIELDS = ['telephone', 'whatsapp', 'parent_telephone', 'parent_whatsapp'];

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $niveauFilter = '';

    /** Age column sort direction: '' (default recent-first), 'asc' or 'desc'. */
    public string $ageSort = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    // Form fields
    public string $nom = '';

    public string $prenom = '';

    public ?string $sexe = null;

    public ?string $date_naissance = null;

    public string $cin = '';

    public string $telephone = '';

    public string $whatsapp = '';

    public string $email = '';

    public string $adresse = '';

    public ?string $niveau = null;

    /** Shown only for Arbeit / Ausbildung (see Student::NIVEAUX_AVEC_DOMAINE). */
    public ?string $domaine = null;

    /** Shown only for Studium (STK / DSH). */
    public ?string $examen_type = null;

    public ?int $etablissement_id = null;

    public string $parent_nom = '';

    public ?string $parent_relation = null;

    public ?string $parent_sexe = null;

    public string $parent_cin = '';

    public string $parent_telephone = '';

    public string $parent_whatsapp = '';

    public string $note = '';

    /** New photo upload (nullable). */
    public $photo = null;

    /** URL of the already-stored photo when editing. */
    public ?string $existingPhotoUrl = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'sexe' => ['nullable', Rule::in(Student::SEXES)],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'cin' => ['nullable', 'string', 'max:30'],
            'niveau' => ['nullable', Rule::in(Student::NIVEAUX)],
            // The two orientation fields are required by — and only accepted
            // for — their own track, so a level switch can't leave a stale value.
            'domaine' => [
                Student::niveauDemandeDomaine($this->niveau) ? 'required' : 'nullable',
                Rule::in(Student::DOMAINES),
                Rule::excludeIf(! Student::niveauDemandeDomaine($this->niveau)),
            ],
            'examen_type' => [
                Student::niveauDemandeExamen($this->niveau) ? 'required' : 'nullable',
                Rule::in(Student::EXAMEN_TYPES),
                Rule::excludeIf(! Student::niveauDemandeExamen($this->niveau)),
            ],
            'etablissement_id' => ['nullable', 'exists:etablissements,id'],
            'parent_nom' => ['nullable', 'string', 'max:100'],
            'parent_relation' => ['nullable', Rule::in(Student::PARENT_RELATIONS)],
            'parent_sexe' => ['nullable', Rule::in(Student::SEXES)],
            'parent_cin' => ['nullable', 'string', 'max:30'],
            'parent_telephone' => ['nullable', 'string', 'max:20'],
            'parent_whatsapp' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
            ...$this->phonePaysRules(),
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Student::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingNiveauFilter(): void
    {
        $this->resetPage();
    }

    public function sortByAge(): void
    {
        $this->ageSort = $this->ageSort === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    /**
     * Switching level drops the orientation that no longer applies, so a
     * student who moves Studium → Arbeit can't keep a stale "DSH".
     */
    public function updatedNiveau(): void
    {
        if (! Student::niveauDemandeDomaine($this->niveau)) {
            $this->domaine = null;
        }

        if (! Student::niveauDemandeExamen($this->niveau)) {
            $this->examen_type = null;
        }

        $this->resetValidation(['domaine', 'examen_type']);
    }

    public function create(): void
    {
        $this->authorize('create', Student::class);
        $this->resetForm();
        // Default to the active center when the user isn't global.
        $this->etablissement_id = app(CurrentContext::class)->etablissementId();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $student = Student::findOrFail($id);
        $this->authorize('update', $student);

        $this->editingId = $student->id;
        $this->nom = $student->nom;
        $this->prenom = $student->prenom;
        $this->sexe = $student->sexe;
        $this->date_naissance = $student->date_naissance?->toDateString();
        $this->resetPhonePays(); // don't inherit the country of a previously edited record
        $this->fillPhone('telephone', $student->telephone);
        $this->fillPhone('whatsapp', $student->whatsapp);
        $this->email = (string) $student->email;
        $this->adresse = (string) $student->adresse;
        $this->cin = (string) $student->cin;
        $this->niveau = $student->niveau;
        $this->domaine = $student->domaine;
        $this->examen_type = $student->examen_type;
        $this->etablissement_id = $student->etablissement_id;
        $this->parent_nom = (string) $student->parent_nom;
        $this->parent_relation = $student->parent_relation;
        $this->parent_sexe = $student->parent_sexe;
        $this->parent_cin = (string) $student->parent_cin;
        $this->fillPhone('parent_telephone', $student->parent_telephone);
        $this->fillPhone('parent_whatsapp', $student->parent_whatsapp);
        $this->note = (string) $student->note;
        $this->photo = null;
        $this->existingPhotoUrl = $student->getFirstMediaUrl('photo') ?: null;
        $this->showModal = true;
    }

    public function save(): void
    {
        $editing = $this->editingId !== null;

        if ($editing) {
            $student = Student::findOrFail($this->editingId);
            $this->authorize('update', $student);
        } else {
            $this->authorize('create', Student::class);
        }

        $data = $this->validate();

        $payload = collect($data)->except(['photo', 'phonePays'])->map(fn ($v) => $v === '' ? null : $v)->all();

        foreach (self::PHONE_FIELDS as $field) {
            $payload[$field] = $this->phoneValue($field);
        }

        if ($editing) {
            $student->update($payload);
        } else {
            $student = Student::create([
                ...$payload,
                'reference' => ReferenceGenerator::make('ETU', 'students'),
            ]);
        }

        if ($this->photo !== null) {
            $student->addMedia($this->photo->getRealPath())
                ->usingFileName('photo-'.$student->id.'.'.$this->photo->getClientOriginalExtension())
                ->toMediaCollection('photo');
        }

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: $editing ? __('Student updated.') : __('Student created.'));
    }

    public function delete(int $id): void
    {
        $student = Student::withCount(['inscriptions', 'encaissements', 'remboursements'])->findOrFail($id);
        $this->authorize('delete', $student);

        if ($student->inscriptions_count || $student->encaissements_count || $student->remboursements_count) {
            $this->addError('delete', __('This student has activity history and cannot be deleted.'));

            return;
        }

        $student->delete();
        $this->dispatch('toast', type: 'success', message: __('Student deleted.'));
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'nom', 'prenom', 'sexe', 'date_naissance', 'cin', 'telephone', 'whatsapp',
            'email', 'adresse', 'niveau', 'domaine', 'examen_type',
            'etablissement_id', 'parent_nom', 'parent_relation',
            'parent_sexe', 'parent_cin', 'parent_telephone', 'parent_whatsapp', 'note',
            'photo', 'existingPhotoUrl',
        ]);
        $this->resetPhonePays(...self::PHONE_FIELDS);
        $this->resetValidation();
    }

    public function render(): View
    {
        $students = Student::query()
            // `media` eager-loaded for the avatar column (avoids N+1).
            ->with(['etablissement', 'media'])
            ->tap(fn ($q) => app(CenterAccessService::class)->scopeAccessibleCenters($q, auth()->user()))
            // Narrow to the center selected in the top-bar switcher.
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($sub): void {
                    $sub->where('nom', 'like', "%{$this->search}%")
                        ->orWhere('prenom', 'like', "%{$this->search}%")
                        ->orWhere('reference', 'like', "%{$this->search}%")
                        ->orWhere('cin', 'like', "%{$this->search}%")
                        ->orWhere('telephone', 'like', "%{$this->search}%");
                });
            })
            ->when($this->niveauFilter !== '', fn ($q) => $q->where('niveau', $this->niveauFilter))
            ->when(
                $this->ageSort !== '',
                // Age asc = youngest first = most recent birth date; unknown birth dates go last.
                fn ($q) => $q->orderByRaw('date_naissance IS NULL')
                    ->orderBy('date_naissance', $this->ageSort === 'asc' ? 'desc' : 'asc'),
                fn ($q) => $q->latest(),
            )
            ->paginate(10);

        $context = app(CurrentContext::class);

        return view('livewire.backoffice.students.students-index', [
            'students' => $students,
            'niveaux' => Student::NIVEAUX,
            'domaines' => Student::DOMAINES,
            'examenTypes' => Student::EXAMEN_TYPES,
            'sexes' => Student::SEXES,
            'parentRelations' => Student::PARENT_RELATIONS,
            'etablissements' => Etablissement::query()->orderBy('nom_centre')->get(),
            // When a specific center is active, new records are locked to it.
            'centerLocked' => ! $context->isAllCenters(),
            'contextCenterName' => $context->etablissement()?->nom_centre,
        ])->layout('components.backoffice.layout.app', ['title' => __('Students')]);
    }
}
