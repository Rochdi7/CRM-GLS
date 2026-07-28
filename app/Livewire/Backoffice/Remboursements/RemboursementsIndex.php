<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Remboursements;

use App\Domain\Finance\Actions\EnregistrerRemboursement;
use App\Livewire\Backoffice\Concerns\WithCenterContext;
use App\Models\Caisse;
use App\Models\Remboursement;
use App\Models\Student;
use App\Services\Authorization\CenterAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Student refunds list + modal add/edit.
 *
 * ⚠ Money invariants (schema §10 + §14):
 *  - creation ALWAYS goes through Domain\Finance\Actions\EnregistrerRemboursement,
 *    which creates the row and decrements caisses.solde in ONE transaction —
 *    solde is never touched here;
 *  - a refund is NEVER deleted (no delete method, no destroy route);
 *  - `montant` and `caisse_id` are not editable after creation (mirrors
 *    UpdateRemboursementRequest) — the till balance already moved.
 */
final class RemboursementsIndex extends Component
{
    use AuthorizesRequests;
    use WithCenterContext;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    /** Till filter (id as string so the Select2 empty option means "all"). */
    public string $caisseFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    // Form fields
    public ?int $beneficiaire_id = null;

    public ?int $caisse_id = null;

    public string $montant = '';

    public string $date_remboursement = '';

    public string $motif = '';

    public string $note = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Remboursement::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCaisseFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    /**
     * Mirrors Store/UpdateRemboursementRequest: `montant` + `caisse_id` are
     * validated only while creating — on edit the rules are dropped entirely so
     * those properties can never reach the update payload.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'date_remboursement' => ['required', 'date'],
            'motif' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ];

        if ($this->editingId === null) {
            $rules['beneficiaire_id'] = ['required', 'exists:students,id'];
            $rules['caisse_id'] = ['required', 'exists:caisses,id'];
            $rules['montant'] = ['required', 'numeric', 'min:0.01'];
        }

        return $rules;
    }

    public function create(): void
    {
        $this->authorize('create', Remboursement::class);
        $this->resetForm();
        $this->date_remboursement = now()->toDateString();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $remboursement = Remboursement::with('caisse')->findOrFail($id);
        $this->authorize('update', $remboursement);

        $this->editingId = $remboursement->id;
        $this->beneficiaire_id = $remboursement->beneficiaire_id;
        // Displayed read-only in the modal — never sent back on update.
        $this->caisse_id = $remboursement->caisse_id;
        $this->montant = (string) $remboursement->montant;
        $this->date_remboursement = $remboursement->date_remboursement?->toDateString() ?? '';
        $this->motif = (string) $remboursement->motif;
        $this->note = (string) $remboursement->note;
        $this->showModal = true;
    }

    public function save(EnregistrerRemboursement $action): void
    {
        $editing = $this->editingId !== null;

        if ($editing) {
            $remboursement = Remboursement::with('caisse')->findOrFail($this->editingId);
            $this->authorize('update', $remboursement);
        } else {
            $this->authorize('create', Remboursement::class);
        }

        $data = $this->validate();

        if ($editing) {
            // montant / caisse_id / beneficiaire_id are deliberately excluded:
            // the till balance already moved (UpdateRemboursementRequest).
            $remboursement->update([
                'date_remboursement' => $data['date_remboursement'],
                'motif' => ($data['motif'] ?? '') === '' ? null : $data['motif'],
                'note' => ($data['note'] ?? '') === '' ? null : $data['note'],
            ]);
        } else {
            $agent = auth()->user()?->employee;

            if ($agent === null) {
                $this->addError('caisse_id', __('Your account is not linked to any employee record.'));
                $this->dispatch('toast', type: 'error', message: __('Your account is not linked to any employee record.'));

                return;
            }

            // Domain action: creates the refund AND decrements caisses.solde
            // in ONE transaction. Never touch solde here.
            $action->handle([
                'beneficiaire_id' => $data['beneficiaire_id'],
                'caisse_id' => $data['caisse_id'],
                'montant' => $data['montant'],
                'date_remboursement' => $data['date_remboursement'],
                'motif' => ($data['motif'] ?? '') === '' ? null : $data['motif'],
                'note' => ($data['note'] ?? '') === '' ? null : $data['note'],
            ], $agent);
        }

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: $editing ? __('Refund updated.') : __('Refund recorded.'));
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'beneficiaire_id', 'caisse_id', 'montant',
            'date_remboursement', 'motif', 'note',
        ]);
        $this->resetValidation();
    }

    public function render(): View
    {
        $centerAccess = app(CenterAccessService::class);
        $user = auth()->user();

        // A refund reaches its center through the till it came out of
        // (RemboursementPolicy::centerId) — scope on the caisse relation.
        $remboursements = Remboursement::query()
            ->with(['beneficiaire', 'caisse', 'agent'])
            ->whereHas('caisse', function (Builder $q) use ($centerAccess, $user): void {
                $centerAccess->scopeAccessibleCenters($q, $user);
                $this->scopeToActiveCenter($q);
            })
            ->when($this->caisseFilter !== '', fn ($q) => $q->where('caisse_id', (int) $this->caisseFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('date_remboursement', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('date_remboursement', '<=', $this->dateTo))
            ->when($this->search !== '', function ($q): void {
                $term = "%{$this->search}%";
                $q->where(function ($sub) use ($term): void {
                    $sub->where('reference', 'like', $term)
                        ->orWhereHas('beneficiaire', fn ($s) => $s
                            ->where('nom', 'like', $term)
                            ->orWhere('prenom', 'like', $term));
                });
            })
            ->latest()
            ->paginate(10);

        $caisses = Caisse::query()
            ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->orderBy('nom')
            ->get();

        return view('livewire.backoffice.remboursements.remboursements-index', [
            'remboursements' => $remboursements,
            'caisses' => $caisses,
            'students' => Student::query()
                ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, $user))
                ->tap(fn ($q) => $this->scopeToActiveCenter($q))
                ->orderBy('nom')
                ->get(),
        ]);
    }
}
