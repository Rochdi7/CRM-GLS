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
                    <i className="ti ti-calendar me-1" />
                    {stats.anneeLabel ?? '—'}
                </span>
                <span className="badge badge-soft-info">
                    <i className="ti ti-building me-1" />
                    {stats.centreLabel ?? 'Tous les centres'}
                </span>
            </div>

            <StatCard
                icon="ti-school"
                iconBg="bg-danger-transparent"
                value={stats.studentsTotal}
                label="Étudiants"
            />

            <StatCard
                icon="ti-users"
                iconBg="bg-secondary-transparent"
                value={stats.employeesTotal}
                label="Employés"
                secondaryLabel="Actifs"
                secondaryValue={stats.employeesActive}
            />

            <StatCard
                icon="ti-user"
                iconBg="bg-warning-transparent"
                value={stats.enseignantsTotal}
                label="Total d'enseignants"
                secondaryLabel="Employés actifs"
                secondaryValue={stats.employeesActive}
            />

            <StatCard
                icon="ti-users"
                iconBg="bg-secondary-transparent"
                value={stats.parentsTotal}
                label="Parents"
                secondaryLabel="Sur"
                secondaryValue={`${stats.studentsTotal} étudiants`}
            />

            <StatCard
                icon="ti-users-group"
                iconBg="bg-primary-transparent"
                value={stats.groupsEnFormation}
                label="Groupes actifs"
                secondaryLabel="Total groupes"
                secondaryValue={stats.groupsTotal}
            />

            <StatCard
                icon="ti-clipboard-list"
                iconBg="bg-info-transparent"
                value={stats.inscriptionsTotal}
                label="Inscriptions"
                footer={
                    <>
                        <span className="badge badge-soft-success">
                            Actives : {stats.inscriptionsActives}
                        </span>
                        <span className="badge badge-soft-danger">
                            Annulées : {stats.inscriptionsAnnulees}
                        </span>
                        <span className="badge badge-soft-info">
                            Changement : {stats.inscriptionsChangement}
                        </span>
                    </>
                }
            />

            <StatCard
                icon="ti-cash-banknote"
                iconBg="bg-success-transparent"
                value={`${Number(stats.paymentsMonth).toFixed(2)} MAD`}
                label="Encaissements ce mois-ci"
            />

            <StatCard
                icon="ti-cash-banknote-off"
                iconBg="bg-danger-transparent"
                value={`${Number(stats.depensesMonth).toFixed(2)} MAD`}
                label="Dépenses ce mois-ci"
                secondaryLabel="Nombre de dépenses"
                secondaryValue={stats.depensesMonthCount}
            />
        </div>
    );
}
