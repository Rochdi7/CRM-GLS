import { usePage } from '@inertiajs/react';
import CountUp from '@/Components/Dashboard/CountUp';
import StatCard from '@/Components/Dashboard/StatCard';
import { t } from '@/Lib/i18n';
import { formatMontant, formatMontantCompact } from '@/Lib/money';
import type { DashboardStats, SharedProps } from '@/Types';

interface StatsGridProps {
    stats: DashboardStats;
}

/**
 * Dashboard KPI cards in two rows — « Vue d'ensemble » (people, groups,
 * registrations) and « Finances du mois » (money in, money out, net).
 * One card per concept; a related figure rides as the secondary line
 * instead of a whole extra card (an earlier version had both "Inscriptions
 * actives" and "Inscriptions", and both "Groupes" and "Groupes actifs").
 *
 * Every card links to the module its figure comes from when the user holds
 * that module's `*.view` permission — UI convenience only (§5): the count
 * itself was computed server-side and is shown either way.
 */
export default function StatsGrid({ stats }: StatsGridProps) {
    const { auth } = usePage<SharedProps>().props;

    const linkIf = (permission: string, href: string): string | undefined =>
        auth.isSuperAdmin || auth.permissions.includes(permission) ? href : undefined;

    // Both figures are server totals over the SAME month + centre + année
    // window (GetDashboardStats keeps the two queries directly comparable),
    // so their difference is a plain subtraction of two authoritative
    // amounts, not a client-side aggregation of rows.
    const net = Number(stats.paymentsMonth) - Number(stats.depensesMonth);
    const monthLabel = new Date().toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });

    return (
        <>
            <div className="gls-dash-section-title">
                <h5 className="mb-0">{t('Overview')}</h5>
            </div>
            <div className="row">
                {/* « Actifs » counts PEOPLE with an Active inscription in the
                    année the switcher shows, so it is normally SMALLER than
                    the total above it: a student carries no année (§11) and
                    stays counted in the total for every year, while their
                    dossier is active in one. The two are deliberately not
                    the same figure — see GetDashboardStats. */}
                <StatCard
                    icon="ti-school"
                    variant="danger"
                    value={<CountUp value={stats.studentsTotal} />}
                    label={t('Students')}
                    footer={
                        <>
                            <span className="badge badge-soft-success">
                                {t('With an active registration')} : {stats.studentsActifs.toLocaleString('fr-FR')}
                            </span>
                            <span className="badge badge-soft-info">
                                {t('Parents on file')} : {stats.parentsTotal.toLocaleString('fr-FR')}
                            </span>
                        </>
                    }
                    href={linkIf('students.view', '/backoffice/students')}
                />

                <StatCard
                    icon="ti-clipboard-list"
                    variant="info"
                    value={<CountUp value={stats.inscriptionsTotal} />}
                    label={t('Registrations')}
                    footer={
                        <>
                            <span className="badge badge-soft-success">
                                {t('Active (registrations)')} : {stats.inscriptionsActives}
                            </span>
                            <span className="badge badge-soft-danger">
                                {t('Cancelled registrations')} : {stats.inscriptionsAnnulees}
                            </span>
                            <span className="badge badge-soft-info">
                                {t('Group changes')} : {stats.inscriptionsChangement}
                            </span>
                        </>
                    }
                    href={linkIf('registrations.view', '/backoffice/inscriptions')}
                />

                <StatCard
                    icon="ti-users-group"
                    variant="primary"
                    value={<CountUp value={stats.groupsTotal} />}
                    label={t('Groups')}
                    footer={
                        <>
                            <span className="badge badge-soft-success">
                                {t('In training')} : {stats.groupsEnFormation}
                            </span>
                            <span className="badge badge-soft-warning">
                                {t('Enrolling')} : {stats.groupsEnInscription}
                            </span>
                            <span className="badge badge-soft-dark">
                                {t('Finished')} : {stats.groupsTermines}
                            </span>
                            <span className="badge badge-soft-danger">
                                {t('Cancelled (groups)')} : {stats.groupsAnnules}
                            </span>
                        </>
                    }
                    href={linkIf('groups.view', '/backoffice/groups')}
                />

                <StatCard
                    icon="ti-user"
                    variant="warning"
                    value={<CountUp value={stats.enseignantsTotal} />}
                    label={t('Teachers')}
                    secondaryLabel={t('Employees')}
                    secondaryValue={stats.employeesTotal}
                    href={linkIf('employees.view', '/backoffice/employees')}
                />
            </div>

            <div className="gls-dash-section-title">
                <h5 className="mb-0">{t("This month's finances")}</h5>
                <span className="badge badge-soft-primary text-capitalize">{monthLabel}</span>
            </div>
            <div className="row">
                <StatCard
                    icon="ti-users"
                    variant="secondary"
                    value={<CountUp value={stats.employeesActive} />}
                    label={t('Active employees')}
                    secondaryLabel={t('Employees')}
                    secondaryValue={stats.employeesTotal}
                    href={linkIf('employees.view', '/backoffice/employees')}
                />

                <StatCard
                    icon="ti-cash-banknote"
                    variant="success"
                    value={<CountUp value={stats.paymentsMonth} format={formatMontantCompact} />}
                    valueTitle={`${formatMontant(stats.paymentsMonth)} MAD`}
                    unit="MAD"
                    label={t('Payments this month')}
                    href={linkIf('payments.view', '/backoffice/encaissements')}
                />

                <StatCard
                    icon="ti-cash-banknote-off"
                    variant="danger"
                    value={<CountUp value={stats.depensesMonth} format={formatMontantCompact} />}
                    valueTitle={`${formatMontant(stats.depensesMonth)} MAD`}
                    unit="MAD"
                    label={t('Expenses this month')}
                    secondaryLabel={t('Number of expenses')}
                    secondaryValue={stats.depensesMonthCount}
                    href={linkIf('expenses.view', '/backoffice/depenses')}
                />

                <StatCard
                    icon={net >= 0 ? 'ti-trending-up' : 'ti-trending-down'}
                    variant={net >= 0 ? 'teal' : 'warning'}
                    value={<CountUp value={net} format={formatMontantCompact} />}
                    valueTitle={`${formatMontant(net)} MAD`}
                    unit="MAD"
                    label={t('Net balance this month')}
                    secondaryLabel={t('Calculation')}
                    secondaryValue={t('Payments minus expenses')}
                />
            </div>
        </>
    );
}
