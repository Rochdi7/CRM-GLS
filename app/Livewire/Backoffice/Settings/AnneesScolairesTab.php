<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Settings;

use App\Models\AnneeScolaire;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Academic years CRUD tab. Enforces the single-default invariant
 * (only one par_defaut at a time) in one transaction, and maps CRUD to the
 * seeded academic-years.* permissions.
 */
final class AnneesScolairesTab extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $nom = '';

    public string $date_debut = '';

    public string $date_fin = '';

    public bool $par_defaut = false;

    public bool $inscription_ouverte = true;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:20', Rule::unique('annees_scolaires', 'nom')->ignore($this->editingId)],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'par_defaut' => ['boolean'],
            'inscription_ouverte' => ['boolean'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('academic-years.view');
    }

    public function create(): void
    {
        $this->authorize('academic-years.create');
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('academic-years.update');

        $a = AnneeScolaire::findOrFail($id);
        $this->editingId = $a->id;
        $this->nom = $a->nom;
        $this->date_debut = $a->date_debut->toDateString();
        $this->date_fin = $a->date_fin->toDateString();
        $this->par_defaut = (bool) $a->par_defaut;
        $this->inscription_ouverte = (bool) $a->inscription_ouverte;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'academic-years.update' : 'academic-years.create');

        $data = $this->validate();

        DB::transaction(function () use ($data): void {
            // Only one default year at a time.
            if ($data['par_defaut']) {
                AnneeScolaire::query()
                    ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
                    ->where('par_defaut', true)
                    ->update(['par_defaut' => false]);
            }

            AnneeScolaire::updateOrCreate(['id' => $this->editingId], $data);
        });

        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: __('Academic year saved.'));
    }

    public function delete(int $id): void
    {
        $this->authorize('academic-years.delete');

        $a = AnneeScolaire::withCount(['groups', 'inscriptions'])->findOrFail($id);

        if ($a->groups_count || $a->inscriptions_count) {
            $this->addError('delete', __('This academic year is still in use and cannot be deleted.'));

            return;
        }

        $a->delete();
        $this->dispatch('toast', type: 'success', message: __('Academic year deleted.'));
    }

    public function closeModal(): void
    {
        $this->resetForm();
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'nom', 'date_debut', 'date_fin', 'par_defaut', 'showModal']);
        $this->inscription_ouverte = true;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.backoffice.settings.annees-scolaires-tab', [
            'annees' => AnneeScolaire::query()->orderByDesc('date_debut')->paginate(8),
        ]);
    }
}
