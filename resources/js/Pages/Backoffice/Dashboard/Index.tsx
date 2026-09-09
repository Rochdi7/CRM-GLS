import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import AnnualFraisChart from '@/Components/Dashboard/AnnualFraisChart';
import SeancesAgenda from '@/Components/Dashboard/SeancesAgenda';
import SeancesCalendar, { isoDate } from '@/Components/Dashboard/SeancesCalendar';
import StatsGrid from '@/Components/Dashboard/StatsGrid';
import { t } from '@/Lib/i18n';
import type { DashboardPageProps, SharedProps } from '@/Types';

interface QuickAction {
    label: string;
    icon: string;
    href: string;
    /** Shown when the user holds ANY of these — UI convenience only (§5). */
    permissions: string[];
    /** Hue of the icon disc (0–360) — one colour per action so the row reads as five distinct shortcuts. */
    hue: number;
}

/**
 * Shortcuts to the screens an employee opens dozens of times a day. Each
 * one is a plain link to the module's list page (every CRUD module is a
 * list + modal, §11) — the permission filter mirrors backofficeNavigation.ts
 * so a user never sees a button their sidebar would not show.
 *
 * `?nouveau=1` makes the destination page open its « Ajouter » modal on
 * arrival (`Hooks/useAutoOpenCreate.ts`), so a quick action is ONE click
 * instead of two. The parameter is a UI convenience only (§5): the page
 * still draws the modal behind its own permission gate, and the server
 * still authorizes the submit.
 */
const QUICK_ACTIONS: QuickAction[] = [
    // No « Nouvel étudiant » here: the page header already carries that button.
    { label: 'New registration', icon: 'ti-clipboard-plus', href: '/backoffice/inscriptions?nouveau=1', permissions: ['registrations.view'], hue: 210 },
    { label: 'Record a payment', icon: 'ti-cash', href: '/backoffice/encaissements?nouveau=1', permissions: ['payments.view'], hue: 150 },
    { label: 'Sessions', icon: 'ti-checklist', href: '/backoffice/seances?nouveau=1', permissions: ['attendance.view'], hue: 35 },
    { label: 'Timetable', icon: 'ti-calendar-time', href: '/backoffice/emploi-du-temps?nouveau=1', permissions: ['attendance.view'], hue: 275 },
    { label: 'Groups', icon: 'ti-users-group', href: '/backoffice/groups?nouveau=1', permissions: ['groups.view'], hue: 340 },
];

/**
 * Tableau de bord — welcome hero (context + quick actions), the KPI grid
 * (StatsGrid), the séances calendar with its day agenda, and the annual
 * fees chart (GetAnnualFraisSummary). Everything follows the top-bar
 * année/centre switcher server-side; nothing here re-filters client-side.
 */
export default function DashboardIndex({ stats, annualFrais, annualFraisPeriode, seancesCalendar }: DashboardPageProps) {
    // auth.user is a shared prop (HandleInertiaRequests) — no page prop needed.
    const { auth } = usePage<SharedProps>().props;
    const [selectedDay, setSelectedDay] = useState<string>(() => isoDate(new Date()));

    const canAny = (permissions: string[]) =>
        auth.isSuperAdmin || permissions.some((p) => auth.permissions.includes(p));
    const quickActions = QUICK_ACTIONS.filter((a) => canAny(a.permissions));

    const todayLabel = new Date().toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

    function changeCalendarMonth(month: string) {
        // Partial reload — only the calendar prop is recomputed server-side;
        // the chart follows the top-bar année scolaire switcher, no query
        // parameter of its own.
        router.get(
            '/backoffice/dashboard',
            { calMonth: month },
            { preserveState: true, preserveScroll: true, replace: true, only: ['seancesCalendar'] },
        );
        // Keep the agenda on a day of the month now displayed.
        setSelectedDay((current) => (current.startsWith(month) ? current : `${month}-01`));
    }

    return (
        <BackofficeLayout
            title={t('Dashboard')}
            breadcrumbs={[{ label: t('Dashboard') }]}
            actions={
                canAny(['students.view']) && (
                    <div className="mb-2">
                        <Link href="/backoffice/students?nouveau=1" className="btn btn-primary d-flex align-items-center me-3">
                            <i className="ti ti-square-rounded-plus me-1" />
                            {t('Add a student')}
                        </Link>
                    </div>
                )
            }
        >
            <div className="row">
                <div className="col-md-12">
                    <div className="card bg-dark gls-dash-hero">
                        <div className="overlay-img">
                            <img src="/assets/crm-gls/img/bg/shape-04.png" alt="" className="img-fluid shape-01" />
                            <img src="/assets/crm-gls/img/bg/shape-01.png" alt="" className="img-fluid shape-02" />
                            <img src="/assets/crm-gls/img/bg/shape-02.png" alt="" className="img-fluid shape-03" />
                            <img src="/assets/crm-gls/img/bg/shape-03.png" alt="" className="img-fluid shape-04" />
                        </div>
                        <div className="card-body">
                            <div className="gls-dash-hero-inner">
                                <div className="gls-dash-hero-text">
                                    <p className="gls-dash-hero-date text-capitalize">
                                        <i className="ti ti-calendar-event me-1" />
                                        {todayLabel}
                                    </p>
                                    <h2 className="text-white mb-1">
                                        {t('Welcome :name to GLS CRM', { name: auth.user?.name ?? '' }).replace(/\s+/g, ' ').trim()}
                                    </h2>
                                    <p className="text-white mb-3 opacity-75">{t('Have a good day at work')}</p>
                                    <div className="d-flex align-items-center flex-wrap gap-2">
                                        <span className="gls-dash-hero-muted">{t('Showing data for')}</span>
                                        <span className="gls-dash-chip gls-dash-chip-year">
                                            <i className="ti ti-calendar me-1" />
                                            {stats.anneeLabel ?? '—'}
                                        </span>
                                        <span className="gls-dash-chip gls-dash-chip-centre">
                                            <i className="ti ti-building me-1" />
                                            {stats.centreLabel ?? t('All centers')}
                                        </span>
                                    </div>
                                </div>

                                {quickActions.length > 0 && (
                                    <div className="gls-dash-quick">
                                        <p className="gls-dash-hero-muted mb-2">{t('Quick actions')}</p>
                                        <div className="gls-dash-quick-grid">
                                            {quickActions.map((action) => (
                                                <Link
                                                    key={action.href}
                                                    href={action.href}
                                                    className="gls-dash-quick-btn"
                                                    style={{ '--qa-h': action.hue } as React.CSSProperties}
                                                >
                                                    <span className="gls-dash-quick-icon" aria-hidden="true">
                                                        <i className={`ti ${action.icon}`} />
                                                    </span>
                                                    <span>{t(action.label)}</span>
                                                </Link>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <StatsGrid stats={stats} />

            <div className="row">
                <div className="col-xl-8 d-flex">
                    <SeancesCalendar
                        data={seancesCalendar}
                        selectedDay={selectedDay}
                        onMonthChange={changeCalendarMonth}
                        onSelectDay={setSelectedDay}
                    />
                </div>
                <div className="col-xl-4 d-flex">
                    <SeancesAgenda data={seancesCalendar} selectedDay={selectedDay} />
                </div>
            </div>

            <div className="row">
                <div className="col-md-12">
                    <AnnualFraisChart data={annualFrais} periode={annualFraisPeriode} />
                </div>
            </div>
        </BackofficeLayout>
    );
}
