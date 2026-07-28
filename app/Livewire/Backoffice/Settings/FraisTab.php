<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Settings;

use App\Models\Frais;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Fee catalog CRUD tab (Paramètres). The managed list of predefined frais
 * (Frais annuel, Frais d'inscription, Frais de Juillet, Frais dexam ÖSD…),
 * assigned to groups and used as "Frais disponibles" on inscriptions.
 */
final class FraisTab extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $nom = '';

    public string $statut = Frais::STATUT_ACTIF;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:150', Rule::unique('frais', 'nom')->ignore($this->editingId)],
            'statut' => ['required', Rule::in(Frais::STATUTS)],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Frais::class);
    }

    public function create(): void
    {
        $this->authorize('create', Frais::class);
        $this->closeModal();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $f = Frais::findOrFail($id);
        $this->authorize('update', $f);

        $this->editingId = $f->id;
        $this->nom = $f->nom;
        $this->statut = $f->statut;
        $this->showModal = true;
    }

    public function save(): void
    {
        if ($this->editingId) {
            $f = Frais::findOrFail($this->editingId);
            $this->authorize('update', $f);
        } else {
            $this->authorize('create', Frais::class);
        }

        $data = $this->validate();

        Frais::updateOrCreate(['id' => $this->editingId], $data);

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: __('Fee saved.'));
    }

    public function delete(int $id): void
    {
        $f = Frais::withCount(['groups'])->findOrFail($id);
        $this->authorize('delete', $f);

        if ($f->groups_count) {
            $this->addError('delete', __('This fee is assigned to groups and cannot be deleted.'));

            return;
        }

        $f->delete();
        $this->dispatch('toast', type: 'success', message: __('Fee deleted.'));
    }

    public function closeModal(): void
    {
        $this->reset(['showModal', 'editingId', 'nom']);
        $this->statut = Frais::STATUT_ACTIF;
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.backoffice.settings.frais-tab', [
            'frais' => Frais::query()->withCount('groups')->orderBy('nom')->paginate(8),
        ]);
    }
}
