<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Settings;

use App\Livewire\Backoffice\Concerns\WithPhoneCountry;
use App\Models\Etablissement;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Centers CRUD tab — inline add/edit form (no modal plugin dependency) +
 * live paginated table. Authorizes in mount() AND in every mutation, and
 * maps CRUD to the seeded centers.* permissions (policy-backed).
 */
final class EtablissementsTab extends Component
{
    use AuthorizesRequests;
    use WithPagination;
    use WithPhoneCountry;

    protected $paginationTheme = 'bootstrap';

    public bool $showModal = false;

    public ?int $editingId = null;

    #[Validate('required|string|max:150')]
    public string $nom_centre = '';

    #[Validate('required|string|max:100')]
    public string $ville = '';

    #[Validate('nullable|string|max:20')]
    public string $telephone = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

    #[Validate('boolean')]
    public bool $siege_social = false;

    /**
     * Merged with the #[Validate] attribute rules by $this->validate().
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return $this->phonePaysRules();
    }

    public function mount(): void
    {
        $this->authorize('centers.view');
    }

    public function create(): void
    {
        $this->authorize('centers.create');
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('centers.update');

        $e = Etablissement::findOrFail($id);
        $this->editingId = $e->id;
        $this->nom_centre = $e->nom_centre;
        $this->ville = $e->ville;
        $this->resetPhonePays(); // don't inherit the country of a previously edited record
        $this->fillPhone('telephone', $e->telephone);
        $this->email = (string) $e->email;
        $this->siege_social = (bool) $e->siege_social;
        $this->showModal = true;
    }

    public function save(): void
    {
        // Re-authorize on the mutation itself — never trust mount() alone.
        $this->authorize($this->editingId ? 'centers.update' : 'centers.create');

        $data = $this->validate();

        Etablissement::updateOrCreate(
            ['id' => $this->editingId],
            [
                'nom_centre' => $data['nom_centre'],
                'ville' => $data['ville'],
                'telephone' => $this->phoneValue('telephone'),
                'email' => $this->email ?: null,
                'siege_social' => $this->siege_social,
            ],
        );

        $this->resetForm();
        $this->dispatch('settings-saved');
        $this->dispatch('toast', type: 'success', message: __('Center saved.'));
    }

    public function delete(int $id): void
    {
        $this->authorize('centers.delete');

        $e = Etablissement::withCount(['salles', 'employees', 'students'])->findOrFail($id);

        // A center still holding rooms/staff/students cannot be removed
        // (restrict FKs would throw). Guard cleanly instead.
        if ($e->salles_count || $e->employees_count || $e->students_count) {
            $this->addError('delete', __('This center is still in use and cannot be deleted.'));

            return;
        }

        $e->delete();
        $this->dispatch('toast', type: 'success', message: __('Center deleted.'));
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
        $this->reset(['editingId', 'nom_centre', 'ville', 'telephone', 'email', 'siege_social', 'showModal']);
        $this->resetPhonePays('telephone');
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.backoffice.settings.etablissements-tab', [
            'etablissements' => Etablissement::query()
                ->withCount('salles')
                ->orderByDesc('siege_social')
                ->orderBy('nom_centre')
                ->paginate(8),
        ]);
    }
}
