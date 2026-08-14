import { Link, router } from '@inertiajs/react';
import type { SeanceCalendarEntry, SeancesCalendarData } from '@/Types';

interface SeancesCalendarProps {
    data: SeancesCalendarData;
    onMonthChange: (month: string) => void;
}

const MONTHS_FR = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
];

const WEEKDAYS_FR = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

/** Local (never UTC-shifted) 'YYYY-MM-DD' key — must match the server's date_seance keys. */
function isoDate(d: Date): string {
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${m}-${day}`;
}

function dotVariant(statut: string): string {
    if (statut === 'Effectuée') return 'success';
    if (statut === 'Annulée') return 'danger';
    return 'warning'; // Prévue
}

function tooltipFor(seance: SeanceCalendarEntry): string {
    const heures = seance.heureDebut ? ` · ${seance.heureDebut} - ${seance.heureFin ?? '?'}` : '';
    const enseignant = seance.enseignant ? ` - ${seance.enseignant}` : '';
    return `[S${seance.id}] ${seance.groupNom ?? 'Groupe supprimé'}${enseignant} (${seance.statut}${heures})`;
}

/**
 * "Résumé des séances" — monthly calendar of all séances in the active
 * context (GetSeancesCalendar). Each day shows a count bubble plus one
 * status-colored dot per séance (vert Effectuée, rouge Annulée, ambre
 * Prévue); dots carry a pure-CSS tooltip (no Bootstrap JS — §3) and open
 * the séance's fiche de présence, while the day itself opens the Séances
 * list filtered to that date.
 */
export default function SeancesCalendar({ data, onMonthChange }: SeancesCalendarProps) {
    const [year, month] = data.month.split('-').map(Number);
    const firstOfMonth = new Date(year, month - 1, 1);
    const gridStart = new Date(year, month - 1, 1 - firstOfMonth.getDay());
    const todayKey = isoDate(new Date());

    function shiftMonth(delta: number) {
        const target = new Date(year, month - 1 + delta, 1);
        onMonthChange(`${target.getFullYear()}-${String(target.getMonth() + 1).padStart(2, '0')}`);
    }

    function openDay(dateKey: string, hasSeances: boolean) {
        if (!hasSeances) return;
        router.get('/backoffice/seances', { dateFrom: dateKey, dateTo: dateKey });
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
        <div className="card flex-fill">
            <div className="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h4 className="card-title mb-1">Résumé des séances</h4>
                    <p className="text-muted mb-0">Vue d&apos;ensemble des séances sur la période sélectionnée.</p>
                </div>
                <Link href="/backoffice/seances" className="link-primary fw-medium">
                    Voir les détails <i className="ti ti-chevron-right" />
                </Link>
            </div>
            <div className="card-body">
                <div className="d-flex align-items-center justify-content-between mb-3">
                    <button
                        type="button"
                        className="btn btn-sm btn-icon text-primary"
                        aria-label="Mois précédent"
                        onClick={() => shiftMonth(-1)}
                    >
                        <i className="ti ti-chevron-left fs-16" />
                    </button>
                    <h5 className="mb-0">{MONTHS_FR[month - 1]} {year}</h5>
                    <button
                        type="button"
                        className="btn btn-sm btn-icon text-primary"
                        aria-label="Mois suivant"
                        onClick={() => shiftMonth(1)}
                    >
                        <i className="ti ti-chevron-right fs-16" />
                    </button>
                </div>

                <div className="gls-seancecal">
                    {WEEKDAYS_FR.map((day) => (
                        <div key={day} className="gls-seancecal-weekday">{day}</div>
                    ))}

                    {cells.map((cell) => {
                        const seances = data.days[cell.key] ?? [];
                        const isToday = cell.key === todayKey;

                        return (
                            <div
                                key={cell.key}
                                className={[
                                    'gls-seancecal-day',
                                    cell.inMonth ? '' : 'is-outside',
                                    cell.weekend ? 'is-weekend' : '',
                                    isToday ? 'is-today' : '',
                                    seances.length > 0 ? 'has-seances' : '',
                                ].filter(Boolean).join(' ')}
                                role={seances.length > 0 ? 'button' : undefined}
                                tabIndex={seances.length > 0 ? 0 : undefined}
                                onClick={() => openDay(cell.key, seances.length > 0)}
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter') openDay(cell.key, seances.length > 0);
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
                                                className={`gls-seancecal-dot gls-seancecal-dot-${dotVariant(seance.statut)}`}
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
