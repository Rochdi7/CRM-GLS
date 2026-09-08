import { Link, router } from '@inertiajs/react';
import { t } from '@/Lib/i18n';
import type { SeanceCalendarEntry, SeancesCalendarData } from '@/Types';

interface SeancesCalendarProps {
    data: SeancesCalendarData;
    /** 'YYYY-MM-DD' of the day previewed in the agenda panel next to the calendar. */
    selectedDay: string;
    onMonthChange: (month: string) => void;
    onSelectDay: (dateKey: string) => void;
}

export const MONTHS_FR = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
];

const WEEKDAYS_FR = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];

/** Local (never UTC-shifted) 'YYYY-MM-DD' key — must match the server's date_seance keys. */
export function isoDate(d: Date): string {
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${m}-${day}`;
}

export function statutVariant(statut: string): 'success' | 'danger' | 'warning' {
    if (statut === 'Effectuée') return 'success';
    if (statut === 'Annulée') return 'danger';
    return 'warning'; // Prévue
}

function tooltipFor(seance: SeanceCalendarEntry): string {
    const heures = seance.heureDebut ? ` · ${seance.heureDebut} - ${seance.heureFin ?? '?'}` : '';
    const enseignant = seance.enseignant ? ` - ${seance.enseignant}` : '';
    return `[S${seance.id}] ${seance.groupNom ?? t('Deleted group')}${enseignant} (${seance.statut}${heures})`;
}

/**
 * "Résumé des séances" — monthly calendar of all séances in the active
 * context (GetSeancesCalendar). Each day shows a count bubble plus one
 * status-colored dot per séance (vert Effectuée, rouge Annulée, ambre
 * Prévue); dots carry a pure-CSS tooltip (no Bootstrap JS — §3) and open
 * the séance's fiche de présence. Clicking a day SELECTS it: the agenda
 * panel beside the calendar lists that day's séances, with a link to the
 * Séances list filtered to the date (one click to preview, a second to
 * leave the dashboard — instead of being sent away on the first click).
 */
export default function SeancesCalendar({ data, selectedDay, onMonthChange, onSelectDay }: SeancesCalendarProps) {
    const [year, month] = data.month.split('-').map(Number);
    const firstOfMonth = new Date(year, month - 1, 1);
    const gridStart = new Date(year, month - 1, 1 - firstOfMonth.getDay());
    const today = new Date();
    const todayKey = isoDate(today);
    const currentMonthKey = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
    const monthTotal = Object.values(data.days).reduce((sum, list) => sum + list.length, 0);

    function shiftMonth(delta: number) {
        const target = new Date(year, month - 1 + delta, 1);
        onMonthChange(`${target.getFullYear()}-${String(target.getMonth() + 1).padStart(2, '0')}`);
    }

    const cells = Array.from({ length: 42 }, (_, i) => {
        const date = new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + i);
        return {
            date,
            key: isoDate(date),
            inMonth: date.getMonth() === month - 1,
            weekend: date.getDay() === 0 || date.getDay() === 6,
        };
    });

    return (
        <div className="card flex-fill gls-dash-card">
            <div className="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h4 className="card-title mb-1">{t('Sessions summary')}</h4>
                    <p className="text-muted mb-0">
                        {t(':count sessions this month', { count: monthTotal.toLocaleString('fr-FR') })}
                        {' · '}
                        {t('Click a day to preview its sessions')}
                    </p>
                </div>
                <Link href="/backoffice/seances" className="link-primary fw-medium">
                    {t('View all sessions')} <i className="ti ti-chevron-right" />
                </Link>
            </div>
            <div className="card-body">
                <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div className="d-flex align-items-center gap-1">
                        <button
                            type="button"
                            className="btn btn-sm btn-outline-light border text-dark gls-cal-nav"
                            aria-label={t('Previous month')}
                            onClick={() => shiftMonth(-1)}
                        >
                            <i className="ti ti-chevron-left fs-16" />
                        </button>
                        <button
                            type="button"
                            className="btn btn-sm btn-outline-light border text-dark gls-cal-nav"
                            aria-label={t('Next month')}
                            onClick={() => shiftMonth(1)}
                        >
                            <i className="ti ti-chevron-right fs-16" />
                        </button>
                        {data.month !== currentMonthKey && (
                            <button
                                type="button"
                                className="btn btn-sm btn-soft-primary ms-1"
                                onClick={() => {
                                    onMonthChange(currentMonthKey);
                                    onSelectDay(todayKey);
                                }}
                            >
                                {t('Today')}
                            </button>
                        )}
                    </div>
                    <h5 className="mb-0">{MONTHS_FR[month - 1]} {year}</h5>
                    <div className="gls-cal-legend d-none d-sm-flex">
                        <span><i className="gls-seancecal-dot gls-seancecal-dot-success" /> {t('Completed')}</span>
                        <span><i className="gls-seancecal-dot gls-seancecal-dot-warning" /> {t('Planned')}</span>
                        <span><i className="gls-seancecal-dot gls-seancecal-dot-danger" /> {t('Cancelled')}</span>
                    </div>
                </div>

                <div className="gls-seancecal">
                    {WEEKDAYS_FR.map((day) => (
                        <div key={day} className="gls-seancecal-weekday">{day}</div>
                    ))}

                    {cells.map((cell) => {
                        const seances = data.days[cell.key] ?? [];
                        const isToday = cell.key === todayKey;
                        const isSelected = cell.key === selectedDay;

                        return (
                            <div
                                key={cell.key}
                                className={[
                                    'gls-seancecal-day',
                                    cell.inMonth ? '' : 'is-outside',
                                    cell.weekend ? 'is-weekend' : '',
                                    isToday ? 'is-today' : '',
                                    isSelected ? 'is-selected' : '',
                                    seances.length > 0 ? 'has-seances' : '',
                                ].filter(Boolean).join(' ')}
                                role="button"
                                tabIndex={0}
                                aria-pressed={isSelected}
                                onClick={() => onSelectDay(cell.key)}
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter' || event.key === ' ') {
                                        event.preventDefault();
                                        onSelectDay(cell.key);
                                    }
                                }}
                            >
                                <div className="gls-seancecal-day-top">
                                    {seances.length > 0 && (
                                        <span className="gls-seancecal-count">{seances.length}</span>
                                    )}
                                    <span className="gls-seancecal-num">{cell.date.getDate()}</span>
                                </div>
                                {seances.length > 0 && (
                                    <div className="gls-seancecal-dots">
                                        {seances.map((seance) => (
                                            <span
                                                key={seance.id}
                                                className={`gls-seancecal-dot gls-seancecal-dot-${statutVariant(seance.statut)}`}
                                                data-tooltip={tooltipFor(seance)}
                                                onClick={(event) => {
                                                    event.stopPropagation();
                                                    router.get(seance.showUrl);
                                                }}
                                            />
                                        ))}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
