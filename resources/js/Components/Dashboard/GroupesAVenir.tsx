import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { t } from '@/Lib/i18n';
import type { GroupeAVenir, GroupesAVenirData } from '@/Types';

interface GroupesAVenirProps {
    data: GroupesAVenirData;
}

type Tone = 'danger' | 'warning' | 'primary' | 'secondary';

/** Countdown chip — how soon the group starts. Everything is server-computed (joursAvantDebut). */
function countdown(groupe: GroupeAVenir): { label: string; tone: Tone } {
    const jours = groupe.joursAvantDebut;

    if (jours === null) return { label: t('Start date to be set'), tone: 'secondary' };
    if (jours < 0) return { label: t('Start date passed'), tone: 'danger' };
    if (jours === 0) return { label: t('Starts today'), tone: 'warning' };
    if (jours === 1) return { label: t('Starts tomorrow'), tone: 'warning' };
    if (jours <= 7) return { label: t('In :count days', { count: String(jours) }), tone: 'warning' };

    return { label: t('In :count days', { count: String(jours) }), tone: 'primary' };
}

function longDate(iso: string): string {
    const [y, m, d] = iso.split('-').map(Number);

    return new Date(y, m - 1, d).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'long' });
}

/** Filling colour: an under-filled group about to start is the one to push. */
function fillTone(pct: number): string {
    if (pct >= 100) return 'danger';
    if (pct >= 60) return 'success';
    if (pct >= 30) return 'info';

    return 'warning';
}

/**
 * « Groupes à venir » — the groups still « En inscription » in the active
 * année + centre (GetGroupesAVenir), soonest start first: countdown, teacher,
 * room, open timetable and how full each one is. Each tile opens the group.
 *
 * Every group is listed in a carousel: a native horizontal scroll-snap track
 * (touch swipe on mobile, trackpad/shift-wheel on desktop) driven by two
 * arrow buttons — no carousel library, no Bootstrap JS (§3/§6).
 */
export default function GroupesAVenir({ data }: GroupesAVenirProps) {
    const trackRef = useRef<HTMLDivElement>(null);
    const [canPrev, setCanPrev] = useState(false);
    const [canNext, setCanNext] = useState(false);

    const updateArrows = useCallback(() => {
        const el = trackRef.current;
        if (!el) return;
        setCanPrev(el.scrollLeft > 4);
        setCanNext(el.scrollLeft + el.clientWidth < el.scrollWidth - 4);
    }, []);

    useEffect(() => {
        updateArrows();
        window.addEventListener('resize', updateArrows);

        return () => window.removeEventListener('resize', updateArrows);
    }, [updateArrows, data.groupes.length]);

    function slide(direction: 1 | -1) {
        const el = trackRef.current;
        if (!el) return;
        // One "page" of visible tiles per click.
        el.scrollBy({ left: direction * el.clientWidth * 0.9, behavior: 'smooth' });
    }

    return (
        <div className="card gls-dash-card gls-upcoming">
            <div className="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div className="d-flex align-items-center gap-2">
                    <span className="gls-upcoming-icon" aria-hidden="true">
                        <i className="ti ti-rocket" />
                    </span>
                    <div>
                        <h4 className="card-title mb-1">{t('Upcoming groups')}</h4>
                        <p className="text-muted mb-0">
                            {t(':count groups open for registration', { count: String(data.total) })}
                        </p>
                    </div>
                </div>
                {data.total > 0 && (
                    <div className="d-flex align-items-center gap-2">
                        <button
                            type="button"
                            className="btn btn-sm btn-outline-light border gls-cal-nav"
                            onClick={() => slide(-1)}
                            disabled={!canPrev}
                            aria-label={t('Previous')}
                        >
                            <i className="ti ti-chevron-left" />
                        </button>
                        <button
                            type="button"
                            className="btn btn-sm btn-outline-light border gls-cal-nav"
                            onClick={() => slide(1)}
                            disabled={!canNext}
                            aria-label={t('Next')}
                        >
                            <i className="ti ti-chevron-right" />
                        </button>
                        <Link
                            href={`/backoffice/groups?statutFilter=${encodeURIComponent('En inscription')}`}
                            className="btn btn-sm btn-outline-primary"
                        >
                            {t('See all')}
                        </Link>
                    </div>
                )}
            </div>

            <div className="card-body">
                {data.groupes.length === 0 ? (
                    <div className="gls-agenda-empty">
                        <span className="avatar avatar-lg rounded-circle bg-light d-flex align-items-center justify-content-center mb-2">
                            <i className="ti ti-users-group fs-24 text-muted" />
                        </span>
                        <p className="text-muted mb-0">{t('No group open for registration.')}</p>
                    </div>
                ) : (
                    <div ref={trackRef} className="gls-upcoming-track" onScroll={updateArrows}>
                        {data.groupes.map((groupe) => {
                            const chip = countdown(groupe);
                            const pct = groupe.capacite ? Math.round((groupe.inscrits / groupe.capacite) * 100) : null;

                            return (
                                <div key={groupe.id} className="gls-upcoming-slide">
                                    <Link href={`/backoffice/groups/${groupe.id}`} className="gls-upcoming-tile">
                                        <div className="d-flex align-items-center justify-content-between gap-2 mb-2">
                                            {groupe.niveau ? (
                                                <span className="badge badge-soft-primary">{groupe.niveau}</span>
                                            ) : (
                                                <span />
                                            )}
                                            <span className={`badge badge-soft-${chip.tone} d-inline-flex align-items-center`}>
                                                <i className="ti ti-clock-hour-4 me-1" />
                                                {chip.label}
                                            </span>
                                        </div>

                                        <h5 className="gls-upcoming-name text-truncate" title={groupe.nom}>
                                            {groupe.nom}
                                        </h5>

                                        <ul className="gls-upcoming-meta">
                                            <li>
                                                <i className="ti ti-calendar-event" />
                                                <span className="text-capitalize">
                                                    {groupe.dateDebut ? longDate(groupe.dateDebut) : t('Start date to be set')}
                                                </span>
                                            </li>
                                            <li>
                                                <i className="ti ti-user" />
                                                <span className="text-truncate">{groupe.enseignant ?? t('No teacher assigned')}</span>
                                            </li>
                                            {(groupe.salle || groupe.centre) && (
                                                <li>
                                                    <i className="ti ti-door" />
                                                    <span className="text-truncate">
                                                        {[groupe.centre, groupe.salle].filter(Boolean).join(' · ')}
                                                    </span>
                                                </li>
                                            )}
                                        </ul>

                                        {groupe.creneaux.length > 0 && (
                                            <div className="gls-upcoming-slots">
                                                {groupe.creneaux.map((c, i) => (
                                                    <span key={i} className="gls-upcoming-slot">
                                                        {c.jour.slice(0, 3)} {c.heureDebut}–{c.heureFin}
                                                    </span>
                                                ))}
                                            </div>
                                        )}

                                        <div className="gls-upcoming-fill mt-auto">
                                            <div className="d-flex justify-content-between align-items-baseline mb-1">
                                                <span className="text-muted fs-12">{t('Enrolled')}</span>
                                                <span className="fw-semibold fs-13">
                                                    {groupe.inscrits}
                                                    {groupe.capacite ? <span className="text-muted fw-normal"> / {groupe.capacite}</span> : null}
                                                </span>
                                            </div>
                                            {pct !== null && (
                                                <div className="progress progress-xs" role="progressbar" aria-valuenow={pct} aria-valuemin={0} aria-valuemax={100}>
                                                    <div className={`progress-bar bg-${fillTone(pct)}`} style={{ width: `${Math.min(pct, 100)}%` }} />
                                                </div>
                                            )}
                                        </div>
                                    </Link>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </div>
    );
}
