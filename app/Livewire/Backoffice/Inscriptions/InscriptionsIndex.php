<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Inscriptions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Livewire\Backoffice\Concerns\WithCenterContext;
use App\Livewire\Backoffice\Concerns\WithPhoneCountry;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Registrations (inscriptions) list + modal add/edit with manual fee lines.
 * On create, the fee lines are inserted with the inscription in one
 * transaction; montant_total defaults to the sum of the fee lines.
 * Center-scoped through CenterAccessService and InscriptionPolicy.
 */
final class InscriptionsIndex extends Component
{
    use AuthorizesRequests;
    use WithCenterContext;
    use WithPagination;
    use WithPhoneCountry;

    private const PHONE_FIELDS = ['new_telephone', 'new_whatsapp', 'new_parent_telephone', 'new_parent_whatsapp'];

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $statutFilter = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    /** "new" = create the student inline (default), "existing" = pick one. */
    public string $inscriptionMode = 'new';

    // Form fields
    public ?int $student_id = null;

    // Inline new-student fields (used only when inscriptionMode === 'new').
    public string $new_nom = '';

    public string $new_prenom = '';

    public ?string $new_sexe = null;

    public ?string $new_date_naissance = null;

    public string $new_cin = '';

    public ?string $new_niveau = null;

    /** Shown only for Arbeit / Ausbildung (see Student::NIVEAUX_AVEC_DOMAINE). */
    public ?string $new_domaine = null;

    /** Shown only for Studium (STK / DSH). */
    public ?string $new_examen_type = null;

    // Contact tab (existing students columns).
    public string $new_email = '';

    public string $new_telephone = '';

    public string $new_whatsapp = '';

    public string $new_adresse = '';

    // Parent tab (existing students columns) — same set as the Student modal.
    public ?string $new_parent_relation = null;

    public string $new_parent_nom = '';

    public ?string $new_parent_sexe = null;

    public string $new_parent_cin = '';

    public string $new_parent_telephone = '';

    public string $new_parent_whatsapp = '';

    public ?int $group_id = null;

    public string $statut = Inscription::STATUT_ACTIVE;

    public ?string $date_inscription = null;

    public ?string $date_debut = null;

    public ?string $date_fin = null;

    public string $note = '';

    /**
     * Fee lines loaded from the selected group's assigned fees
     * ("Frais disponibles"). The group carries ALL catalog fees, so every one
     * is billed (no checkbox) with the amount entered on the group — editable
     * here per student via the remise. Each: initial + remise + final.
     *
     * @var array<int, array{frais_id: ?int, nom: string, montant_initial: string, remise_pct: string, remise_montant: string, note: string, date_echeance: string}>
     */
    public array $feeLines = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Inscription::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatutFilter(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'group_id' => ['required', 'exists:groups,id'],
            'date_inscription' => ['required', 'date'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'note' => ['nullable', 'string'],
        ];

        // Status is only shown — and only changeable — when editing; new
        // registrations are always created as "Active".
        if ($this->editingId !== null) {
            $rules['statut'] = ['required', Rule::in(Inscription::STATUTS)];
        }

        // "Inscription pour": existing student → pick one; new student → inline fields.
        // (On edit the student is fixed, so require an existing one.)
        if ($this->editingId !== null || $this->inscriptionMode === 'existing') {
            $rules['student_id'] = ['required', 'exists:students,id'];
        } else {
            $rules['new_nom'] = ['required', 'string', 'max:100'];
            $rules['new_prenom'] = ['required', 'string', 'max:100'];
            $rules['new_sexe'] = ['nullable', Rule::in(Student::SEXES)];
            $rules['new_date_naissance'] = ['nullable', 'date', 'before:today'];
            $rules['new_cin'] = ['nullable', 'string', 'max:30'];
            $rules['new_niveau'] = ['nullable', Rule::in(Student::NIVEAUX)];
            // Required by — and only accepted for — their own track.
            $rules['new_domaine'] = [
                Student::niveauDemandeDomaine($this->new_niveau) ? 'required' : 'nullable',
                Rule::in(Student::DOMAINES),
                Rule::excludeIf(! Student::niveauDemandeDomaine($this->new_niveau)),
            ];
            $rules['new_examen_type'] = [
                Student::niveauDemandeExamen($this->new_niveau) ? 'required' : 'nullable',
                Rule::in(Student::EXAMEN_TYPES),
                Rule::excludeIf(! Student::niveauDemandeExamen($this->new_niveau)),
            ];
            $rules['new_email'] = ['nullable', 'email', 'max:255'];
            $rules['new_telephone'] = ['nullable', 'string', 'max:20'];
            $rules['new_whatsapp'] = ['nullable', 'string', 'max:20'];
            $rules['new_adresse'] = ['nullable', 'string', 'max:255'];
            $rules['new_parent_relation'] = ['nullable', Rule::in(Student::PARENT_RELATIONS)];
            $rules['new_parent_nom'] = ['nullable', 'string', 'max:100'];
            $rules['new_parent_sexe'] = ['nullable', Rule::in(Student::SEXES)];
            $rules['new_parent_cin'] = ['nullable', 'string', 'max:30'];
            $rules['new_parent_telephone'] = ['nullable', 'string', 'max:20'];
            $rules['new_parent_whatsapp'] = ['nullable', 'string', 'max:20'];
            $rules = [...$rules, ...$this->phonePaysRules()];
        }

        // Fee lines only apply when creating.
        if ($this->editingId === null) {
            $rules['feeLines.*.montant_initial'] = ['nullable', 'numeric', 'min:0'];
            $rules['feeLines.*.remise_pct'] = ['nullable', 'numeric', 'min:0', 'max:100'];
            $rules['feeLines.*.remise_montant'] = ['nullable', 'numeric', 'min:0'];
            $rules['feeLines.*.date_echeance'] = ['nullable', 'date'];
        }

        return $rules;
    }

    public function create(): void
    {
        $this->authorize('create', Inscription::class);
        $this->resetForm();
        $this->inscriptionMode = 'new';
        $this->date_inscription = now()->toDateString();
        $this->showModal = true;
    }

    /** Reset the student side when switching mode. */
    public function updatedInscriptionMode(): void
    {
        $this->reset([
            'student_id', 'new_nom', 'new_prenom', 'new_sexe', 'new_date_naissance', 'new_cin',
            'new_niveau', 'new_domaine', 'new_examen_type',
            'new_email', 'new_telephone', 'new_whatsapp', 'new_adresse',
            'new_parent_relation', 'new_parent_nom', 'new_parent_sexe', 'new_parent_cin',
            'new_parent_telephone', 'new_parent_whatsapp',
        ]);
        $this->resetPhonePays(...self::PHONE_FIELDS);
        $this->resetValidation();
    }

    /**
     * Switching level drops the orientation that no longer applies (same rule
     * as StudentsIndex) so a stale "DSH"/"Cuisine" can't be saved.
     */
    public function updatedNewNiveau(): void
    {
        if (! Student::niveauDemandeDomaine($this->new_niveau)) {
            $this->new_domaine = null;
        }

        if (! Student::niveauDemandeExamen($this->new_niveau)) {
            $this->new_examen_type = null;
        }

        $this->resetValidation(['new_domaine', 'new_examen_type']);
    }

    /**
     * When the group changes, load ITS assigned fees as "Frais disponibles"
     * and auto-fill the (read-only) start/end dates from the group's training
     * dates.
     */
    public function updatedGroupId(): void
    {
        $this->loadGroupFees();
    }

    private function loadGroupFees(): void
    {
        $this->feeLines = [];
        $this->date_debut = null;
        $this->date_fin = null;

        if ($this->group_id === null) {
            return;
        }

        // Only ACTIVE catalog fees are offered as "Frais disponibles".
        $group = Group::with(['frais' => fn ($q) => $q->where('statut', Frais::STATUT_ACTIF)])
            ->find($this->group_id);
        if ($group === null) {
            return;
        }

        // Inscription dates follow the group's training period (read-only).
        $this->date_debut = $group->date_debut_formation?->toDateString();
        $this->date_fin = $group->date_fin_formation?->toDateString();

        $this->feeLines = $group->frais->map(function ($frais) {
            $initial = (float) $frais->pivot->montant;

            return [
                'frais_id' => $frais->id,
                'nom' => $frais->nom,
                'montant_initial' => (string) $initial,
                'remise_pct' => '',
                'remise_montant' => '',
                'note' => '',
                // Pre-fill the due date from the group's per-fee échéance,
                // falling back to today when the group didn't set one.
                'date_echeance' => $frais->pivot->date_echeance ?: now()->toDateString(),
            ];
        })->all();
    }

    /**
     * Two-way sync between the % and DH discount on a fee line: editing one
     * recomputes the other from the line's initial amount.
     */
    public function updated(string $property): void
    {
        if (! preg_match('/^feeLines\.(\d+)\.(remise_pct|remise_montant|montant_initial)$/', $property, $m)) {
            return;
        }

        $i = (int) $m[1];
        $field = $m[2];
        $line = $this->feeLines[$i] ?? null;
        if ($line === null) {
            return;
        }

        $initial = (float) ($line['montant_initial'] ?: 0);
        if ($initial <= 0) {
            return;
        }

        if ($field === 'remise_pct') {
            // % → DH
            $pct = min(100, max(0, (float) ($line['remise_pct'] ?: 0)));
            $this->feeLines[$i]['remise_montant'] = $line['remise_pct'] === '' ? '' : (string) round($initial * $pct / 100, 2);
        } elseif ($field === 'remise_montant') {
            // DH → %
            $dh = min($initial, max(0, (float) ($line['remise_montant'] ?: 0)));
            $this->feeLines[$i]['remise_pct'] = $line['remise_montant'] === '' ? '' : (string) round($dh / $initial * 100, 2);
        } elseif ($field === 'montant_initial') {
            // Initial changed → recompute DH from the current %.
            if (($line['remise_pct'] ?? '') !== '') {
                $pct = min(100, max(0, (float) $line['remise_pct']));
                $this->feeLines[$i]['remise_montant'] = (string) round($initial * $pct / 100, 2);
            }
        }
    }

    /** Live final amount for a line after discount. */
    public function lineMontant(int $index): float
    {
        $line = $this->feeLines[$index] ?? null;
        if ($line === null) {
            return 0.0;
        }

        return InscriptionFee::computeMontant(
            (float) ($line['montant_initial'] ?: 0),
            $line['remise_pct'] !== '' ? (float) $line['remise_pct'] : null,
            $line['remise_montant'] !== '' ? (float) $line['remise_montant'] : null,
        );
    }

    public function edit(int $id): void
    {
        $inscription = Inscription::findOrFail($id);
        $this->authorize('update', $inscription);

        $this->editingId = $inscription->id;
        $this->student_id = $inscription->student_id;
        $this->group_id = $inscription->group_id;
        $this->statut = $inscription->statut;
        $this->date_inscription = $inscription->date_inscription?->toDateString();
        $this->date_debut = $inscription->date_debut?->toDateString();
        $this->date_fin = $inscription->date_fin?->toDateString();
        $this->note = (string) $inscription->note;
        $this->showModal = true;
    }

    public function save(): void
    {
        $editing = $this->editingId !== null;

        if ($editing) {
            $inscription = Inscription::findOrFail($this->editingId);
            $this->authorize('update', $inscription);
        } else {
            $this->authorize('create', Inscription::class);
        }

        $data = $this->validate();

        if ($editing) {
            $inscription->update([
                'student_id' => $data['student_id'],
                'group_id' => $data['group_id'],
                'statut' => $data['statut'],
                'date_inscription' => $data['date_inscription'],
                'date_debut' => $this->date_debut ?: null,
                'date_fin' => $this->date_fin ?: null,
                'note' => $this->note ?: null,
            ]);

            $this->closeModal();
            $this->dispatch('toast', type: 'success', message: __('Registration updated.'));

            return;
        }

        // All of the group's fee lines are billed (no checkbox).
        $selected = collect($this->feeLines);

        $creatingStudent = $this->inscriptionMode === 'new';

        // Create inscription + fee lines (with discount) + derived total.
        DB::transaction(function () use ($data, $selected, $creatingStudent): void {
            $group = Group::find($data['group_id']);

            // "Nouvel étudiant": create the student inline, scoped to the
            // group's center, then enroll them.
            if ($creatingStudent) {
                $student = Student::create([
                    'reference' => ReferenceGenerator::make('ETU', 'students'),
                    'nom' => $data['new_nom'],
                    'prenom' => $data['new_prenom'],
                    'sexe' => $this->new_sexe ?: null,
                    'date_naissance' => $this->new_date_naissance ?: null,
                    'cin' => $this->new_cin ?: null,
                    'niveau' => $this->new_niveau ?: null,
                    // Only the orientation matching the chosen track is stored.
                    'domaine' => Student::niveauDemandeDomaine($this->new_niveau) ? ($this->new_domaine ?: null) : null,
                    'examen_type' => Student::niveauDemandeExamen($this->new_niveau) ? ($this->new_examen_type ?: null) : null,
                    'email' => $this->new_email ?: null,
                    'telephone' => $this->phoneValue('new_telephone'),
                    'whatsapp' => $this->phoneValue('new_whatsapp'),
                    'adresse' => $this->new_adresse ?: null,
                    'parent_relation' => $this->new_parent_relation ?: null,
                    'parent_nom' => $this->new_parent_nom ?: null,
                    'parent_sexe' => $this->new_parent_sexe ?: null,
                    'parent_cin' => $this->new_parent_cin ?: null,
                    'parent_telephone' => $this->phoneValue('new_parent_telephone'),
                    'parent_whatsapp' => $this->phoneValue('new_parent_whatsapp'),
                    'etablissement_id' => $group?->etablissement_id,
                ]);
                $studentId = $student->id;
            } else {
                $studentId = $data['student_id'];
            }

            $lines = $selected->map(function ($l) {
                $initial = (float) ($l['montant_initial'] ?: 0);
                $final = InscriptionFee::computeMontant(
                    $initial,
                    $l['remise_pct'] !== '' ? (float) $l['remise_pct'] : null,
                    $l['remise_montant'] !== '' ? (float) $l['remise_montant'] : null,
                );

                return [
                    'frais_id' => $l['frais_id'] ?? null,
                    'nom' => $l['nom'],
                    'montant_initial' => $initial,
                    'remise_pct' => $l['remise_pct'] !== '' ? (float) $l['remise_pct'] : null,
                    'remise_montant' => $l['remise_montant'] !== '' ? (float) $l['remise_montant'] : null,
                    'montant' => $final,
                    'note' => $l['note'] ?: null,
                    'date_echeance' => $l['date_echeance'] ?: now()->toDateString(),
                    'statut' => InscriptionFee::STATUT_NON_PAYE,
                ];
            });

            $total = $lines->sum('montant');

            $inscription = Inscription::create([
                'reference' => ReferenceGenerator::make('INS', 'inscriptions'),
                'student_id' => $studentId,
                'group_id' => $data['group_id'],
                'etablissement_id' => $group?->etablissement_id,
                'annee_scolaire_id' => $group?->annee_scolaire_id ?? app(CurrentContext::class)->anneeScolaireId(),
                // Forced server-side: a new registration always starts Active,
                // whatever the client sends.
                'statut' => Inscription::STATUT_ACTIVE,
                'date_inscription' => $data['date_inscription'],
                // Dates are the group's training period (read-only in the UI) —
                // re-derived here so a tampered field can't override them.
                'date_debut' => $group?->date_debut_formation?->toDateString(),
                'date_fin' => $group?->date_fin_formation?->toDateString(),
                'montant_total' => $total > 0 ? $total : null,
                'note' => $this->note ?: null,
                'created_by' => auth()->user()->employee?->id,
            ]);

            foreach ($lines as $line) {
                $inscription->fees()->create($line);
            }
        });

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: __('Registration created.'));
    }

    public function delete(int $id): void
    {
        $inscription = Inscription::findOrFail($id);
        $this->authorize('delete', $inscription);

        // Fees with payments are blocked by the DB restrict FK on encaissements.
        try {
            $inscription->delete();
        } catch (QueryException) {
            $this->addError('delete', __('This registration has payments and cannot be deleted.'));

            return;
        }

        $this->dispatch('toast', type: 'success', message: __('Registration deleted.'));
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'inscriptionMode', 'student_id',
            'new_nom', 'new_prenom', 'new_sexe', 'new_date_naissance', 'new_cin',
            'new_niveau', 'new_domaine', 'new_examen_type',
            'new_email', 'new_telephone', 'new_whatsapp', 'new_adresse',
            'new_parent_relation', 'new_parent_nom', 'new_parent_sexe', 'new_parent_cin',
            'new_parent_telephone', 'new_parent_whatsapp',
            'group_id', 'date_inscription', 'date_debut', 'date_fin', 'note', 'feeLines',
        ]);
        $this->resetPhonePays(...self::PHONE_FIELDS);
        $this->statut = Inscription::STATUT_ACTIVE;
        $this->resetValidation();
    }

    public function render(): View
    {
        $context = app(CurrentContext::class);
        $centerAccess = app(CenterAccessService::class);

        $inscriptions = Inscription::query()
            ->with(['student', 'group', 'anneeScolaire'])
            ->withCount('fees')
            ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, auth()->user()))
            // Scope to the active year + center from the context switcher.
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->when($this->statutFilter !== '', fn ($q) => $q->where('statut', $this->statutFilter))
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($sub): void {
                    $sub->where('reference', 'like', "%{$this->search}%")
                        ->orWhereHas('student', fn ($s) => $s->where('nom', 'like', "%{$this->search}%")
                            ->orWhere('prenom', 'like', "%{$this->search}%"));
                });
            })
            ->latest()
            ->paginate(10);

        // Selectable students/groups respect center access AND the active center.
        $students = Student::query()
            ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, auth()->user()))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->orderBy('nom')->get();

        $groups = Group::query()
            ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, auth()->user()))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->orderBy('nom')->get();

        return view('livewire.backoffice.inscriptions.inscriptions-index', [
            'inscriptions' => $inscriptions,
            'students' => $students,
            'groups' => $groups,
            'statuts' => Inscription::STATUTS,
            'niveaux' => Student::NIVEAUX,
            'domaines' => Student::DOMAINES,
            'examenTypes' => Student::EXAMEN_TYPES,
            'sexes' => Student::SEXES,
            'parentRelations' => Student::PARENT_RELATIONS,
        ])->layout('components.backoffice.layout.app', ['title' => __('Registrations')]);
    }
}
