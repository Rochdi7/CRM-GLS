<?php

declare(strict_types=1);

namespace App\Domain\Reports\DTOs;

/**
 * Typed result of GetDashboardStats — mirrors exactly the values the
 * Livewire DashboardStats component passed to its view, no more, no less.
 */
final class DashboardStatsData
{
    public function __construct(
        public readonly int $studentsTotal,
        public readonly int $employeesTotal,
        public readonly int $employeesActive,
        public readonly int $enseignantsTotal,
        public readonly int $parentsTotal,
        public readonly int $groupsTotal,
        public readonly int $groupsEnFormation,
        public readonly int $inscriptionsTotal,
        public readonly int $inscriptionsActives,
        public readonly int $inscriptionsAnnulees,
        public readonly int $inscriptionsChangement,
        public readonly float $paymentsMonth,
        public readonly float $depensesMonth,
        public readonly int $depensesMonthCount,
        public readonly ?string $anneeLabel,
        public readonly ?string $centreLabel,
    ) {}

    /**
     * @return array{studentsTotal: int, employeesTotal: int, employeesActive: int,
     *     enseignantsTotal: int, parentsTotal: int, groupsTotal: int, groupsEnFormation: int, inscriptionsTotal: int,
     *     inscriptionsActives: int, inscriptionsAnnulees: int, inscriptionsChangement: int, paymentsMonth: string,
     *     depensesMonth: string, depensesMonthCount: int, anneeLabel: ?string, centreLabel: ?string}
     */
    public function toArray(): array
    {
        return [
            'studentsTotal' => $this->studentsTotal,
            'employeesTotal' => $this->employeesTotal,
            'employeesActive' => $this->employeesActive,
            'enseignantsTotal' => $this->enseignantsTotal,
            'parentsTotal' => $this->parentsTotal,
            'groupsTotal' => $this->groupsTotal,
            'groupsEnFormation' => $this->groupsEnFormation,
            'inscriptionsTotal' => $this->inscriptionsTotal,
            'inscriptionsActives' => $this->inscriptionsActives,
            'inscriptionsAnnulees' => $this->inscriptionsAnnulees,
            'inscriptionsChangement' => $this->inscriptionsChangement,
            // String, fixed 2-decimal — money is never floated to the client
            // (CLAUDE.md §17 Money rules; the source column is decimal(12,2)).
            'paymentsMonth' => number_format($this->paymentsMonth, 2, '.', ''),
            'depensesMonth' => number_format($this->depensesMonth, 2, '.', ''),
            'depensesMonthCount' => $this->depensesMonthCount,
            'anneeLabel' => $this->anneeLabel,
            'centreLabel' => $this->centreLabel,
        ];
    }
}
