<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Encaissements;

use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Livewire\Backoffice\Concerns\WithCaisseSelection;
use App\Livewire\Backoffice\Concerns\WithCenterContext;
use App\Livewire\Backoffice\Concerns\WithPerPage;
use App\Models\Caisse;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
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
    use WithCaisseSelection;
    use WithCenterContext;
    use WithPagination;
    use WithPerPage;

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

    /**
     * One row per unpaid fee of the chosen registration, keyed by the fee's
     * id (not a plain 0-based index) so the Blade view can pair each row's
     * inputs with the matching entry of $fees (built separately in render()
     * with the full dû/payé/reste display data) without relying on both
     * collections staying in the same order. Filled in only for rows the
     * user actually pays in this submit — every touched row becomes its own
     * Encaissement (see save()). Not used in edit mode.
     *
     * @var array<int, array{fee_id: int, montant: string, methode: string, date_paiement: string}>
     */
    public array $paymentLines = [];

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
     * Edition mirrors UpdateEncaissementRequest (montant/caisse_id absent on
     * purpose) and validates the single frozen row's methode/date/cheque
     * fields. Creation validates one or more rows of $paymentLines instead —
     * only rows the user actually filled in (StoreEncaissementRequest's
     * shape applied per touched row).
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        if ($this->editingId !== null) {
            $cheque = ['nullable', 'required_if:methode,'.Encaissement::METHODE_CHEQUE];

            return [
                'methode' => ['required', Rule::in(Encaissement::METHODES)],
                'date_paiement' => ['required', 'date'],
                'numero_cheque' => [...$cheque, 'string', 'max:50'],
                'banque' => [...$cheque, 'string', 'max:100'],
                'date_echeance_cheque' => [...$cheque, 'date'],
                'note' => ['nullable', 'string'],
            ];
        }

        $rules = [
            'student_id' => ['required', 'exists:students,id'],
            'inscription_id' => ['required', 'exists:inscriptions,id'],
            'caisse_id' => ['required', 'exists:caisses,id'],
            'note' => ['nullable', 'string'],
            // At least one row must be touched — fails before reaching save()'s
            // own empty-lines guard, so the "paymentLines" error key is always
            // populated together with the other required-field errors.
            'paymentLines' => [function (string $attribute, mixed $value, \Closure $fail): void {
                $touched = collect($this->paymentLines)->contains(fn (array $line) => ($line['montant'] ?? '') !== '');
                if (! $touched) {
                    $fail(__('Enter an amount for at least one fee.'));
                }
            }],
        ];

        $anyCheque = false;

        foreach ($this->paymentLines as $i => $line) {
            if (($line['montant'] ?? '') === '') {
                // Untouched row — not part of this submit, no rules needed.
                continue;
            }

            $reste = $this->resteDuFeeById((int) ($line['fee_id'] ?? 0));
            $rules["paymentLines.$i.montant"] = ['required', 'numeric', 'min:0.01', 'max:'.max(0.01, $reste)];
            $rules["paymentLines.$i.methode"] = ['required', Rule::in(Encaissement::METHODES)];
            $rules["paymentLines.$i.date_paiement"] = ['required', 'date'];
            $anyCheque = $anyCheque || $line['methode'] === Encaissement::METHODE_CHEQUE;
        }

        // Shared « Détails du chèque » block: required once, if ANY touched
        // row uses Chèque — applies the same cheque to every Chèque row.
        $cheque = ['nullable', Rule::requiredIf($anyCheque)];
        $rules['numero_cheque'] = [...$cheque, 'string', 'max:50'];
        $rules['banque'] = [...$cheque, 'string', 'max:100'];
        $rules['date_echeance_cheque'] = [...$cheque, 'date'];

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
        // A payment always hits the signed-in employee's own till — there is
        // no till picker in this modal (every employee owns exactly one,
        // CaisseProvisioner).
        $this->caisse_id = auth()->user()?->employee?->caisses()->value('id');
        $this->showModal = true;
    }

    /** Changing the student drops the enrollment/payment rows below it. */
    public function updatedStudentId(): void
    {
        $this->inscription_id = null;
        $this->paymentLines = [];
        $this->resetValidation(['inscription_id', 'paymentLines']);
    }

    /** Changing the enrollment reloads its fees as fresh payment rows. */
    public function updatedInscriptionId(): void
    {
        $this->loadPaymentLines();
        $this->resetValidation('paymentLines');
    }

    /** One row per unpaid fee of the chosen registration, amount left blank. */
    private function loadPaymentLines(): void
    {
        $this->paymentLines = [];

        if ($this->inscription_id === null) {
            return;
        }

        $this->paymentLines = InscriptionFee::query()
            ->where('inscription_id', $this->inscription_id)
            ->orderBy('date_echeance')
            ->get()
            ->filter(fn (InscriptionFee $fee) => $this->resteDuFeeById($fee->id) > 0)
            ->mapWithKeys(fn (InscriptionFee $fee) => [
                $fee->id => [
                    'fee_id' => $fee->id,
                    'montant' => '',
                    'methode' => Encaissement::METHODE_ESPECES,
                    'date_paiement' => now()->toDateString(),
                ],
            ])
            ->all();
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

    /** Amount still owed on a given fee (montant − payments already in). */
    public function resteDuFeeById(int $feeId): float
    {
        $fee = InscriptionFee::find($feeId);

        if ($fee === null) {
            return 0.0;
        }

        return round(max(0, (float) $fee->montant - $fee->montantPaye()), 2);
    }

    /** Whether any touched payment row uses Chèque (drives the shared cheque-details block). */
    public function anyLineIsCheque(): bool
    {
        return collect($this->paymentLines)->contains(
            fn (array $line) => ($line['montant'] ?? '') !== '' && ($line['methode'] ?? null) === Encaissement::METHODE_CHEQUE
        );
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

        // Re-read from the live component array, not $data: rules() only
        // declares rules for touched rows' montant/methode/date_paiement, so
        // Livewire's validate() would strip the untouched fee_id key out of
        // $data['paymentLines'] entirely. rules()'s own 'paymentLines' rule
        // already guarantees at least one row is touched by this point.
        $touchedLines = collect($this->paymentLines)
            ->filter(fn (array $line) => ($line['montant'] ?? '') !== '');

        // One Encaissement per touched row, all inside one transaction: if
        // any row is invalid or its fee doesn't belong to the selected
        // enrollment, every row in this submit rolls back together.
        DB::transaction(function () use ($touchedLines, $data, $agent, $action): void {
            foreach ($touchedLines as $line) {
                $fee = InscriptionFee::find($line['fee_id']);

                if ($fee === null || $fee->inscription_id !== (int) $data['inscription_id']) {
                    throw ValidationException::withMessages([
                        'paymentLines' => __('One of the fees does not belong to the selected registration.'),
                    ]);
                }

                $isCheque = $line['methode'] === Encaissement::METHODE_CHEQUE;

                // ⚠ Domain action ONLY: it creates the payment, increments
                // caisses.solde and recomputes the fee statut in one transaction.
                $action->handle([
                    'student_id' => $data['student_id'],
                    'inscription_fee_id' => $fee->id,
                    'montant' => $line['montant'],
                    'methode' => $line['methode'],
                    'date_paiement' => $line['date_paiement'],
                    'caisse_id' => $data['caisse_id'],
                    // Shared « Détails du chèque » block: same numéro/banque/
                    // échéance applied to every Chèque row in this submit.
                    'numero_cheque' => $isCheque ? ($this->numero_cheque ?: null) : null,
                    'banque' => $isCheque ? ($this->banque ?: null) : null,
                    'date_echeance_cheque' => $isCheque ? ($this->date_echeance_cheque ?: null) : null,
                    'note' => $this->note ?: null,
                ], $agent);
            }
        });

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
            'montant', 'caisse_id', 'date_paiement', 'paymentLines',
            'numero_cheque', 'banque', 'date_echeance_cheque', 'note',
        ]);
        $this->methode = Encaissement::METHODE_ESPECES;
        $this->resetValidation();
    }

    /**
     * Selectable students respecting center access AND the active center.
     * Computed: memoized per request — unaffected by search/filter/date
     * field updates within the same round trip.
     *
     * @return Collection<int, Student>
     */
    #[Computed]
    public function students(): Collection
    {
        $centerAccess = app(CenterAccessService::class);
        $user = auth()->user();

        return Student::query()
            ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->orderBy('nom')
            ->get();
    }

    public function render(): View
    {
        $centerAccess = app(CenterAccessService::class);
        $context = app(CurrentContext::class);
        $user = auth()->user();

        // Tills the user may pay into — center scoped + narrowed to the
        // active center from the top-bar switcher.
        $caisses = $this->caissesAccessibles(activesOnly: true);

        // Deliberately a second query, not a reuse of $caisses above: this one
        // is NOT restricted to Active tills (a payment recorded while its till
        // was still active must stay visible after the till is deactivated).
        $accessibleCaisseIds = Caisse::query()
            ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->pluck('id');

        $encaissements = Encaissement::query()
            ->with(['student', 'fee', 'caisse'])
            // A payment belongs to the center of its till (EncaissementPolicy).
            ->whereIn('caisse_id', $accessibleCaisseIds)
            ->when($this->caisseFilter !== '', fn ($q) => $q->where('caisse_id', (int) $this->caisseFilter))
            ->when($this->methodeFilter !== '', fn ($q) => $q->where('methode', $this->methodeFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('date_paiement', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('date_paiement', '<=', $this->dateTo))
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($sub): void {
                    $sub->where('reference', 'ilike', "%{$this->search}%")
                        ->orWhereHas('student', fn ($s) => $s->where('nom', 'ilike', "%{$this->search}%")
                            ->orWhere('prenom', 'ilike', "%{$this->search}%")
                            ->orWhere('reference', 'ilike', "%{$this->search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage);

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
                        'date_echeance' => $fee->date_echeance,
                        'du' => $du,
                        'paye' => $paye,
                        'reste' => round(max(0, $du - $paye), 2),
                        'statut' => self::statutForFee($du, $paye),
                    ];
                });

        return view('livewire.backoffice.encaissements.encaissements-index', [
            'encaissements' => $encaissements,
            'students' => $this->students(),
            'inscriptions' => $inscriptions,
            'fees' => $fees,
            'caisses' => $caisses,
            'methodes' => Encaissement::METHODES,
        ])->layout('components.backoffice.layout.app', ['title' => __('Payments')]);
    }
}
