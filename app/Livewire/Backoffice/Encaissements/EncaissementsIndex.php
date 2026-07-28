<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Encaissements;

use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Livewire\Backoffice\Concerns\WithCenterContext;
use App\Models\Caisse;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Student payments (money IN) — list + modal add/edit.
 *
 * ⚠ Invariants (CLAUDE.md §11):
 *  - creation ALWAYS goes through the Domain action EnregistrerEncaissement,
 *    which creates the row, increments caisses.solde and recomputes the fee
 *    statut in ONE transaction. This component never touches `solde` and never
 *    calls Encaissement::create() itself;
 *  - a payment is NEVER deleted — there is no delete()/destroy path here;
 *  - `montant` and `caisse_id` are NOT editable after creation (see
 *    UpdateEncaissementRequest) — the edit modal only exposes methode /
 *    date_paiement / cheque fields / note.
 *
 * Center-scoped through CenterAccessService + WithCenterContext: a payment
 * reaches its center through its till (caisse.etablissement_id), same rule as
 * EncaissementPolicy.
 */
final class EncaissementsIndex extends Component
{
    use AuthorizesRequests;
    use WithCenterContext;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $caisseFilter = '';

    public string $methodeFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    // Cascade selects: student → inscription → fee.
    public ?int $student_id = null;

    public ?int $inscription_id = null;

    public ?int $inscription_fee_id = null;

    public string $montant = '';

    public string $methode = Encaissement::METHODE_ESPECES;

    public ?string $date_paiement = null;

    public ?int $caisse_id = null;

    public string $numero_cheque = '';

    public string $banque = '';

    public ?string $date_echeance_cheque = null;

    public string $note = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Encaissement::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCaisseFilter(): void
    {
        $this->resetPage();
    }

    public function updatingMethodeFilter(): void
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
     * Creation mirrors StoreEncaissementRequest; edition mirrors
     * UpdateEncaissementRequest (montant/caisse_id absent on purpose).
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $cheque = ['nullable', 'required_if:methode,'.Encaissement::METHODE_CHEQUE];

        $rules = [
            'methode' => ['required', Rule::in(Encaissement::METHODES)],
            'date_paiement' => ['required', 'date'],
            'numero_cheque' => [...$cheque, 'string', 'max:50'],
            'banque' => [...$cheque, 'string', 'max:100'],
            'date_echeance_cheque' => [...$cheque, 'date'],
            'note' => ['nullable', 'string'],
        ];

        if ($this->editingId !== null) {
            return $rules;
        }

        $rules['student_id'] = ['required', 'exists:students,id'];
        $rules['inscription_id'] = ['required', 'exists:inscriptions,id'];
        $rules['inscription_fee_id'] = ['required', 'exists:inscription_fees,id'];
        $rules['caisse_id'] = ['required', 'exists:caisses,id'];
        // Never accept more than what is still owed on the selected fee.
        $rules['montant'] = ['required', 'numeric', 'min:0.01', 'max:'.max(0.01, $this->resteDuFee())];

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'montant.max' => __('The amount cannot exceed the remaining balance of this fee.'),
        ];
    }

    public function create(): void
    {
        $this->authorize('create', Encaissement::class);
        $this->resetForm();
        $this->date_paiement = now()->toDateString();
        $this->showModal = true;
    }

    /** Changing the student drops the inscription/fee selection below it. */
    public function updatedStudentId(): void
    {
        $this->inscription_id = null;
        $this->inscription_fee_id = null;
        $this->montant = '';
        $this->resetValidation(['inscription_id', 'inscription_fee_id', 'montant']);
    }

    /** Changing the enrollment drops the fee selection below it. */
    public function updatedInscriptionId(): void
    {
        $this->inscription_fee_id = null;
        $this->montant = '';
        $this->resetValidation(['inscription_fee_id', 'montant']);
    }

    /** Picking a fee pre-fills the amount with what is still owed on it. */
    public function updatedInscriptionFeeId(): void
    {
        $reste = $this->resteDuFee();
        $this->montant = $reste > 0 ? (string) $reste : '';
        $this->resetValidation('montant');
    }

    /** Chèque fields only make sense for the Chèque method. */
    public function updatedMethode(): void
    {
        if ($this->methode !== Encaissement::METHODE_CHEQUE) {
            $this->numero_cheque = '';
            $this->banque = '';
            $this->date_echeance_cheque = null;
            $this->resetValidation(['numero_cheque', 'banque', 'date_echeance_cheque']);
        }
    }

    /** Amount still owed on the selected fee (montant − payments already in). */
    public function resteDuFee(): float
    {
        $fee = $this->selectedFee();

        if ($fee === null) {
            return 0.0;
        }

        return round(max(0, (float) $fee->montant - $fee->montantPaye()), 2);
    }

    private function selectedFee(): ?InscriptionFee
    {
        if ($this->inscription_fee_id === null) {
            return null;
        }

        return InscriptionFee::find($this->inscription_fee_id);
    }

    /**
     * Payment status of a fee derived from its payments (not the stored
     * statut) so the modal stays live while the user picks.
     */
    public static function statutForFee(float $montant, float $paye): string
    {
        return match (true) {
            $paye >= $montant && $montant > 0 => InscriptionFee::STATUT_PAYE,
            $paye > 0 => InscriptionFee::STATUT_PAYE_PARTIELLEMENT,
            default => InscriptionFee::STATUT_NON_PAYE,
        };
    }

    public function edit(int $id): void
    {
        $encaissement = Encaissement::with('caisse')->findOrFail($id);
        $this->authorize('update', $encaissement);

        $this->resetForm();
        $this->editingId = $encaissement->id;
        // Read-only context in the modal (montant / caisse are frozen).
        $this->student_id = $encaissement->student_id;
        $this->inscription_fee_id = $encaissement->inscription_fee_id;
        $this->inscription_id = $encaissement->fee?->inscription_id;
        $this->caisse_id = $encaissement->caisse_id;
        $this->montant = (string) $encaissement->montant;
        $this->methode = $encaissement->methode;
        $this->date_paiement = $encaissement->date_paiement?->toDateString();
        $this->numero_cheque = (string) $encaissement->numero_cheque;
        $this->banque = (string) $encaissement->banque;
        $this->date_echeance_cheque = $encaissement->date_echeance_cheque?->toDateString();
        $this->note = (string) $encaissement->note;
        $this->showModal = true;
    }

    public function save(EnregistrerEncaissement $action): void
    {
        $editing = $this->editingId !== null;

        if ($editing) {
            $encaissement = Encaissement::with('caisse')->findOrFail($this->editingId);
            $this->authorize('update', $encaissement);
        } else {
            $this->authorize('create', Encaissement::class);
        }

        $data = $this->validate();

        if ($editing) {
            // montant / caisse_id are deliberately absent: correcting them
            // requires a remboursement + a new encaissement.
            $encaissement->update([
                'methode' => $data['methode'],
                'date_paiement' => $data['date_paiement'],
                'numero_cheque' => $this->methode === Encaissement::METHODE_CHEQUE ? ($this->numero_cheque ?: null) : null,
                'banque' => $this->methode === Encaissement::METHODE_CHEQUE ? ($this->banque ?: null) : null,
                'date_echeance_cheque' => $this->methode === Encaissement::METHODE_CHEQUE ? ($this->date_echeance_cheque ?: null) : null,
                'note' => $this->note ?: null,
            ]);

            $this->closeModal();
            $this->dispatch('toast', type: 'success', message: __('Payment updated.'));

            return;
        }

        // Money-moving operations need an employee identity for the trail.
        $agent = auth()->user()?->employee;

        if ($agent === null) {
            $this->addError('caisse_id', __('Your account is not linked to an employee record.'));

            return;
        }

        // The fee must belong to the selected enrollment (a tampered id must
        // not let a payment land on someone else's fee).
        $fee = InscriptionFee::find($data['inscription_fee_id']);

        if ($fee === null || $fee->inscription_id !== (int) $data['inscription_id']) {
            $this->addError('inscription_fee_id', __('This fee does not belong to the selected registration.'));

            return;
        }

        // ⚠ Domain action ONLY: it creates the payment, increments
        // caisses.solde and recomputes the fee statut in one transaction.
        $action->handle([
            'student_id' => $data['student_id'],
            'inscription_fee_id' => $data['inscription_fee_id'],
            'montant' => $data['montant'],
            'methode' => $data['methode'],
            'date_paiement' => $data['date_paiement'],
            'caisse_id' => $data['caisse_id'],
            'numero_cheque' => $this->methode === Encaissement::METHODE_CHEQUE ? ($this->numero_cheque ?: null) : null,
            'banque' => $this->methode === Encaissement::METHODE_CHEQUE ? ($this->banque ?: null) : null,
            'date_echeance_cheque' => $this->methode === Encaissement::METHODE_CHEQUE ? ($this->date_echeance_cheque ?: null) : null,
            'note' => $this->note ?: null,
        ], $agent);

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: __('Payment recorded.'));
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'student_id', 'inscription_id', 'inscription_fee_id',
            'montant', 'caisse_id', 'date_paiement',
            'numero_cheque', 'banque', 'date_echeance_cheque', 'note',
        ]);
        $this->methode = Encaissement::METHODE_ESPECES;
        $this->resetValidation();
    }

    public function render(): View
    {
        $centerAccess = app(CenterAccessService::class);
        $context = app(CurrentContext::class);
        $user = auth()->user();

        // Tills the user may pay into — center scoped + narrowed to the
        // active center from the top-bar switcher.
        $caisses = Caisse::query()
            ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->where('statut', Caisse::STATUT_ACTIVE)
            ->orderBy('nom')
            ->get();

        $accessibleCaisseIds = Caisse::query()
            ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->pluck('id');

        $encaissements = Encaissement::query()
            ->with(['student', 'fee.inscription.group', 'caisse', 'agent'])
            // A payment belongs to the center of its till (EncaissementPolicy).
            ->whereIn('caisse_id', $accessibleCaisseIds)
            ->when($this->caisseFilter !== '', fn ($q) => $q->where('caisse_id', (int) $this->caisseFilter))
            ->when($this->methodeFilter !== '', fn ($q) => $q->where('methode', $this->methodeFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('date_paiement', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('date_paiement', '<=', $this->dateTo))
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($sub): void {
                    $sub->where('reference', 'like', "%{$this->search}%")
                        ->orWhereHas('student', fn ($s) => $s->where('nom', 'like', "%{$this->search}%")
                            ->orWhere('prenom', 'like', "%{$this->search}%")
                            ->orWhere('reference', 'like', "%{$this->search}%"));
                });
            })
            ->latest()
            ->paginate(10);

        // Selectable students respect center access AND the active center.
        $students = Student::query()
            ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->orderBy('nom')
            ->get();

        // Cascade level 2: the chosen student's enrollments (active year).
        $inscriptions = $this->student_id === null
            ? collect()
            : Inscription::query()
                ->with('group')
                ->where('student_id', $this->student_id)
                ->when($context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
                ->latest()
                ->get();

        // Cascade level 3: that enrollment's fee lines with dû / payé / reste.
        $fees = $this->inscription_id === null
            ? collect()
            : InscriptionFee::query()
                ->with('encaissements')
                ->where('inscription_id', $this->inscription_id)
                ->orderBy('date_echeance')
                ->get()
                ->map(function (InscriptionFee $fee): array {
                    $du = (float) $fee->montant;
                    $paye = (float) $fee->encaissements->sum('montant');

                    return [
                        'id' => $fee->id,
                        'nom' => $fee->nom,
                        'du' => $du,
                        'paye' => $paye,
                        'reste' => round(max(0, $du - $paye), 2),
                        'statut' => self::statutForFee($du, $paye),
                    ];
                });

        return view('livewire.backoffice.encaissements.encaissements-index', [
            'encaissements' => $encaissements,
            'students' => $students,
            'inscriptions' => $inscriptions,
            'fees' => $fees,
            'caisses' => $caisses,
            'methodes' => Encaissement::METHODES,
            'reste' => $this->resteDuFee(),
        ])->layout('components.backoffice.layout.app', ['title' => __('Payments')]);
    }
}
