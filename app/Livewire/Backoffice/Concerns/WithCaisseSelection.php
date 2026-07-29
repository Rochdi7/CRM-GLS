<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Concerns;

use App\Models\Caisse;
use App\Services\Authorization\CenterAccessService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Till selection shared by the money modals (encaissements / dépenses /
 * remboursements): the scoped till list, the single-till preselection and
 * the value of the read-only « Solde » box beside the till select.
 *
 * Expects the component to expose a `?int $caisse_id` property and to use
 * WithCenterContext (scopeToActiveCenter).
 */
trait WithCaisseSelection
{
    /**
     * Tills the user may operate on: permission + active-center scoped.
     *
     * @return Collection<int, Caisse>
     */
    protected function caissesAccessibles(bool $activesOnly = false): Collection
    {
        return Caisse::query()
            ->tap(fn ($q) => app(CenterAccessService::class)->scopeAccessibleCenters($q, Auth::user()))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($activesOnly, fn ($q) => $q->where('statut', Caisse::STATUT_ACTIVE))
            ->orderBy('nom')
            ->get();
    }

    /**
     * Preselect the till so the « Solde » box shows a balance as soon as the
     * modal opens: every employee owns a till (CaisseProvisioner), so default
     * to the signed-in employee's own till when it is selectable; otherwise
     * fall back to the single accessible one.
     */
    protected function preselectCaisseParDefaut(bool $activesOnly = false): void
    {
        $caisses = $this->caissesAccessibles($activesOnly);

        $employeeId = Auth::user()?->employee?->id;
        $own = $employeeId !== null
            ? $caisses->firstWhere('responsable_employee_id', $employeeId)
            : null;

        $caisse = $own ?? ($caisses->count() === 1 ? $caisses->first() : null);

        if ($caisse !== null) {
            $this->caisse_id = (int) $caisse->getKey();
        }
    }

    /**
     * Balance shown in the read-only « Solde » box. Fetched directly by id:
     * when editing, the record's till may sit outside the scoped list.
     */
    protected function soldeCaisse(): ?float
    {
        return $this->caisse_id !== null
            ? (float) (Caisse::query()->whereKey($this->caisse_id)->value('solde') ?? 0)
            : null;
    }
}
