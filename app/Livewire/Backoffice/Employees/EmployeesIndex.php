<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Employees;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Livewire\Backoffice\Concerns\WithCenterContext;
use App\Livewire\Backoffice\Concerns\WithPhoneCountry;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Employees list with add/edit in a modal (no separate pages).
 * Creating an employee auto-generates its login (EmployeeObserver) — the
 * one-time username + password are flashed to the session and surfaced here.
 * Categories come from Employee::CATEGORIES (the screenshot list).
 */
final class EmployeesIndex extends Component
{
    use AuthorizesRequests;
    use WithCenterContext;
    use WithFileUploads;
    use WithPagination;
    use WithPhoneCountry;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $categorieFilter = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    // Form fields
    public string $nom = '';

    public string $prenom = '';

    public string $sexe = 'Homme';

    public string $categorie = Employee::CATEGORIE_ENSEIGNANT;

    public string $statut = Employee::STATUT_ACTIF;

    public string $telephone = '';

    public string $whatsapp = '';

    public string $email = '';

    public string $adresse = '';

    public string $note = '';

    /** New photo upload (nullable) — stored in the "photo" media collection. */
    public $photo = null;

    /** URL of the already-stored photo when editing. */
    public ?string $existingPhotoUrl = null;

    public ?string $date_naissance = null;

    public ?string $date_embauche = null;

    public ?string $salaire = null;

    public ?int $etablissement_id = null;

    // One-time credentials shown after creating an employee.
    public ?string $newUsername = null;

    public ?string $newPassword = null;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'sexe' => ['required', Rule::in(Employee::SEXES)],
            'categorie' => ['required', Rule::in(Employee::CATEGORIES)],
            'statut' => ['required', Rule::in(Employee::STATUTS)],
            'telephone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'date_embauche' => ['nullable', 'date'],
            'salaire' => ['nullable', 'numeric', 'min:0'],
            'etablissement_id' => ['nullable', 'exists:etablissements,id'],
            ...$this->phonePaysRules(),
        ];
    }

    public function mount(): void
    {
        $this->authorize('employees.view');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategorieFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('employees.create');
        $this->resetForm();
        // Auto-scope new records to the active center from the top-bar switcher.
        $this->etablissement_id = app(CurrentContext::class)->etablissementId();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('employees.update');

        $e = Employee::findOrFail($id);
        $this->editingId = $e->id;
        $this->nom = $e->nom;
        $this->prenom = $e->prenom;
        $this->sexe = $e->sexe ?? 'Homme';
        $this->categorie = $e->categorie;
        $this->statut = $e->statut;
        $this->resetPhonePays(); // don't inherit the country of a previously edited record
        $this->fillPhone('telephone', $e->telephone);
        $this->fillPhone('whatsapp', $e->whatsapp);
        $this->email = (string) $e->email;
        $this->adresse = (string) $e->adresse;
        $this->note = (string) $e->note;
        $this->photo = null;
        $this->existingPhotoUrl = $e->getFirstMediaUrl('photo') ?: null;
        $this->date_naissance = $e->date_naissance?->toDateString();
        $this->date_embauche = $e->date_embauche?->toDateString();
        $this->salaire = $e->salaire !== null ? (string) $e->salaire : null;
        $this->etablissement_id = $e->etablissement_id;
        $this->newUsername = null;
        $this->newPassword = null;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'employees.update' : 'employees.create');

        $data = $this->validate();
        // `photo` is media-library managed, not a column — never let it reach
        // the mass-assignment payload.
        unset($data['phonePays'], $data['photo']);
        $payload = [
            ...$data,
            'telephone' => $this->phoneValue('telephone'),
            'whatsapp' => $this->phoneValue('whatsapp'),
            'email' => $this->email ?: null,
            'adresse' => $this->adresse ?: null,
            'note' => $this->note ?: null,
            'date_naissance' => $this->date_naissance ?: null,
            'date_embauche' => $this->date_embauche ?: null,
            'salaire' => $this->salaire !== null && $this->salaire !== '' ? $this->salaire : null,
        ];

        if ($this->editingId) {
            $employee = Employee::findOrFail($this->editingId);
            $employee->update($payload);
            $this->storePhoto($employee);
            $this->closeModal();
            $this->dispatch('toast', type: 'success', message: __('Employee updated.'));

            return;
        }

        // Create → EmployeeObserver auto-creates the User and flashes the
        // one-time username + password to the session.
        $employee = Employee::create([
            ...$payload,
            'reference' => ReferenceGenerator::make('EMP', 'employees'),
        ]);

        $this->storePhoto($employee);

        // Surface the one-time credentials inside the modal.
        $this->newUsername = session('new_employee_username');
        $this->newPassword = session('new_employee_password');
        $this->editingId = -1; // marks "created" state so the modal shows creds

        $this->dispatch('toast', type: 'success', message: __('Employee created. Its login credentials have been generated.'));
    }

    /**
     * Attach the uploaded picture to the single-file "photo" collection
     * (a new upload replaces the previous one). No-op when nothing was picked.
     */
    private function storePhoto(Employee $employee): void
    {
        if ($this->photo === null) {
            return;
        }

        $employee->addMedia($this->photo->getRealPath())
            ->usingFileName('photo-'.$employee->id.'.'.$this->photo->getClientOriginalExtension())
            ->toMediaCollection('photo');
    }

    public function delete(int $id): void
    {
        $this->authorize('employees.delete');

        $e = Employee::withCount(['groupes', 'encaissements', 'depenses', 'remboursements'])->findOrFail($id);

        if ($e->groupes_count || $e->encaissements_count || $e->depenses_count || $e->remboursements_count) {
            $this->addError('delete', __('This employee has activity history and cannot be deleted. Deactivate instead.'));

            return;
        }

        $e->delete();
        $this->dispatch('toast', type: 'success', message: __('Employee deleted.'));
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'nom', 'prenom', 'telephone', 'whatsapp', 'email', 'adresse', 'note',
            'photo', 'existingPhotoUrl',
            'date_naissance', 'date_embauche', 'salaire', 'etablissement_id',
            'newUsername', 'newPassword',
        ]);
        $this->sexe = 'Homme';
        $this->categorie = Employee::CATEGORIE_ENSEIGNANT;
        $this->statut = Employee::STATUT_ACTIF;
        $this->resetPhonePays('telephone', 'whatsapp');
        $this->resetValidation();
    }

    public function render(): View
    {
        $employees = Employee::query()
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
                        ->orWhere('adresse', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->categorieFilter !== '', fn ($q) => $q->where('categorie', $this->categorieFilter))
            ->latest()
            ->paginate(10);

        $context = app(CurrentContext::class);

        return view('livewire.backoffice.employees.employees-index', [
            'employees' => $employees,
            'categories' => Employee::CATEGORIES,
            'statuts' => Employee::STATUTS,
            'etablissements' => Etablissement::query()->orderBy('nom_centre')->get(),
            'centerLocked' => ! $context->isAllCenters(),
            'contextCenterName' => $context->etablissement()?->nom_centre,
        ])->layout('components.backoffice.layout.app', ['title' => __('Employees')]);
    }
}
