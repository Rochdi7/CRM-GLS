import { router, usePage } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import StatsGrid from '@/Components/Dashboard/StatsGrid';
import AnnualFraisChart from '@/Components/Dashboard/AnnualFraisChart';
import SeancesCalendar from '@/Components/Dashboard/SeancesCalendar';
import { t } from '@/Lib/i18n';
import type { DashboardPageProps, SharedProps } from '@/Types';

/**
 * Replaces resources/views/backoffice/dashboard/index.blade.php +
 * app/Livewire/Backoffice/Dashboard/DashboardStats — same welcome banner,
 * same KPI cards, same "Add New Student" action link (a plain anchor:
 * Students is still a Livewire route — mixed-navigation rule), plus the
 * "Résumé des frais annuels" chart (GetAnnualFraisSummary).
 */
export default function DashboardIndex({ stats, annualFrais, annualFraisPeriode, seancesCalendar }: DashboardPageProps) {
    // auth.user is a shared prop (HandleInertiaRequests) — no page prop needed.
    const { auth } = usePage<SharedProps>().props;

    function changeCalendarMonth(month: string) {
        // Partial reload — only the calendar prop is recomputed server-side;
        // the chart follows the top-bar année scolaire switcher, no query
        // parameter of its own.
        router.get(
            '/backoffice/dashboard',
            { calMonth: month },
            { preserveState: true, preserveScroll: true, replace: true, only: ['seancesCalendar'] },
        );
    }

    return (
        <BackofficeLayout
            title="Tableau de bord"
            breadcrumbs={[{ label: 'Tableau de bord' }]}
            actions={
                <div className="mb-2">
                    <a href="/backoffice/students" className="btn btn-primary d-flex align-items-center me-3">
                        <i className="ti ti-square-rounded-plus me-1" />
                        Ajouter un étudiant
                    </a>
                </div>
            }
        >
            <div className="row">
                <div className="col-md-12">
                    <div className="card bg-dark">
                        <div className="overlay-img">
                            <img src="/assets/crm-gls/img/bg/shape-04.png" alt="" className="img-fluid shape-01" />
                            <img src="/assets/crm-gls/img/bg/shape-01.png" alt="" className="img-fluid shape-02" />
                            <img src="/assets/crm-gls/img/bg/shape-02.png" alt="" className="img-fluid shape-03" />
                            <img src="/assets/crm-gls/img/bg/shape-03.png" alt="" className="img-fluid shape-04" />
                        </div>
                        <div className="card-body">
                            <div className="d-flex align-items-xl-center justify-content-xl-between flex-xl-row flex-column">
                                <div className="mb-3 mb-xl-0">
                                    <div className="d-flex align-items-center flex-wrap mb-2">
                                        <h1 className="text-white me-2">
                                            {t('Welcome :name to GLS CRM', { name: auth.user?.name ?? '' }).replace(/\s+/g, ' ').trim()}
                                        </h1>
                                    </div>
                                    <p className="text-white">{t('Have a good day at work')}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <StatsGrid stats={stats} />

            <div className="row">
                <div className="col-md-12 d-flex">
                    <SeancesCalendar data={seancesCalendar} onMonthChange={changeCalendarMonth} />
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
