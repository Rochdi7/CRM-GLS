<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\TypesDepenses;

use App\Models\TypeDepense;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Expense types list + modal CRUD (schema §12).
 *
 * is_system rows are seeded by TypeDepenseSeeder and LOCKED: payroll automation
 * and till transfers depend on their exact names. They can never be edited or
 * deleted here — TypeDepensePolicy refuses update/delete on them and every
 * mutation below re-checks it. The form never sets is_system (always false).
 */
final class TypesDepensesIndex extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $nom = '';

    public string $statut = TypeDepense::STATUT_ACTIF;

    public function mount(): void
    {
        $this->authorize('viewAny', TypeDepense::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nom' => [
                'required', 'string', 'max:100',
                Rule::unique('types_depenses', 'nom')->ignore($this->editingId),
            ],
            'statut' => ['required', Rule::in(TypeDepense::STATUTS)],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', TypeDepense::class);
        $this->resetForm();
        $this->showModal = true;
    }

    /**
     * System types are locked (schema §12) — payroll automation and till
     * transfers depend on their exact names. The policy already refuses them,
     * but super-admin bypasses every policy via Gate::before, so the invariant
     * is asserted explicitly here as well (same as TypeDepenseController).
     */
    private function guardSystemType(TypeDepense $type): void
    {
        abort_if($type->is_system, 403, __('System expense types cannot be modified.'));
    }

    public function edit(int $id): void
    {
        $type = TypeDepense::findOrFail($id);
        $this->guardSystemType($type);
        $this->authorize('update', $type);

        $this->editingId = $type->id;
        $this->nom = $type->nom;
        $this->statut = $type->statut;
        $this->showModal = true;
    }

    public function save(): void
    {
        $editing = $this->editingId !== null;

        if ($editing) {
            $type = TypeDepense::findOrFail($this->editingId);
            $this->guardSystemType($type);
            $this->authorize('update', $type);
        } else {
            $this->authorize('create', TypeDepense::class);
        }

        $data = $this->validate();

        if ($editing) {
            $type->update($data);
        } else {
            // The admin form only ever creates custom types (schema §12).
            TypeDepense::create([...$data, 'is_system' => false]);
        }

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: $editing
            ? __('Expense type updated.')
            : __('Expense type created.'));
    }

    public function delete(int $id): void
    {
        $type = TypeDepense::withCount('depenses')->findOrFail($id);
        $this->guardSystemType($type);
        $this->authorize('delete', $type);

        if ($type->depenses_count) {
            $this->addError('delete', __('This expense type is used by expenses and cannot be deleted.'));

            return;
        }

        $type->delete();
        $this->dispatch('toast', type: 'success', message: __('Expense type deleted.'));
    }

    public function closeModal(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['showModal', 'editingId', 'nom']);
        $this->statut = TypeDepense::STATUT_ACTIF;
        $this->resetValidation();
    }

    public function render(): View
    {
        $types = TypeDepense::query()
            ->withCount('depenses')
            ->when($this->search !== '', fn ($q) => $q->where('nom', 'like', "%{$this->search}%"))
            // System types first, then custom ones alphabetically.
            ->orderByDesc('is_system')
            ->orderBy('nom')
            ->paginate(10);

        return view('livewire.backoffice.types-depenses.types-depenses-index', [
            'types' => $types,
        ]);
    }
}
