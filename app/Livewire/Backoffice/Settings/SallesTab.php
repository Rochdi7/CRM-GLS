<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Settings;

use App\Livewire\Backoffice\Concerns\WithCenterContext;
use App\Models\Etablissement;
use App\Models\Salle;
use App\Services\Context\CurrentContext;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Rooms CRUD tab — each room belongs to one center. Maps CRUD to the seeded
 * rooms.* permissions.
 */
final class SallesTab extends Component
{
    use AuthorizesRequests;
    use WithCenterContext;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $nom = '';

    public ?int $etablissement_id = null;

    public ?int $capacite = null;

    public string $statut = Salle::STATUT_ACTIVE;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100'],
            'etablissement_id' => ['required', 'exists:etablissements,id'],
            'capacite' => ['nullable', 'integer', 'min:1'],
            'statut' => ['required', Rule::in(Salle::STATUTS)],
        ];
    }

    public function mount(): void
    {
        $this->authorize('rooms.view');
    }

    public function create(): void
    {
        $this->authorize('rooms.create');
        $this->resetForm();
        // Default new rooms to the active center from the top-bar switcher.
        $this->etablissement_id = app(CurrentContext::class)->etablissementId();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('rooms.update');

        $s = Salle::findOrFail($id);
        $this->editingId = $s->id;
        $this->nom = $s->nom;
        $this->etablissement_id = $s->etablissement_id;
        $this->capacite = $s->capacite;
        $this->statut = $s->statut;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'rooms.update' : 'rooms.create');

        $data = $this->validate();

        Salle::updateOrCreate(['id' => $this->editingId], $data);

        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: __('Room saved.'));
    }

    public function delete(int $id): void
    {
        $this->authorize('rooms.delete');

        $s = Salle::withCount('groups')->findOrFail($id);

        if ($s->groups_count) {
            $this->addError('delete', __('This room is still assigned to groups and cannot be deleted.'));

            return;
        }

        $s->delete();
        $this->dispatch('toast', type: 'success', message: __('Room deleted.'));
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
        $this->reset(['editingId', 'nom', 'etablissement_id', 'capacite', 'showModal']);
        $this->statut = Salle::STATUT_ACTIVE;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.backoffice.settings.salles-tab', [
            'salles' => Salle::query()
                ->with('etablissement')
                // Narrow to the center selected in the top-bar switcher.
                ->tap(fn ($q) => $this->scopeToActiveCenter($q))
                ->orderBy('nom')
                ->paginate(8),
            'etablissements' => Etablissement::query()->orderBy('nom_centre')->get(),
        ]);
    }
}
