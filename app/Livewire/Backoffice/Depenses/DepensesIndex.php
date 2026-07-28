<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Depenses;

use App\Domain\Expenses\Actions\EnregistrerDepense;
use App\Livewire\Backoffice\Concerns\WithCenterContext;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Group;
use App\Models\TypeDepense;
use App\Services\Authorization\CenterAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Expenses (money OUT) list + modal add/edit with justificatif uploads.
 *
 * ⚠ Invariants (gls-crm-schema.md §10/§13):
 *  - creation goes through the Domain action EnregistrerDepense, which creates
 *    the row, generates the DEP- reference and decrements caisses.solde in ONE
 *    transaction — the balance is NEVER touched here;
 *  - an expense is NEVER deleted (audit trail) — no delete method exists;
 *  - `montant` and `caisse_id` are NOT editable after creation (the till
 *    balance already moved) — mirrors UpdateDepenseRequest.
 *
 * A Depense has no etablissement_id: it reaches its center through the till it
 * came out of, so center scoping goes through the `caisse` relation.
 */
final class DepensesIndex extends Component
{
    use AuthorizesRequests;
    use WithCenterContext;
    use WithFileUploads;
    use WithPagination;

    /** Accepted receipt types — mirrors Depense::registerMediaCollections(). */
    private const JUSTIFICATIF_MIMES = ['jpeg', 'jpg', 'png', 'webp', 'pdf'];

    private const JUSTIFICATIF_MAX_KB = 5120;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    /** Expense-type filter (id as string so the empty option means "all"). */
    public string $typeFilter = '';

    /** Till filter (id as string so the empty option means "all"). */
    public string $caisseFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    // Form fields
    public ?int $type_depense_id = null;

    public ?int $caisse_id = null;

    /** Optional link to the class/group the expense belongs to. */
    public ?int $group_id = null;

    public string $montant = '';

    /** How it was paid — Depense::METHODES (required when creating). */
    public ?string $methode_paiement = null;

    public ?string $date_depense = null;

    /** Supplier invoice number (free text). */
    public string $reference_facture = '';

    public string $description = '';

    public string $mots_cles = '';

    public string $note = '';

    /**
     * Newly picked receipts (multiple upload).
     *
     * @var array<int, mixed>
     */
    public array $justificatifs = [];

    /**
     * Already-stored receipts when editing: [media id => ['name', 'url']].
     *
     * @var array<int, array{name: string, url: string}>
     */
    public array $existingJustificatifs = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Depense::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
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
     * Mirrors StoreDepenseRequest / UpdateDepenseRequest: `montant` and
     * `caisse_id` are validated only while creating — on edit the rules are
     * dropped entirely so the properties can never reach the update payload.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'type_depense_id' => ['required', 'exists:types_depenses,id'],
            'date_depense' => ['required', 'date'],
            'reference_facture' => ['nullable', 'string', 'max:100'],
            'group_id' => ['nullable', 'exists:groups,id'],
            // Required on create; nullable on edit so rows predating the
            // field can still be corrected.
            'methode_paiement' => [
                $this->editingId === null ? 'required' : 'nullable',
                Rule::in(Depense::METHODES),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'mots_cles' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            // Same mime/size contract as the media collection.
            'justificatifs.*' => ['file', 'mimes:'.implode(',', self::JUSTIFICATIF_MIMES), 'max:'.self::JUSTIFICATIF_MAX_KB],
        ];

        if ($this->editingId === null) {
            $rules['caisse_id'] = ['required', 'exists:caisses,id'];
            $rules['montant'] = ['required', 'numeric', 'min:0.01'];
        }

        return $rules;
    }

    public function create(): void
    {
        $this->authorize('create', Depense::class);
        $this->resetForm();
        $this->date_depense = now()->toDateString();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $depense = Depense::with(['caisse', 'media'])->findOrFail($id);
        $this->authorize('update', $depense);

        $this->editingId = $depense->id;
        $this->type_depense_id = $depense->type_depense_id;
        $this->caisse_id = $depense->caisse_id;
        $this->group_id = $depense->group_id;
        // Displayed read-only in the modal — never sent back on update.
        $this->montant = (string) $depense->montant;
        $this->methode_paiement = $depense->methode_paiement;
        $this->date_depense = $depense->date_depense?->toDateString();
        $this->reference_facture = (string) $depense->reference_facture;
        $this->description = (string) $depense->description;
        $this->mots_cles = (string) $depense->mots_cles;
        $this->note = (string) $depense->note;
        $this->justificatifs = [];
        $this->loadExistingJustificatifs($depense);
        $this->showModal = true;
    }

    public function save(): void
    {
        $editing = $this->editingId !== null;

        if ($editing) {
            $depense = Depense::with('caisse')->findOrFail($this->editingId);
            $this->authorize('update', $depense);
        } else {
            $this->authorize('create', Depense::class);
        }

        $data = $this->validate();

        $payload = collect($data)
            ->except(['justificatifs'])
            ->map(fn ($v) => $v === '' ? null : $v)
            ->all();

        if ($editing) {
            // montant / caisse_id are absent from $payload by construction —
            // the till balance already moved, corrections need a compensating
            // entry (UpdateDepenseRequest).
            $depense->update($payload);
        } else {
            $employee = auth()->user()?->employee;

            if ($employee === null) {
                $this->addError('caisse_id', __('Your account is not linked to any employee record.'));

                return;
            }

            // Domain action: creates the expense, generates the DEP- reference
            // and decrements caisses.solde in ONE transaction.
            $depense = app(EnregistrerDepense::class)->handle($payload, $employee);
        }

        $this->storeJustificatifs($depense);

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: $editing ? __('Expense updated.') : __('Expense recorded.'));
    }

    /** Detach one stored receipt while editing (the expense itself stays). */
    public function removeJustificatif(int $mediaId): void
    {
        $depense = Depense::with(['caisse', 'media'])->findOrFail($this->editingId);
        $this->authorize('update', $depense);

        $media = $depense->getMedia('justificatifs')->firstWhere('id', $mediaId);

        if ($media !== null) {
            $media->delete();
        }

        $this->loadExistingJustificatifs($depense->refresh());
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'type_depense_id', 'caisse_id', 'group_id', 'montant',
            'methode_paiement', 'date_depense', 'reference_facture',
            'description', 'mots_cles', 'note', 'justificatifs', 'existingJustificatifs',
        ]);
        $this->resetValidation();
    }

    private function loadExistingJustificatifs(Depense $depense): void
    {
        $this->existingJustificatifs = $depense->getMedia('justificatifs')
            ->mapWithKeys(fn ($media) => [
                (int) $media->id => [
                    'name' => (string) $media->file_name,
                    // /media/<8-char-uuid>/… via ShortUuidPathGenerator.
                    'url' => $media->getUrl(),
                ],
            ])
            ->all();
    }

    private function storeJustificatifs(Depense $depense): void
    {
        foreach ($this->justificatifs as $file) {
            if ($file === null) {
                continue;
            }

            $depense->addMedia($file->getRealPath())
                ->usingFileName($depense->id.'-'.$file->getClientOriginalName())
                ->toMediaCollection('justificatifs');
        }
    }

    public function render(): View
    {
        $centerAccess = app(CenterAccessService::class);
        $user = auth()->user();

        $query = Depense::query()
            // An expense has no own center column — it is scoped through the
            // till it came out of (same rule as DepensePolicy::centerId()).
            ->whereHas('caisse', function (Builder $q) use ($centerAccess, $user): void {
                $centerAccess->scopeAccessibleCenters($q, $user);
                $this->scopeToActiveCenter($q);
            })
            ->when($this->typeFilter !== '', fn ($q) => $q->where('type_depense_id', (int) $this->typeFilter))
            ->when($this->caisseFilter !== '', fn ($q) => $q->where('caisse_id', (int) $this->caisseFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('date_depense', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('date_depense', '<=', $this->dateTo))
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($sub): void {
                    $sub->where('reference', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('mots_cles', 'like', "%{$this->search}%");
                });
            });

        // Sum of ALL rows matching the filters (not just the current page).
        $montantTotal = (float) (clone $query)->sum('montant');

        $depenses = $query
            ->with(['typeDepense', 'caisse', 'agent', 'media', 'group'])
            ->latest()
            ->paginate(10);

        return view('livewire.backoffice.depenses.depenses-index', [
            'depenses' => $depenses,
            'montantTotal' => $montantTotal,
            'typesDepenses' => TypeDepense::query()
                ->where('statut', TypeDepense::STATUT_ACTIF)
                ->orderBy('nom')->get(),
            // Tills follow permission + active-center scoping.
            'caisses' => Caisse::query()
                ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, $user))
                ->tap(fn ($q) => $this->scopeToActiveCenter($q))
                ->orderBy('nom')->get(),
            'methodes' => Depense::METHODES,
            // Optional class/group link — same scoping as the groups list.
            'groups' => Group::query()
                ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, $user))
                ->tap(fn ($q) => $this->scopeToActiveCenter($q))
                ->orderBy('nom')->get(),
            // Read-only balance shown next to the till select (legacy-app
            // « Solde » field). Fetched directly: when editing, the expense's
            // till may sit outside the filtered list above.
            'soldeCaisse' => $this->caisse_id !== null
                ? (float) (Caisse::query()->whereKey($this->caisse_id)->value('solde') ?? 0)
                : null,
        ]);
    }
}
