import StatCard from '@/Components/Dashboard/StatCard';
import type { DashboardStats } from '@/Types';

interface StatsGridProps {
    stats: DashboardStats;
}

/**
 * Dashboard KPI cards — 8 distinct counts, no two cards covering the same
 * concept (an earlier version had both "Inscriptions actives" and
 * "Inscriptions" cards, and both "Groupes" and "Groupes actifs" — merged
 * here into one card per concept, with the other figure as the secondary
 * line instead of a whole extra card).
 */
export default function StatsGrid({ stats }: StatsGridProps) {
    return (
        <div className="row">
            <div className="d-flex align-items-center flex-wrap gap-2 mb-3">
                <span className="text-muted">Affichage des données pour :</span>
                <span className="badge badge-soft-primary">
                    <i className="fa fa-calendar me-1" />
                    {stats.anneeLabel ?? '—'}
                </span>
                <span className="badge badge-soft-info">
                    <i className="fa fa-building me-1" />
                    {stats.centreLabel ?? 'Tous les centres'}
                </span>
            </div>

            <StatCard
                icon="fa-school"
                iconBg="bg-danger-transparent"
                value={stats.studentsTotal}
                label="Étudiants"
            />

            <StatCard
                icon="fa-users"
                iconBg="bg-secondary-transparent"
                value={stats.employeesTotal}
                label="Employés"
                secondaryLabel="Actifs"
                secondaryValue={stats.employeesActive}
            />

            <StatCard
                icon="fa-chalkboard-user"
                iconBg="bg-warning-transparent"
                value={stats.enseignantsTotal}
                label="Total d'enseignants"
                secondaryLabel="Employés actifs"
                secondaryValue={stats.employeesActive}
            />

            <StatCard
                icon="fa-people-roof"
                iconBg="bg-secondary-transparent"
                value={stats.parentsTotal}
                label="Parents"
                secondaryLabel="Sur"
                secondaryValue={`${stats.studentsTotal} étudiants`}
            />

            <StatCard
                icon="fa-people-group"
                iconBg="bg-primary-transparent"
                value={stats.groupsEnFormation}
                label="Groupes actifs"
                secondaryLabel="Total groupes"
                secondaryValue={stats.groupsTotal}
            />

            <StatCard
                icon="fa-clipboard-list"
                iconBg="bg-info-transparent"
                value={stats.inscriptionsTotal}
                label="Inscriptions"
                secondaryLabel="Actives"
                secondaryValue={stats.inscriptionsActives}
            />

            <StatCard
                icon="fa-money-bill-1"
                iconBg="bg-success-transparent"
                value={`${Number(stats.paymentsMonth).toFixed(2)} MAD`}
                label="Encaissements ce mois-ci"
            />
        </div>
    );
}
