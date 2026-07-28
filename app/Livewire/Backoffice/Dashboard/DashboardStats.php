<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Dashboard;

use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Student;
use App\Services\Context\CurrentContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Dashboard KPI cards, scoped to the active context (selected academic year +
 * center). Re-renders on `context-changed` (dispatched by the top-bar
 * switchers), so switching year/center updates the figures live.
 */
final class DashboardStats extends Component
{
    /** Refresh when the top-bar context switcher changes the year/center. */
    #[On('context-changed')]
    public function refresh(): void
    {
        // Method body intentionally empty — the attribute triggers a re-render.
    }

    public function render(): View
    {
        $context = app(CurrentContext::class);
        $anneeId = $context->anneeScolaireId();
        $centreId = $context->etablissementId();

        // Students / Employees are scoped by center only (no academic year FK).
        $studentsQuery = Student::query()->when($centreId, fn ($q) => $q->where('etablissement_id', $centreId));
        $employeesQuery = Employee::query()->when($centreId, fn ($q) => $q->where('etablissement_id', $centreId));

        // Groups / registrations are scoped by BOTH year and center.
        $groupsQuery = Group::query()
            ->when($anneeId, fn ($q) => $q->where('annee_scolaire_id', $anneeId))
            ->when($centreId, fn ($q) => $q->where('etablissement_id', $centreId));

        $inscriptionsQuery = Inscription::query()
            ->when($anneeId, fn ($q) => $q->where('annee_scolaire_id', $anneeId))
            ->when($centreId, fn ($q) => $q->where('etablissement_id', $centreId));

        // Payments this month, scoped by center via the till.
        $paymentsMonth = Encaissement::query()
            ->whereMonth('date_paiement', now()->month)
            ->whereYear('date_paiement', now()->year)
            ->when($centreId, fn ($q) => $q->whereHas('caisse', fn ($c) => $c->where('etablissement_id', $centreId)))
            ->sum('montant');

        return view('livewire.backoffice.dashboard.dashboard-stats', [
            'studentsTotal' => (clone $studentsQuery)->count(),
            'employeesTotal' => (clone $employeesQuery)->count(),
            'employeesActive' => (clone $employeesQuery)->where('statut', Employee::STATUT_ACTIF)->count(),
            'groupsTotal' => (clone $groupsQuery)->count(),
            'groupsEnFormation' => (clone $groupsQuery)->where('statut', Group::STATUT_EN_FORMATION)->count(),
            'inscriptionsTotal' => (clone $inscriptionsQuery)->count(),
            'inscriptionsActives' => (clone $inscriptionsQuery)->where('statut', Inscription::STATUT_ACTIVE)->count(),
            'paymentsMonth' => (float) $paymentsMonth,
            'anneeLabel' => $context->anneeScolaire()?->nom,
            'centreLabel' => $context->isAllCenters() ? __('All centers') : $context->etablissement()?->nom_centre,
        ]);
    }
}
