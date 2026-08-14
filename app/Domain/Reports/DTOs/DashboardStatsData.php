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
        public readonly float $paymentsMonth,
        public readonly ?string $anneeLabel,
        public readonly ?string $centreLabel,
    ) {}

    /**
     * @return array{studentsTotal: int, employeesTotal: int, employeesActive: int,
     *     enseignantsTotal: int, parentsTotal: int, groupsTotal: int, groupsEnFormation: int, inscriptionsTotal: int,
     *     inscriptionsActives: int, paymentsMonth: string, anneeLabel: ?string, centreLabel: ?string}
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
            // String, fixed 2-decimal — money is never floated to the client
            // (CLAUDE.md §17 Money rules; the source column is decimal(12,2)).
            'paymentsMonth' => number_format($this->paymentsMonth, 2, '.', ''),
            'anneeLabel' => $this->anneeLabel,
            'centreLabel' => $this->centreLabel,
        ];
    }
}
