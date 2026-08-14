<?php

declare(strict_types=1);

namespace App\Domain\Reports\Actions;

use App\Domain\Reports\DTOs\DashboardStatsData;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Student;
use App\Services\Context\CurrentContext;

/**
 * Server-side dashboard KPI computation — extracted verbatim from the
 * Livewire App\Livewire\Backoffice\Dashboard\DashboardStats::render()
 * this replaces (docs/dashboard-livewire-to-inertia-map.md has the full
 * per-stat mapping). Query semantics, filters, and center/year scoping are
 * byte-for-byte identical; only the transport (DTO vs. Blade view data)
 * changed.
 *
 * No HTTP/request dependency — takes CurrentContext directly, testable in
 * isolation.
 */
final class GetDashboardStats
{
    public function __invoke(CurrentContext $context): DashboardStatsData
    {
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

        // Payments this month, scoped by center via the till. A plain range
        // comparison (not whereMonth/whereYear, which wrap the column in a
        // SQL function) keeps the encaissements(caisse_id, date_paiement)
        // index usable.
        $paymentsMonth = Encaissement::query()
            ->whereBetween('date_paiement', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->when($centreId, fn ($q) => $q->whereHas('caisse', fn ($c) => $c->where('etablissement_id', $centreId)))
            ->sum('montant');

        return new DashboardStatsData(
            studentsTotal: (clone $studentsQuery)->count(),
            employeesTotal: (clone $employeesQuery)->count(),
            employeesActive: (clone $employeesQuery)->where('statut', Employee::STATUT_ACTIF)->count(),
            enseignantsTotal: (clone $employeesQuery)->where('categorie', Employee::CATEGORIE_ENSEIGNANT)->count(),
            // No separate "parents" table by design (Student::parent_nom
            // lives inline, gls-crm-schema.md §5) — a "parent" is any
            // student with a guardian actually recorded.
            parentsTotal: (clone $studentsQuery)->whereNotNull('parent_nom')->count(),
            groupsTotal: (clone $groupsQuery)->count(),
            groupsEnFormation: (clone $groupsQuery)->where('statut', Group::STATUT_EN_FORMATION)->count(),
            inscriptionsTotal: (clone $inscriptionsQuery)->count(),
            inscriptionsActives: (clone $inscriptionsQuery)->where('statut', Inscription::STATUT_ACTIVE)->count(),
            paymentsMonth: (float) $paymentsMonth,
            anneeLabel: $context->anneeScolaire()?->nom,
            centreLabel: $context->isAllCenters() ? __('All centers') : $context->etablissement()?->nom_centre,
        );
    }
}
