<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Groups;

use App\Livewire\Backoffice\Concerns\WithCenterContext;
use App\Livewire\Backoffice\Concerns\WithPerPage;
use App\Models\Employee;
use App\Models\Frais;
use App\Models\Group;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Groups list + modal CRUD. The modal also assigns catalog fees to the group
 * (group_frais pivot with a per-group amount) — those become the
 * "Frais disponibles" when a student is enrolled into the group.
 *
 * Groups are NEVER deleted (schema §6); "Fin de formation" archives via
 * Group::archiverCommeTermine().
 */
final class GroupsIndex extends Component
{
    use AuthorizesRequests;
    use WithCenterContext;
    use WithPagination;
    use WithPerPage;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    /** Active status tab: En formation (default) / Pré-inscription / Fin de formation. */
    public string $statutFilter = Group::STATUT_EN_FORMATION;

    public bool $showModal = false;

    public ?int $editingId = null;

    // Form fields. Center + academic year are NOT form inputs: a group is
    // always created in the active working context (top-bar switchers).
    public string $nom = '';

    public ?string $niveau = null;

    public ?int $enseignant_id = null;

    public string $statut = Group::STATUT_PRE_INSCRIPTION;

    public ?string $date_debut_formation = null;

    public ?string $date_fin_formation = null;

    /**
     * Fee lines — one per active catalog fee, ALL always assigned to the group
     * (no checkbox). Each amount defaults to 0 and is edited per group; every
     * fee is saved on the group so enrollments grab the full list with the
     * amounts entered here.
     *
     * @var array<int, array{montant: string, date_echeance: string, classification: string}>
     */
    public array $fraisLignes = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Group::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatutFilter(): void
    {
        $this->resetPage();
    }

    /** Switch the status tab (En formation / Pré-inscription / Historique). */
    public function setStatutTab(string $statut): void
    {
        if (! in_array($statut, Group::STATUTS, true)) {
            return;
        }

        $this->statutFilter = $statut;
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:150'],
            'niveau' => ['required', Rule::in(Group::NIVEAUX)],
            'enseignant_id' => ['nullable', 'exists:employees,id'],
            'statut' => ['required', Rule::in([Group::STATUT_PRE_INSCRIPTION, Group::STATUT_EN_FORMATION, Group::STATUT_FIN_FORMATION])],
            'date_debut_formation' => ['nullable', 'date'],
            'date_fin_formation' => ['nullable', 'date', 'after_or_equal:date_debut_formation'],
            'fraisLignes.*.montant' => ['required', 'numeric', 'min:0'],
            'fraisLignes.*.date_echeance' => ['nullable', 'date'],
            'fraisLignes.*.classification' => ['nullable', Rule::in(Group::NIVEAUX)],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', Group::class);
        $this->resetForm();
        $this->initFraisLignes();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $group = Group::with('frais')->findOrFail($id);
        $this->authorize('update', $group);

        $this->editingId = $group->id;
        $this->nom = $group->nom;
        $this->niveau = $group->niveau;
        $this->enseignant_id = $group->enseignant_id;
        $this->statut = $group->statut;
        $this->date_debut_formation = $group->date_debut_formation?->toDateString();
        $this->date_fin_formation = $group->date_fin_formation?->toDateString();

        // Prefill the amounts + due dates of the group's assigned fees.
        $assigned = $group->frais->keyBy('id');
        $this->initFraisLignes();
        foreach ($this->fraisLignes as $fraisId => $ligne) {
            if ($assigned->has($fraisId)) {
                $this->fraisLignes[$fraisId] = [
                    'montant' => (string) $assigned[$fraisId]->pivot->montant,
                    'date_echeance' => $assigned[$fraisId]->pivot->date_echeance ?: '',
                    'classification' => $assigned[$fraisId]->pivot->classification ?: '',
                ];
            }
        }

        $this->showModal = true;
    }

    /**
     * Seed a line per active catalog fee. Every fee starts at 0 DH and stays
     * assigned to the group — the user types the real amount for each fee/month
     * per group (group_frais pivot). The catalog carries no amount: amounts
     * are always per-group.
     */
    private function initFraisLignes(): void
    {
        $this->fraisLignes = Frais::query()
            ->where('statut', Frais::STATUT_ACTIF)
            ->orderBy('nom')
            ->get()
            ->mapWithKeys(fn (Frais $f) => [
                $f->id => [
                    'montant' => '0',
                    'date_echeance' => '',
                    'classification' => '',
                ],
            ])
            ->all();
    }

    public function save(): void
    {
        $editing = $this->editingId !== null;

        if ($editing) {
            $group = Group::findOrFail($this->editingId);
            $this->authorize('update', $group);
        } else {
            $this->authorize('create', Group::class);
        }

        $data = $this->validate();

        DB::transaction(function () use ($data, $editing, &$group): void {
            $attrs = collect($data)->except('fraisLignes')->all();

            if ($editing) {
                // A raw statut change to "Fin de formation" must go through the
                // archive method — block it here (do it from the detail page).
                if ($attrs['statut'] === Group::STATUT_FIN_FORMATION
                    && $group->statut !== Group::STATUT_FIN_FORMATION) {
                    $attrs['statut'] = $group->statut;
                }
                $group->update($attrs);
            } else {
                // Center + academic year are inherited from the active working
                // context (top-bar switchers), never picked in the form.
                $context = app(CurrentContext::class);
                $attrs['etablissement_id'] = $context->etablissementId();
                $attrs['annee_scolaire_id'] = $context->anneeScolaireId();
                $group = Group::create($attrs);
            }

            // Every catalog fee is assigned to the group (no checkbox): each
            // line carries the amount entered for this group — 0 DH included —
            // so enrollments grab the full list with these amounts.
            $sync = [];
            foreach ($this->fraisLignes as $fraisId => $ligne) {
                $sync[$fraisId] = [
                    'montant' => ($ligne['montant'] ?? '') !== '' ? $ligne['montant'] : 0,
                    'date_echeance' => ($ligne['date_echeance'] ?? '') !== '' ? $ligne['date_echeance'] : null,
                    'classification' => ($ligne['classification'] ?? '') !== '' ? $ligne['classification'] : null,
                ];
            }
            $group->frais()->sync($sync);
        });

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: $editing ? __('Group updated.') : __('Group created.'));
    }

    public function closeModal(): void
    {
        $this->reset([
            'showModal', 'editingId', 'nom', 'niveau', 'enseignant_id',
            'date_debut_formation', 'date_fin_formation', 'fraisLignes',
        ]);
        $this->statut = Group::STATUT_PRE_INSCRIPTION;
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->closeModal();
    }

    /**
     * Teacher select follows the active center too (global NULL-center staff
     * stay listed). Computed: memoized per request.
     *
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function enseignants(): Collection
    {
        return Employee::query()
            ->where('categorie', Employee::CATEGORIE_ENSEIGNANT)
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->orderBy('nom')->get();
    }

    /**
     * @return Collection<int, Frais>
     */
    #[Computed]
    public function fraisCatalog(): Collection
    {
        return Frais::query()->where('statut', Frais::STATUT_ACTIF)->orderBy('nom')->get()->keyBy('id');
    }

    public function render(): View
    {
        $centerAccess = app(CenterAccessService::class);
        $context = app(CurrentContext::class);

        $groups = Group::query()
            ->with(['enseignant'])
            ->withCount(['inscriptions', 'frais'])
            ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, auth()->user()))
            // Narrow to the center selected in the top-bar switcher.
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->when($this->statutFilter !== '', fn ($q) => $q->where('statut', $this->statutFilter))
            ->when($this->search !== '', fn ($q) => $q->where('nom', 'ilike', "%{$this->search}%"))
            ->latest()
            ->paginate($this->perPage);

        // Per-status counts for the tab badges (same center/year scope, ignores search).
        $statutCounts = Group::query()
            ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, auth()->user()))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        return view('livewire.backoffice.groups.groups-index', [
            'groups' => $groups,
            'statutCounts' => $statutCounts,
            'niveaux' => Group::NIVEAUX,
            'statuts' => Group::STATUTS,
            'enseignants' => $this->enseignants(),
            'fraisCatalog' => $this->fraisCatalog(),
        ])->layout('components.backoffice.layout.app', ['title' => __('Groups')]);
    }
}
