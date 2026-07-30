<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\CaisseTransfers;

use App\Domain\Finance\Actions\DemanderTransfertCaisse;
use App\Domain\Finance\Actions\ValiderTransfertCaisse;
use App\Livewire\Backoffice\Concerns\WithCenterContext;
use App\Livewire\Backoffice\Concerns\WithPerPage;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Services\Authorization\CenterAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Till-to-till transfers list + modal request/edit, with the two-step
 * request → validate workflow (structure doc §7, schema §15).
 *
 * ⚠ Money invariants:
 *  - REQUEST goes through Domain\Finance\Actions\DemanderTransfertCaisse:
 *    balances are NOT touched, only the *_avant snapshots are captured;
 *  - VALIDATION goes through Domain\Finance\Actions\ValiderTransfertCaisse:
 *    balances move there, in one transaction, and the action refuses
 *    self-validation (requester ≠ validator) — never bypassed here;
 *  - a validated transfer is immutable; only "En attente" rows can be edited
 *    or cancelled (UpdateCaisseTransferRequest);
 *  - a transfer is NEVER deleted (no delete method, no destroy route).
 */
final class CaisseTransfersIndex extends Component
{
    use AuthorizesRequests;
    use WithCenterContext;
    use WithPagination;
    use WithPerPage;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    /** Active status tab: En attente (default) / Validé / Annulé. */
    public string $statutFilter = CaisseTransfer::STATUT_EN_ATTENTE;

    /** Source-till filter (id as string so the Select2 empty option means "all"). */
    public string $caisseFilter = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    // Form fields
    public ?int $caisse_source_id = null;

    public ?int $caisse_destination_id = null;

    public string $montant = '';

    public string $note = '';

    public function mount(): void
    {
        $this->authorize('viewAny', CaisseTransfer::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCaisseFilter(): void
    {
        $this->resetPage();
    }

    /** Switch the status tab (En attente / Validé / Annulé). */
    public function setStatutTab(string $statut): void
    {
        if (! in_array($statut, CaisseTransfer::STATUTS, true)) {
            return;
        }

        $this->statutFilter = $statut;
        $this->resetPage();
    }

    /**
     * Mirrors Store/UpdateCaisseTransferRequest: the tills and the amount are
     * validated only while requesting — a pending transfer may only have its
     * note edited (the amount defines the money that will move).
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'note' => ['nullable', 'string'],
        ];

        if ($this->editingId === null) {
            $rules['caisse_source_id'] = ['required', 'exists:caisses,id'];
            $rules['caisse_destination_id'] = ['required', 'exists:caisses,id', 'different:caisse_source_id'];
            $rules['montant'] = ['required', 'numeric', 'min:0.01'];
        }

        return $rules;
    }

    public function create(): void
    {
        $this->authorize('create', CaisseTransfer::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $transfer = CaisseTransfer::with('caisseSource')->findOrFail($id);
        $this->authorize('update', $transfer);

        // A validated (or cancelled) transfer is frozen — never editable.
        if ($transfer->statut !== CaisseTransfer::STATUT_EN_ATTENTE) {
            $this->dispatch('toast', type: 'error', message: __('Only a pending transfer can be edited.'));

            return;
        }

        $this->editingId = $transfer->id;
        $this->caisse_source_id = $transfer->caisse_source_id;
        $this->caisse_destination_id = $transfer->caisse_destination_id;
        $this->montant = (string) $transfer->montant;
        $this->note = (string) $transfer->note;
        $this->showModal = true;
    }

    public function save(DemanderTransfertCaisse $action): void
    {
        $editing = $this->editingId !== null;

        if ($editing) {
            $transfer = CaisseTransfer::with('caisseSource')->findOrFail($this->editingId);
            $this->authorize('update', $transfer);

            if ($transfer->statut !== CaisseTransfer::STATUT_EN_ATTENTE) {
                $this->addError('note', __('Only a pending transfer can be edited.'));
                $this->dispatch('toast', type: 'error', message: __('Only a pending transfer can be edited.'));

                return;
            }
        } else {
            $this->authorize('create', CaisseTransfer::class);
        }

        $data = $this->validate();

        if ($editing) {
            // Only the note is editable while pending — the tills and the
            // amount define the money that will move on validation.
            $transfer->update(['note' => ($data['note'] ?? '') === '' ? null : $data['note']]);
        } else {
            $requester = auth()->user()?->employee;

            if ($requester === null) {
                $this->addError('caisse_source_id', __('Your account is not linked to any employee record.'));
                $this->dispatch('toast', type: 'error', message: __('Your account is not linked to any employee record.'));

                return;
            }

            // Domain action: creates the request with the *_avant snapshots.
            // Balances stay UNTOUCHED until a different employee validates.
            $action->handle([
                'caisse_source_id' => $data['caisse_source_id'],
                'caisse_destination_id' => $data['caisse_destination_id'],
                'montant' => $data['montant'],
                'note' => ($data['note'] ?? '') === '' ? null : $data['note'],
            ], $requester);
        }

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: $editing
            ? __('Transfer updated.')
            : __('Transfer requested — awaiting validation.'));
    }

    /**
     * Approval step — moves real money. Permission + center gate come from
     * CaisseTransferPolicy::validate(); the "requester ≠ validator" rule is
     * enforced by the Domain action and surfaced here as an error toast.
     */
    public function validateTransfer(int $id, ValiderTransfertCaisse $action): void
    {
        $transfer = CaisseTransfer::with('caisseSource')->findOrFail($id);
        $this->authorize('validate', $transfer);

        $validator = auth()->user()?->employee;

        if ($validator === null) {
            $this->addError('validate', __('Your account is not linked to any employee record.'));
            $this->dispatch('toast', type: 'error', message: __('Your account is not linked to any employee record.'));

            return;
        }

        try {
            // Domain action: refuses self-validation and a non-pending transfer,
            // then moves BOTH balances in one transaction. Never bypassed.
            $action->handle($transfer, $validator);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? __('This transfer cannot be validated.');
            $this->addError('validate', $message);
            $this->dispatch('toast', type: 'error', message: $message);

            return;
        }

        $this->dispatch('toast', type: 'success', message: __('Transfer validated — balances have been updated.'));
    }

    /** Cancel a pending transfer (no money ever moved — nothing to reverse). */
    public function cancel(int $id): void
    {
        $transfer = CaisseTransfer::with('caisseSource')->findOrFail($id);
        $this->authorize('update', $transfer);

        if ($transfer->statut !== CaisseTransfer::STATUT_EN_ATTENTE) {
            $this->addError('validate', __('Only a pending transfer can be cancelled.'));
            $this->dispatch('toast', type: 'error', message: __('Only a pending transfer can be cancelled.'));

            return;
        }

        $transfer->update(['statut' => CaisseTransfer::STATUT_ANNULE]);
        $this->dispatch('toast', type: 'success', message: __('Transfer cancelled.'));
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'caisse_source_id', 'caisse_destination_id', 'montant', 'note']);
        $this->resetValidation();
    }

    public function render(): View
    {
        $centerAccess = app(CenterAccessService::class);
        $user = auth()->user();
        $currentEmployeeId = $user?->employee?->id;

        // A transfer reaches its center through the SOURCE till
        // (CaisseTransferPolicy::centerId) — scope on that relation.
        $transfers = CaisseTransfer::query()
            ->with(['caisseSource', 'caisseDestination', 'requestedBy', 'validatedBy'])
            ->whereHas('caisseSource', function (Builder $q) use ($centerAccess, $user): void {
                $centerAccess->scopeAccessibleCenters($q, $user);
                $this->scopeToActiveCenter($q);
            })
            ->when($this->statutFilter !== '', fn ($q) => $q->where('statut', $this->statutFilter))
            ->when($this->caisseFilter !== '', fn ($q) => $q->where('caisse_source_id', (int) $this->caisseFilter))
            ->when($this->search !== '', function ($q): void {
                $term = "%{$this->search}%";
                $q->where(function ($sub) use ($term): void {
                    $sub->where('reference', 'ilike', $term)
                        ->orWhere('note', 'ilike', $term);
                });
            })
            ->latest()
            ->paginate($this->perPage);

        // Per-status counts for the tab badges (same center scope, ignores search).
        $statutCounts = CaisseTransfer::query()
            ->whereHas('caisseSource', function (Builder $q) use ($centerAccess, $user): void {
                $centerAccess->scopeAccessibleCenters($q, $user);
                $this->scopeToActiveCenter($q);
            })
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        return view('livewire.backoffice.caisse-transfers.caisse-transfers-index', [
            'transfers' => $transfers,
            'statutCounts' => $statutCounts,
            'currentEmployeeId' => $currentEmployeeId,
            'caisses' => Caisse::query()
                ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, $user))
                ->tap(fn ($q) => $this->scopeToActiveCenter($q))
                ->orderBy('nom')
                ->get(),
        ]);
    }
}
