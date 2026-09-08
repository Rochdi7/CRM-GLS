import { Link } from '@inertiajs/react';
import { statutVariant } from '@/Components/Dashboard/SeancesCalendar';
import { t } from '@/Lib/i18n';
import type { SeanceCalendarEntry, SeancesCalendarData } from '@/Types';

interface SeancesAgendaProps {
    data: SeancesCalendarData;
    /** 'YYYY-MM-DD' — the day selected in the calendar. */
    selectedDay: string;
}

const STATUT_LABEL: Record<ReturnType<typeof statutVariant>, string> = {
    success: 'Completed',
    warning: 'Planned',
    danger: 'Cancelled',
};

function longDate(dateKey: string): string {
    const [y, m, d] = dateKey.split('-').map(Number);

    return new Date(y, m - 1, d).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });
}

function sortByTime(a: SeanceCalendarEntry, b: SeanceCalendarEntry): number {
    return (a.heureDebut ?? '').localeCompare(b.heureDebut ?? '');
}

/**
 * Day agenda beside the "Résumé des séances" calendar — the séances of the
 * selected day as a readable list (time, group, teacher, status) with a
 * link to each fiche de présence. Reads the SAME `seancesCalendar` prop as
 * the calendar (one month, already loaded), so previewing a day costs no
 * request; the "open in the list" link is the only navigation.
 */
export default function SeancesAgenda({ data, selectedDay }: SeancesAgendaProps) {
    const seances = [...(data.days[selectedDay] ?? [])].sort(sortByTime);
    const counts = seances.reduce(
        (acc, s) => {
            acc[statutVariant(s.statut)] += 1;
            return acc;
        },
        { success: 0, warning: 0, danger: 0 },
    );

    return (
        <div className="card flex-fill gls-dash-card gls-agenda">
            <div className="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h4 className="card-title mb-1 text-capitalize">{longDate(selectedDay)}</h4>
                    <p className="text-muted mb-0">
                        {t(':count sessions', { count: String(seances.length) })}
                    </p>
                </div>
                {seances.length > 0 && (
                    <Link
                        href={`/backoffice/seances?dateFrom=${selectedDay}&dateTo=${selectedDay}`}
                        className="btn btn-sm btn-outline-primary"
                    >
                        {t('Open the list')}
                    </Link>
                )}
            </div>

            {seances.length > 0 && (
                <div className="gls-agenda-counts">
                    <span className="badge badge-soft-success">{counts.success} {t('Completed')}</span>
                    <span className="badge badge-soft-warning">{counts.warning} {t('Planned')}</span>
                    <span className="badge badge-soft-danger">{counts.danger} {t('Cancelled')}</span>
                </div>
            )}

            <div className="card-body gls-agenda-body">
                {seances.length === 0 ? (
                    <div className="gls-agenda-empty">
                        <span className="avatar avatar-lg rounded-circle bg-light d-flex align-items-center justify-content-center mb-2">
                            <i className="ti ti-calendar-off fs-24 text-muted" />
                        </span>
                        <p className="text-muted mb-0">{t('No session on this day.')}</p>
                    </div>
                ) : (
                    <ul className="gls-agenda-list">
                        {seances.map((seance) => {
                            const variant = statutVariant(seance.statut);

                            return (
                                <li key={seance.id}>
                                    <Link href={seance.showUrl} className={`gls-agenda-item gls-agenda-${variant}`}>
                                        <div className="gls-agenda-time">
                                            {seance.heureDebut ? (
                                                <>
                                                    <strong>{seance.heureDebut}</strong>
                                                    <small>{seance.heureFin ?? ''}</small>
                                                </>
                                            ) : (
                                                <strong>—</strong>
                                            )}
                                        </div>
                                        <div className="flex-fill overflow-hidden">
                                            <div className="gls-agenda-group text-truncate">
                                                {seance.groupNom ?? t('Deleted group')}
                                            </div>
                                            <div className="text-muted fs-13 text-truncate">
                                                <i className="ti ti-user me-1" />
                                                {seance.enseignant ?? t('No teacher')}
                                            </div>
                                        </div>
                                        <span className={`badge badge-soft-${variant} flex-shrink-0`}>
                                            {t(STATUT_LABEL[variant])}
                                        </span>
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>
        </div>
    );
}
