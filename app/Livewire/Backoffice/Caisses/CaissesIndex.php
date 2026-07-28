<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Caisses;

use App\Livewire\Backoffice\Concerns\WithCenterContext;
use App\Models\Caisse;
use App\Models\Etablissement;
use App\Services\Authorization\CenterAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Cash registers (caisses) — READ-ONLY list.
 *
 * ⚠ There is no CRUD here by design: a caisse is created automatically for
 * every employee by CaisseProvisioner (EmployeeObserver), named after them and
 * opened at 0,00 DH. Nobody adds, renames or deletes a till by hand.
 *
 * `solde` is application-maintained (schema §10): it moves exclusively through
 * encaissements / depenses / remboursements / validated caisse_transfers.
 */
final class CaissesIndex extends Component
{
    use AuthorizesRequests;
    use WithCenterContext;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    /** Center filter (id as string so the Select2 empty option means "all"). */
    public string $etablissementFilter = '';

    /** Status filter (empty = all). */
    public string $statutFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Caisse::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEtablissementFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatutFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $centerAccess = app(CenterAccessService::class);

        $caisses = Caisse::query()
            ->with(['etablissement', 'responsable'])
            ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, auth()->user()))
            // Narrow to the center selected in the top-bar switcher.
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($this->etablissementFilter !== '', fn ($q) => $q->where('etablissement_id', (int) $this->etablissementFilter))
            ->when($this->statutFilter !== '', fn ($q) => $q->where('statut', $this->statutFilter))
            ->when($this->search !== '', fn ($q) => $q->where(function ($q): void {
                $q->where('nom', 'like', "%{$this->search}%")
                    ->orWhereHas('responsable', fn ($r) => $r
                        ->where('nom', 'like', "%{$this->search}%")
                        ->orWhere('prenom', 'like', "%{$this->search}%"));
            }))
            ->latest()
            ->paginate(10);

        return view('livewire.backoffice.caisses.caisses-index', [
            'caisses' => $caisses,
            'statuts' => Caisse::STATUTS,
            'etablissements' => Etablissement::query()
                ->tap(fn ($q) => $centerAccess->scopeAccessibleCenters($q, auth()->user(), 'id'))
                ->orderBy('nom_centre')->get(),
        ]);
    }
}
