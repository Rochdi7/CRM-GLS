import StatCard from '@/Components/Dashboard/StatCard';
import type { DashboardStats } from '@/Types';

interface StatsGridProps {
    stats: DashboardStats;
}

/**
 * Renders exactly the 4 cards the Livewire dashboard-stats.blade.php shows
 * (Students, Employees, Groups, Payments-this-month) — same figures, same
 * secondary metrics, same order. No new cards added
 * (docs/dashboard-livewire-to-inertia-map.md).
 */
export default function StatsGrid({ stats }: StatsGridProps) {
    return (
        <div className="row">
            <div className="d-flex align-items-center flex-wrap gap-2 mb-3">
                <span className="text-muted">Affichage des données pour :</span>
                <span className="badge badge-soft-primary">
                    <i className="ti ti-calendar me-1" />
                    {stats.anneeLabel ?? '—'}
                </span>
                <span className="badge badge-soft-info">
                    <i className="ti ti-building me-1" />
                    {stats.centreLabel ?? 'Tous les centres'}
                </span>
            </div>

            <StatCard
                icon="/assets/preskool/img/icons/student.svg"
                iconBg="bg-danger-transparent"
                value={stats.studentsTotal}
                label="Étudiants"
            />

            <StatCard
                icon="/assets/preskool/img/icons/teacher.svg"
                iconBg="bg-secondary-transparent"
                value={stats.employeesTotal}
                label="Employés"
                secondaryLabel="Actifs"
                secondaryValue={stats.employeesActive}
            />

            <StatCard
                icon="/assets/preskool/img/icons/staff.svg"
                iconBg="bg-warning-transparent"
                value={stats.groupsTotal}
                label="Groupes"
                secondaryLabel="En formation"
                secondaryValue={stats.groupsEnFormation}
            />

            <StatCard
                icon="/assets/preskool/img/icons/subject.svg"
                iconBg="bg-success-transparent"
                value={stats.inscriptionsActives}
                label="Inscriptions actives"
                secondaryLabel="Ce mois-ci"
                secondaryValue={`${Number(stats.paymentsMonth).toFixed(2)} MAD`}
            />
        </div>
    );
}
