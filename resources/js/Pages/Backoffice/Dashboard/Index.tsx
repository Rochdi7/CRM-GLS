import { router } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import StatsGrid from '@/Components/Dashboard/StatsGrid';
import AnnualFraisChart from '@/Components/Dashboard/AnnualFraisChart';
import type { DashboardPageProps } from '@/Types';

/**
 * Replaces resources/views/backoffice/dashboard/index.blade.php +
 * app/Livewire/Backoffice/Dashboard/DashboardStats — same welcome banner,
 * same KPI cards, same "Add New Student" action link (a plain anchor:
 * Students is still a Livewire route — mixed-navigation rule), plus the
 * "Résumé des frais annuels" chart (GetAnnualFraisSummary).
 */
export default function DashboardIndex({ stats, annualFrais, annualFraisYear, annualFraisYears }: DashboardPageProps) {
    function changeYear(year: number) {
        router.get('/backoffice/dashboard', { year }, { preserveState: true, preserveScroll: true, replace: true });
    }

    return (
        <BackofficeLayout
            title="Tableau de bord"
            breadcrumbs={[{ label: 'Tableau de bord' }]}
            actions={
                <div className="mb-2">
                    <a href="/backoffice/students" className="btn btn-primary d-flex align-items-center me-3">
                        <i className="fa fa-square-plus me-1" />
                        Ajouter un étudiant
                    </a>
                </div>
            }
        >
            <div className="row">
                <div className="col-md-12">
                    <div className="card bg-dark">
                        <div className="overlay-img">
                            <img src="/assets/preskool/img/bg/shape-04.png" alt="" className="img-fluid shape-01" />
                            <img src="/assets/preskool/img/bg/shape-01.png" alt="" className="img-fluid shape-02" />
                            <img src="/assets/preskool/img/bg/shape-02.png" alt="" className="img-fluid shape-03" />
                            <img src="/assets/preskool/img/bg/shape-03.png" alt="" className="img-fluid shape-04" />
                        </div>
                        <div className="card-body">
                            <div className="d-flex align-items-xl-center justify-content-xl-between flex-xl-row flex-column">
                                <div className="mb-3 mb-xl-0">
                                    <div className="d-flex align-items-center flex-wrap mb-2">
                                        <h1 className="text-white me-2">Bienvenue sur GLS CRM</h1>
                                    </div>
                                    <p className="text-white">Passez une bonne journée de travail</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <StatsGrid stats={stats} />

            <div className="row">
                <div className="col-md-12">
                    <AnnualFraisChart
                        data={annualFrais}
                        year={annualFraisYear}
                        years={annualFraisYears}
                        onYearChange={changeYear}
                    />
                </div>
            </div>
        </BackofficeLayout>
    );
}
