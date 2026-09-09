import { useMemo, useState } from 'react';
import { t } from '@/Lib/i18n';
import type { CreneauRow } from '@/Types';

interface WeekTimelineProps {
    creneaux: CreneauRow[];
    /** Creneau::JOURS — { '1': 'Lundi', … '7': 'Dimanche' }. */
    jours: Record<string, string>;
    /** Non-empty when the page is filtered to one day: only that column is drawn. */
    jourFilter: string;
    canUpdate: boolean;
    canDelete: boolean;
    /**
     * Ouvre la FICHE du créneau (lecture seule). C'est ce que fait un clic sur
     * la carte — voir le commentaire de `activate`.
     */
    onView: (row: CreneauRow) => void;
    onEdit: (row: CreneauRow) => void;
    onDelete: (row: CreneauRow) => void;
}

/** Pixel height of one hour on the axis — every vertical position derives from it. */
const HOUR_PX = 64;
/** Ten hues spread around the wheel; a group keeps the same one on every day it appears. */
const HUES = [222, 160, 28, 340, 262, 190, 90, 8, 300, 45];

/** "HH:MM" → minutes since midnight. */
function toMinutes(heure: string): number {
    const [h, m] = heure.split(':').map(Number);
    return h * 60 + (m || 0);
}

function hueFor(groupId: number): number {
    return HUES[Math.abs(groupId) % HUES.length];
}

/** JS getDay() (0 = Sunday) → Creneau::JOURS key (1 = Lundi … 7 = Dimanche). */
function todayJour(): number {
    const d = new Date().getDay();
    return d === 0 ? 7 : d;
}

function closTitle(row: CreneauRow): string {
    return row.motifCloture === 'termine'
        ? `Fin de formation${row.dateFin ? ` le ${row.dateFin}` : ''} — ce créneau ne génère plus de séance, c'est normal.`
        : `Enseignant remplacé${row.dateFin ? ` le ${row.dateFin}` : ''} — l'emploi du temps du prof sortant a été séparé pour la paie. Le nouvel enseignant a ses propres créneaux.`;
}

/**
 * Every créneau sharing the SAME start and end on a day — the everyday case
 * in a language centre, where several groups run at 10:00–12:30 at once.
 * Drawn as ONE block for that time span with a row per group, so three
 * groups at the same hour read as a list at full width instead of three
 * slivers squeezed side by side. Side-by-side columns are kept only for
 * PARTIAL overlaps (10:00–12:00 next to 11:00–13:00), which are rare.
 */
interface Stack {
    start: number;
    end: number;
    rows: CreneauRow[];
    /** Column index inside its overlap cluster and the cluster's width. */
    col: number;
    cols: number;
}

function placeDay(rows: CreneauRow[]): Stack[] {
    const byRange = new Map<string, Stack>();
    rows.forEach((row) => {
        const start = toMinutes(row.heureDebut);
        const end = Math.max(start + 15, toMinutes(row.heureFin));
        const key = `${start}|${end}`;
        const stack = byRange.get(key) ?? { start, end, rows: [], col: 0, cols: 1 };
        stack.rows.push(row);
        byRange.set(key, stack);
    });

    // Classic calendar layout on the stacks: walk them in start order, each
    // takes the first free column of the cluster it overlaps, and every
    // stack of a cluster gets the cluster's final column count.
    const sorted = [...byRange.values()].sort((a, b) => a.start - b.start || b.end - a.end);
    sorted.forEach((s) => s.rows.sort((a, b) => a.groupNom.localeCompare(b.groupNom, 'fr')));

    let cluster: Stack[] = [];
    let clusterEnd = -1;
    let columnEnds: number[] = [];
    const closeCluster = () => {
        cluster.forEach((s) => {
            s.cols = columnEnds.length;
        });
        cluster = [];
        columnEnds = [];
    };

    sorted.forEach((s) => {
        if (s.start >= clusterEnd) {
            closeCluster();
        }
        let col = columnEnds.findIndex((e) => e <= s.start);
        if (col === -1) {
            col = columnEnds.length;
            columnEnds.push(s.end);
        } else {
            columnEnds[col] = s.end;
        }
        s.col = col;
        cluster.push(s);
        clusterEnd = Math.max(clusterEnd, s.end);
    });
    closeCluster();

    return sorted;
}

interface SlotActionsProps {
    row: CreneauRow;
    canUpdate: boolean;
    canDelete: boolean;
    onEdit: (row: CreneauRow) => void;
    onDelete: (row: CreneauRow) => void;
}

/** Edit / delete icon buttons, revealed on hover of the card or the row. */
function SlotActions({ row, canUpdate, canDelete, onEdit, onDelete }: SlotActionsProps) {
    if (!canUpdate && !canDelete) return null;

    return (
        <div className="gls-tl-ev-actions">
            {canUpdate && (
                <button
                    type="button"
                    aria-label={t('Edit')}
                    title={t('Edit')}
                    onClick={(event) => {
                        event.stopPropagation();
                        onEdit(row);
                    }}
                >
                    <i className="ti ti-edit" />
                </button>
            )}
            {canDelete && (
                <button
                    type="button"
                    className="is-danger"
                    aria-label={t('Delete')}
                    title={t('Delete')}
                    onClick={(event) => {
                        event.stopPropagation();
                        onDelete(row);
                    }}
                >
                    <i className="ti ti-trash" />
                </button>
            )}
        </div>
    );
}

/**
 * Emploi du temps as a week timeline: a continuous time axis, one column per
 * day, each créneau drawn as a card whose top and height are its real start
 * and duration (a 10:00–12:00 course is visibly twice a 17:00–18:00 one),
 * same-time créneaux stacked as rows of one block, one colour per group,
 * today's column and the current time marked. The legend highlights one
 * group across the week without touching the server-side filters (§5).
 */
export default function WeekTimeline({ creneaux, jours, jourFilter, canUpdate, canDelete, onView, onEdit, onDelete }: WeekTimelineProps) {
    const [highlight, setHighlight] = useState<number | null>(null);

    const days = useMemo(() => {
        const all = Object.entries(jours).map(([key, label]) => ({ key: Number(key), label }));
        if (jourFilter) {
            return all.filter((d) => String(d.key) === jourFilter);
        }
        const hasSunday = creneaux.some((c) => c.jourSemaine === 7);
        // Sunday is drawn only when something is scheduled on it — six empty
        // columns already cost enough width.
        return all.filter((d) => d.key !== 7 || hasSunday);
    }, [jours, jourFilter, creneaux]);

    // Axis: 08:00–21:00 by default, stretched to cover any earlier / later slot.
    const { firstHour, hours } = useMemo(() => {
        let first = 8;
        let last = 21;
        creneaux.forEach((c) => {
            first = Math.min(first, Math.floor(toMinutes(c.heureDebut) / 60));
            last = Math.max(last, Math.ceil(toMinutes(c.heureFin) / 60));
        });
        return { firstHour: first, hours: Array.from({ length: last - first }, (_, i) => first + i) };
    }, [creneaux]);

    const byDay = useMemo(() => {
        const map = new Map<number, Stack[]>();
        days.forEach((d) => {
            map.set(d.key, placeDay(creneaux.filter((c) => c.jourSemaine === d.key)));
        });
        return map;
    }, [creneaux, days]);

    // One column per day, at least 190px wide — and wider when stacks
    // partially overlap, so each side-by-side block keeps ~170px.
    const columns = useMemo(() => {
        const per = days.map((d) => {
            const maxCols = Math.max(1, ...(byDay.get(d.key) ?? []).map((s) => s.cols));
            return `minmax(${Math.max(190, maxCols * 170)}px, 1fr)`;
        });
        return `72px ${per.join(' ')}`;
    }, [days, byDay]);

    const groupes = useMemo(() => {
        const seen = new Map<number, { id: number; nom: string; niveau: string | null; count: number }>();
        creneaux.forEach((c) => {
            const g = seen.get(c.groupId) ?? { id: c.groupId, nom: c.groupNom, niveau: c.groupNiveau, count: 0 };
            g.count += 1;
            seen.set(c.groupId, g);
        });
        return [...seen.values()].sort((a, b) => a.nom.localeCompare(b.nom, 'fr'));
    }, [creneaux]);

    const enseignantsCount = useMemo(
        () => new Set(creneaux.map((c) => c.enseignantId).filter((id) => id !== null)).size,
        [creneaux],
    );

    const today = todayJour();
    const now = new Date();
    const nowMinutes = now.getHours() * 60 + now.getMinutes();
    const nowTop = ((nowMinutes - firstHour * 60) / 60) * HOUR_PX;
    const showNow = nowTop >= 0 && nowTop <= hours.length * HOUR_PX && days.some((d) => d.key === today);
    const bodyHeight = hours.length * HOUR_PX;

    const actionProps = { canUpdate, canDelete, onEdit, onDelete };
    const isDim = (row: CreneauRow) => highlight !== null && highlight !== row.groupId;
    /**
     * ⚠ Un clic sur la carte OUVRE LA FICHE, il ne modifie rien (09/09/2026).
     *
     * Il ouvrait le formulaire de modification : la carte est petite et
     * dense — le nom du groupe et la salle y sont tronqués — donc on clique
     * dessus pour LIRE, et on se retrouvait dans un formulaire prérempli
     * qu'un simple Entrée suffisait à enregistrer. Le geste le plus courant
     * de l'écran était donc le plus risqué, alors qu'il ne demandait qu'à
     * consulter.
     *
     * Modifier reste possible d'un seul geste par l'icône crayon de la carte
     * (SlotActions) ou par le menu de la vue « Paramétrage » : l'action
     * destructrice s'énonce, elle ne se déduit pas d'un clic sur du texte.
     *
     * La carte est donc activable par TOUT LE MONDE, `canUpdate` ou non —
     * consulter n'a jamais demandé le droit de modifier.
     */
    const activate = (row: CreneauRow) => (event: React.KeyboardEvent) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            onView(row);
        }
    };

    if (creneaux.length === 0) {
        return (
            <div className="gls-tl-empty">
                <span className="avatar avatar-xl rounded-circle bg-primary-transparent d-flex align-items-center justify-content-center mb-3">
                    <i className="ti ti-calendar-off fs-24" />
                </span>
                <h5 className="mb-1">{t('No slot for these filters.')}</h5>
                <p className="text-muted mb-0">{t('Adjust the filters or add a slot with the button above.')}</p>
            </div>
        );
    }

    return (
        <div className="gls-tl-wrap">
            <div className="gls-tl-summary">
                <div className="gls-tl-stats">
                    <span><strong>{creneaux.length}</strong> {t('slots')}</span>
                    <span><strong>{groupes.length}</strong> {t('groups')}</span>
                    <span><strong>{enseignantsCount}</strong> {t('teachers')}</span>
                </div>
                <div className="gls-tl-legend" role="group" aria-label={t('Highlight a group')}>
                    {groupes.map((g) => (
                        <button
                            key={g.id}
                            type="button"
                            className={`gls-tl-legend-chip${highlight === g.id ? ' is-active' : ''}${highlight !== null && highlight !== g.id ? ' is-dim' : ''}`}
                            style={{ '--ev-h': hueFor(g.id) } as React.CSSProperties}
                            aria-pressed={highlight === g.id}
                            title={t('Highlight a group')}
                            onClick={() => setHighlight((cur) => (cur === g.id ? null : g.id))}
                        >
                            <span className="gls-tl-legend-dot" aria-hidden="true" />
                            {g.nom}
                            {g.niveau && <small>{g.niveau}</small>}
                            <em>{g.count}</em>
                        </button>
                    ))}
                    {highlight !== null && (
                        <button type="button" className="gls-tl-legend-clear" onClick={() => setHighlight(null)}>
                            <i className="ti ti-x" /> {t('Show all')}
                        </button>
                    )}
                </div>
            </div>

            <div className="gls-tl">
                <div className="gls-tl-head" style={{ gridTemplateColumns: columns }}>
                    <div className="gls-tl-corner" />
                    {days.map((d) => {
                        const n = (byDay.get(d.key) ?? []).reduce((sum, s) => sum + s.rows.length, 0);
                        return (
                            <div key={d.key} className={`gls-tl-dayhead${d.key === today ? ' is-today' : ''}`}>
                                <span className="gls-tl-dayname">{d.label}</span>
                                <span className="gls-tl-daycount">
                                    {n === 0 ? t('free') : t(':count slots', { count: String(n) })}
                                </span>
                            </div>
                        );
                    })}
                </div>

                <div
                    className="gls-tl-body"
                    style={{
                        gridTemplateColumns: columns,
                        height: bodyHeight,
                        '--gls-tl-hour': `${HOUR_PX}px`,
                    } as React.CSSProperties}
                >
                    <div className="gls-tl-axis">
                        {hours.map((h) => (
                            <div key={h} className="gls-tl-hour" style={{ height: HOUR_PX }}>
                                <span>{String(h).padStart(2, '0')}:00</span>
                            </div>
                        ))}
                    </div>

                    {days.map((d) => (
                        <div key={d.key} className={`gls-tl-col${d.key === today ? ' is-today' : ''}`}>
                            {d.key === today && showNow && (
                                <div className="gls-tl-now" style={{ top: nowTop }} aria-hidden="true">
                                    <span>{`${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`}</span>
                                </div>
                            )}
                            {(byDay.get(d.key) ?? []).map((stack) => {
                                const end = Math.max(stack.start + 30, stack.end);
                                const top = ((stack.start - firstHour * 60) / 60) * HOUR_PX;
                                const height = ((end - stack.start) / 60) * HOUR_PX - 4;
                                const width = 100 / stack.cols;
                                const box = {
                                    top,
                                    left: `calc(${stack.col * width}% + 3px)`,
                                    width: `calc(${width}% - 6px)`,
                                };
                                const first = stack.rows[0];
                                const timeLabel = `${first.heureDebut} – ${first.heureFin}`;

                                if (stack.rows.length === 1) {
                                    const row = first;
                                    const isShort = end - stack.start < 75;

                                    return (
                                        <article
                                            key={row.id}
                                            className={[
                                                'gls-tl-ev',
                                                row.clos ? 'is-clos' : '',
                                                isDim(row) ? 'is-dim' : '',
                                                isShort ? 'is-short' : '',
                                                'is-editable',
                                            ].filter(Boolean).join(' ')}
                                            style={{ ...box, height, '--ev-h': hueFor(row.groupId) } as React.CSSProperties}
                                            role="button"
                                            tabIndex={0}
                                            /* Le survol garde le contenu COMPLET (le nom du groupe et la salle
                                               sont tronqués sur la carte), suivi du geste. */
                                            title={`${row.groupNom} · ${timeLabel}${row.enseignant ? ` · ${row.enseignant}` : ''}${row.salle ? ` · ${row.salle}` : ''}\n${t('View the slot')}`}
                                            onClick={() => onView(row)}
                                            onKeyDown={activate(row)}
                                        >
                                            <div className="gls-tl-ev-head">
                                                <span className="gls-tl-ev-title" title={row.groupNom}>{row.groupNom}</span>
                                                {row.groupNiveau && <span className="gls-tl-ev-level">{row.groupNiveau}</span>}
                                            </div>
                                            <div className="gls-tl-ev-time">
                                                <i className="ti ti-clock" aria-hidden="true" />
                                                {timeLabel}
                                            </div>
                                            <div className="gls-tl-ev-meta">
                                                {row.enseignant && (
                                                    <span><i className="ti ti-user" aria-hidden="true" />{row.enseignant}</span>
                                                )}
                                                {row.salle && (
                                                    <span><i className="ti ti-door" aria-hidden="true" />{row.salle}</span>
                                                )}
                                            </div>
                                            {/*
                                              Case morte : ce créneau ne génère plus de séance. Une fin de
                                              formation est NORMALE (« Terminé »), un remplacement d'enseignant
                                              aussi (« Remplacé ») — gris neutre, jamais rouge (07/09/2026).
                                            */}
                                            {row.clos && (
                                                <span className="gls-tl-ev-clos" title={closTitle(row)}>
                                                    {row.motifCloture === 'termine' ? t('Finished (slot)') : t('Replaced')}
                                                </span>
                                            )}
                                            <SlotActions row={row} {...actionProps} />
                                        </article>
                                    );
                                }

                                // Several groups at exactly the same time: one block, one row each.
                                return (
                                    <section
                                        key={`${stack.start}-${stack.end}`}
                                        className="gls-tl-stack"
                                        style={{ ...box, minHeight: height }}
                                        aria-label={`${timeLabel} — ${t(':count groups at the same time', { count: String(stack.rows.length) })}`}
                                    >
                                        <header className="gls-tl-stack-head">
                                            <span className="gls-tl-stack-time">
                                                <i className="ti ti-clock" aria-hidden="true" />
                                                {timeLabel}
                                            </span>
                                            <span
                                                className="gls-tl-stack-n"
                                                title={t(':count groups at the same time', { count: String(stack.rows.length) })}
                                            >
                                                {t(':count groups at the same time', { count: String(stack.rows.length) })}
                                            </span>
                                        </header>
                                        <div className="gls-tl-stack-rows">
                                            {stack.rows.map((row) => (
                                                <div
                                                    key={row.id}
                                                    className={[
                                                        'gls-tl-stack-row',
                                                        row.clos ? 'is-clos' : '',
                                                        isDim(row) ? 'is-dim' : '',
                                                        'is-editable',
                                                    ].filter(Boolean).join(' ')}
                                                    style={{ '--ev-h': hueFor(row.groupId) } as React.CSSProperties}
                                                    role="button"
                                                    tabIndex={0}
                                                    title={`${row.groupNom} · ${timeLabel}${row.enseignant ? ` · ${row.enseignant}` : ''}${row.salle ? ` · ${row.salle}` : ''}\n${t('View the slot')}`}
                                                    onClick={() => onView(row)}
                                                    onKeyDown={activate(row)}
                                                >
                                                    <div className="gls-tl-ev-head">
                                                        <span className="gls-tl-ev-title" title={row.groupNom}>{row.groupNom}</span>
                                                        {row.groupNiveau && <span className="gls-tl-ev-level">{row.groupNiveau}</span>}
                                                        {row.clos && (
                                                            <span className="gls-tl-ev-clos is-inline" title={closTitle(row)}>
                                                                {row.motifCloture === 'termine' ? t('Finished (slot)') : t('Replaced')}
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="gls-tl-stack-meta">
                                                        {row.enseignant && (
                                                            <span><i className="ti ti-user" aria-hidden="true" />{row.enseignant}</span>
                                                        )}
                                                        {row.salle && (
                                                            <span><i className="ti ti-door" aria-hidden="true" />{row.salle}</span>
                                                        )}
                                                    </div>
                                                    <SlotActions row={row} {...actionProps} />
                                                </div>
                                            ))}
                                        </div>
                                    </section>
                                );
                            })}
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
