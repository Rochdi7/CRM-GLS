<?php

declare(strict_types=1);

namespace App\Domain\Reports\Actions;

use App\Domain\Reports\DTOs\DashboardStatsData;
use App\Models\Depense;
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
        $anneeRange = $context->anneeDateRange();
        $centreId = $context->etablissementId();

        // Students follow the year switcher through their inscriptions: a
        // student "belongs" to the years they hold an inscription in. A
        // student never enrolled anywhere stays visible in every year (they
        // were just created and are about to be enrolled — hiding them would
        // make a fresh student vanish from the dashboard).
        $studentsQuery = Student::query()
            ->when($centreId, fn ($q) => $q->where('etablissement_id', $centreId))
            ->when($anneeId, fn ($q) => $q->where(fn ($sub) => $sub
                ->whereHas('inscriptions', fn ($i) => $i->where('annee_scolaire_id', $anneeId))
                ->orWhereDoesntHave('inscriptions')));

        // Employees are staff — they exist regardless of academic year, so
        // they are scoped by center only (no year dimension to scope on).
        $employeesQuery = Employee::query()->when($centreId, fn ($q) => $q->where('etablissement_id', $centreId));

        // Groups / registrations are scoped by BOTH year and center.
        $groupsQuery = Group::query()
            ->when($anneeId, fn ($q) => $q->where('annee_scolaire_id', $anneeId))
            ->when($centreId, fn ($q) => $q->where('etablissement_id', $centreId));

        $inscriptionsQuery = Inscription::query()
            ->when($anneeId, fn ($q) => $q->where('annee_scolaire_id', $anneeId))
            ->when($centreId, fn ($q) => $q->where('etablissement_id', $centreId));

        // Payments this month, scoped by center via the STUDENT — the same
        // definition as the Encaissements list and EncaissementPolicy. Scoping
        // through the till hid every payment collected by an operator whose
        // till lives in another centre (legacy import), so the card read 0 DH
        // for centres whose money was booked into a visiting operator's till.
        // A plain range comparison (not whereMonth/whereYear, which wrap the
        // column in a SQL function) keeps the date index usable.
        $paymentsMonth = Encaissement::query()
            ->whereBetween('date_paiement', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->when($centreId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('etablissement_id', $centreId)))
            // Year of a payment = its fee's inscription's year; an avance
            // (no fee) follows its payment date — the exact rule the
            // Encaissements list applies, so the card and the list agree.
            ->when($anneeId, fn ($q) => $q->where(fn ($sub) => $sub
                ->whereHas('fee.inscription', fn ($i) => $i->where('annee_scolaire_id', $anneeId))
                ->orWhere(fn ($w) => $w
                    ->whereNull('inscription_fee_id')
                    ->when($anneeRange, fn ($x, $r) => $x->whereBetween('date_paiement', $r)))))
            ->sum('montant');

        // Dépenses this month — same range + till-based center scoping as
        // the encaissements figure so the two cards are directly comparable.
        // A dépense has no inscription, so its year is the year its date
        // falls in (same rule as the Dépenses list's default window).
        $depensesMonthQuery = Depense::query()
            ->whereBetween('date_depense', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->when($anneeRange, fn ($q, $r) => $q->whereBetween('date_depense', $r))
            ->when($centreId, fn ($q) => $q->whereHas('caisse', fn ($c) => $c->where('etablissement_id', $centreId)));

        // One aggregate query per table (PostgreSQL `COUNT(*) FILTER (WHERE …)`)
        // instead of one COUNT round-trip per card — the row set is scanned
        // once and every conditional total falls out of the same pass.
        $students = $studentsQuery->selectRaw(
            'COUNT(*) AS total, COUNT(*) FILTER (WHERE parent_nom IS NOT NULL) AS parents',
        )->toBase()->first();

        $employees = $employeesQuery->selectRaw(
            'COUNT(*) AS total, COUNT(*) FILTER (WHERE statut = ?) AS actifs, COUNT(*) FILTER (WHERE categorie = ?) AS enseignants',
            [Employee::STATUT_ACTIF, Employee::CATEGORIE_ENSEIGNANT],
        )->toBase()->first();

        $groups = $groupsQuery->selectRaw(
            'COUNT(*) AS total, COUNT(*) FILTER (WHERE statut = ?) AS en_formation',
            [Group::STATUT_EN_FORMATION],
        )->toBase()->first();

        $inscriptions = $inscriptionsQuery->selectRaw(
            'COUNT(*) AS total, COUNT(*) FILTER (WHERE statut = ?) AS actives, COUNT(*) FILTER (WHERE statut = ?) AS annulees, COUNT(*) FILTER (WHERE statut = ?) AS changement',
            [Inscription::STATUT_ACTIVE, Inscription::STATUT_ANNULEE, Inscription::STATUT_CHANGEMENT],
        )->toBase()->first();

        $depenses = $depensesMonthQuery->selectRaw(
            'COALESCE(SUM(montant), 0) AS total, COUNT(*) AS nb',
        )->toBase()->first();

        return new DashboardStatsData(
            studentsTotal: (int) $students->total,
            employeesTotal: (int) $employees->total,
            employeesActive: (int) $employees->actifs,
            enseignantsTotal: (int) $employees->enseignants,
            // No separate "parents" table by design (Student::parent_nom
            // lives inline, gls-crm-schema.md §5) — a "parent" is any
            // student with a guardian actually recorded.
            parentsTotal: (int) $students->parents,
            groupsTotal: (int) $groups->total,
            groupsEnFormation: (int) $groups->en_formation,
            inscriptionsTotal: (int) $inscriptions->total,
            inscriptionsActives: (int) $inscriptions->actives,
            inscriptionsAnnulees: (int) $inscriptions->annulees,
            inscriptionsChangement: (int) $inscriptions->changement,
            paymentsMonth: (float) $paymentsMonth,
            depensesMonth: (float) $depenses->total,
            depensesMonthCount: (int) $depenses->nb,
            anneeLabel: $context->anneeScolaire()?->nom,
            centreLabel: $context->isAllCenters() ? __('All centers') : $context->etablissement()?->nom_centre,
        );
    }
}
