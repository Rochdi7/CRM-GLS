import { Link, router } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import EmptyState from '@/Components/Shared/EmptyState';
import Pagination from '@/Components/Tables/Pagination';
import SelectField from '@/Components/Forms/SelectField';
import DateField from '@/Components/Forms/DateField';
import StatusBadge from '@/Components/Details/StatusBadge';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { t } from '@/Lib/i18n';
import type { MesSeancesPageProps, SeanceRow } from '@/Types';

const STATUT_TABS: Array<{ key: string; icon: string; label: string }> = [
    { key: '', icon: 'ti-list', label: 'All sessions' },
    { key: 'Prévue', icon: 'ti-clock', label: 'To do' },
    { key: 'Effectuée', icon: 'ti-circle-check', label: 'Completed' },
    { key: 'Annulée', icon: 'ti-x', label: 'Cancelled' },
];

function statutVariant(statut: string): 'success' | 'danger' | 'warning' {
    if (statut === 'Effectuée') return 'success';
    if (statut === 'Annulée') return 'danger';
    return 'warning';
}

function dayHeading(iso: string): string {
    const label = new Date(`${iso}T00:00:00`).toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
    return label.charAt(0).toUpperCase() + label.slice(1);
}

/** Consecutive rows of the same day, in the server's order (à faire first). */
function groupByDay(rows: SeanceRow[]): Array<{ date: string; rows: SeanceRow[] }> {
    const days: Array<{ date: string; rows: SeanceRow[] }> = [];
    for (const row of rows) {
        const last = days[days.length - 1];
        if (last && last.date === row.dateSeance) {
            last.rows.push(row);
        } else {
            days.push({ date: row.dateSeance, rows: [row] });
        }
    }
    return days;
}

/**
 * « Mes séances » — the séances list as an ENSEIGNANT sees it (portée
 * `groups.view-own`, PorteeEnseignant): cards grouped by day, one action —
 * take (or re-open) the roll call. No row menu: cancelling, editing and
 * deleting a séance belong to the office (SeancePolicy@cancel needs
 * `attendance.update`, which the teacher preset does not carry).
 */
export default function MesSeances({ seances, filters, groupOptions, today }: MesSeancesPageProps) {
    const isLoading = useInertiaLoading();

    function reload(next: Partial<typeof filters>) {
        router.get(
            '/backoffice/seances',
            { ...filters, ...next, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const days = groupByDay(seances.data);

    return (
        <BackofficeLayout
            title={t('My sessions')}
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: t('My sessions') }]}
        >
            <ul className="nav nav-tabs mb-3" role="tablist">
                <li className="nav-item" role="presentation">
                    <button type="button" className="nav-link active fw-medium" role="tab" aria-selected="true">
                        <i className="ti ti-calendar-check me-2" />
                        {t('Sessions')}
                    </button>
                </li>
                <li className="nav-item" role="presentation">
                    <Link href="/backoffice/seances/saisir-absence" className="nav-link fw-medium" role="tab" aria-selected="false">
                        <i className="ti ti-checklist me-2" />
                        {t('Roll call')}
                    </Link>
                </li>
                <li className="nav-item" role="presentation">
                    <Link href="/backoffice/seances/absence-par-groupe" className="nav-link fw-medium" role="tab" aria-selected="false">
                        <i className="ti ti-table me-2" />
                        {t('Absences by group')}
                    </Link>
                </li>
            </ul>

            <div className="card">
                <div className="card-body pb-1">
                    <ul className="nav nav-tabs nav-tabs-solid nav-tabs-rounded-fill mb-2" role="tablist">
                        {STATUT_TABS.map((tab) => (
                            <li className="me-2 mb-2" role="presentation" key={tab.key || 'all'}>
                                <button
                                    type="button"
                                    className={`nav-link rounded${filters.statutFilter === tab.key ? ' active' : ''}`}
                                    onClick={() => reload({ statutFilter: tab.key })}
                                >
                                    <i className={`ti ${tab.icon} me-1`} />
                                    {t(tab.label)}
                                </button>
                            </li>
                        ))}
                    </ul>
                    <div className="row g-2 mb-2">
                        <div className="col-md-4">
                            <SelectField
                                id="mes-seances-groupe"
                                label={t('Group')}
                                options={groupOptions}
                                placeholder={t('All my groups')}
                                value={filters.groupFilter}
                                onChange={(event) => reload({ groupFilter: event.target.value })}
                            />
                        </div>
                        <div className="col-md-4 col-6">
                            <DateField
                                id="mes-seances-du"
                                label={t('Start date')}
                                value={filters.dateFrom}
                                onChange={(event) => reload({ dateFrom: event.target.value })}
                            />
                        </div>
                        <div className="col-md-4 col-6">
                            <DateField
                                id="mes-seances-au"
                                label={t('End date')}
                                value={filters.dateTo}
                                onChange={(event) => reload({ dateTo: event.target.value })}
                            />
                        </div>
                    </div>
                </div>
            </div>

            {days.length === 0 ? (
                <div className="card">
                    <div className="card-body">
                        <EmptyState title={t('No session')} icon="ti ti-calendar-off" />
                    </div>
                </div>
            ) : (
                <div className={isLoading ? 'opacity-50' : undefined}>
                    {days.map((day) => (
                        <div key={day.date} className="mb-2">
                            <h6 className="d-flex align-items-center mb-3">
                                <i className="ti ti-calendar-event me-2 text-primary" />
                                {dayHeading(day.date)}
                                {day.date === today && <span className="badge bg-primary ms-2">{t('Today')}</span>}
                            </h6>
                            <div className="row">
                                {day.rows.map((seance) => (
                                    <div className="col-xxl-4 col-md-6 d-flex" key={seance.id}>
                                        <div className={`card flex-fill${day.date === today ? ' border-primary' : ''}`}>
                                            <div className="card-body d-flex flex-column">
                                                <div className="d-flex align-items-start justify-content-between gap-2 mb-3">
                                                    <div className="d-flex align-items-center overflow-hidden">
                                                        <span className="avatar avatar-lg rounded bg-primary-transparent text-primary me-2 flex-shrink-0 d-inline-flex flex-column align-items-center justify-content-center">
                                                            <i className="ti ti-clock fs-18" />
                                                        </span>
                                                        <div className="overflow-hidden">
                                                            <h6 className="mb-1 text-truncate text-uppercase">{seance.groupNom ?? '—'}</h6>
                                                            <span className="fs-13 text-muted">
                                                                {seance.heureDebut ?? '—'}
                                                                {seance.heureFin ? ` – ${seance.heureFin}` : ''}
                                                            </span>
                                                            {seance.groupNiveau && (
                                                                <span className="badge badge-soft-info ms-2">{seance.groupNiveau}</span>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <StatusBadge label={seance.statut} variant={statutVariant(seance.statut)} dot />
                                                </div>

                                                <div className="d-flex gap-2 mb-3">
                                                    <span className="badge badge-soft-success fs-13 fw-normal">
                                                        <i className="ti ti-user-check me-1" />
                                                        {seance.presentsCount} {t('present')}
                                                    </span>
                                                    <span className="badge badge-soft-danger fs-13 fw-normal">
                                                        <i className="ti ti-user-x me-1" />
                                                        {seance.absentsCount} {t('absent')}
                                                    </span>
                                                </div>

                                                <div className="mt-auto pt-2 border-top">
                                                    {seance.statut === 'Annulée' ? (
                                                        <span className="fs-13 text-muted">{t('This session was cancelled.')}</span>
                                                    ) : (
                                                        <Link
                                                            href={seance.showUrl}
                                                            className={`btn btn-sm d-inline-flex align-items-center ${
                                                                seance.statut === 'Effectuée' ? 'btn-light' : 'btn-primary'
                                                            }`}
                                                        >
                                                            <i className={`ti ${seance.statut === 'Effectuée' ? 'ti-eye' : 'ti-checklist'} me-1`} />
                                                            {seance.statut === 'Effectuée' ? t('View roll call') : t('Roll call')}
                                                        </Link>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            <Pagination paginator={seances} />
        </BackofficeLayout>
    );
}
