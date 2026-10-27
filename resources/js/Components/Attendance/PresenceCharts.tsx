import { useEffect, useRef, useState } from 'react';

export interface PresenceParDate {
    date: string;
    presents: number;
    absents: number;
}

export interface PresenceParEtudiant {
    id: number;
    nom: string;
    actif: boolean;
    presents: number;
    absents: number;
}

/**
 * Two series only — « Retard » was removed from the system (25/09/2026); the
 * server already folds its legacy rows into « Présence ». Colours are the
 * PreSkool success / danger hues already validated for both themes on
 * `.gls-frais-chart` (app.css `--gls-bar-3` / `--gls-bar-7`); the legend, the
 * tooltip labels and the value labels carry the identity too, never the
 * colour alone.
 */
const SERIES = [
    { key: 'presents', label: 'Présence', color: 'var(--gls-bar-3)' },
    { key: 'absents', label: 'Absence', color: 'var(--gls-bar-7)' },
] as const;

const FALLBACK_WIDTH = 1000;
const PAD_LEFT = 44;
const PAD_RIGHT = 24;
const PAD_TOP = 24;
const gridLines = [0, 0.25, 0.5, 0.75, 1];

function niceMax(max: number): number {
    if (max <= 4) {
        return 4;
    }
    const magnitude = 10 ** Math.floor(Math.log10(max));
    const normalized = max / magnitude;
    const step = normalized <= 1 ? 1 : normalized <= 2 ? 2 : normalized <= 4 ? 4 : normalized <= 8 ? 8 : 10;

    return step * magnitude;
}

function formatDate(date: string): string {
    const [year, month, day] = date.split('-');

    return year && month && day ? `${day}/${month}/${year}` : date;
}

/** The SVG is drawn at the container's real pixel width (see AnnualFraisChart). */
function useContainerWidth() {
    const ref = useRef<HTMLDivElement | null>(null);
    const [width, setWidth] = useState(FALLBACK_WIDTH);

    useEffect(() => {
        const el = ref.current;
        if (!el || typeof ResizeObserver === 'undefined') return;

        const observer = new ResizeObserver((entries) => {
            const w = Math.round(entries[0]?.contentRect.width ?? 0);
            if (w > 0) setWidth(w);
        });
        observer.observe(el);

        return () => observer.disconnect();
    }, []);

    return { ref, width };
}

function Legend() {
    return (
        <div className="gls-frais-legend justify-content-center">
            {SERIES.map((s) => (
                <span key={s.key} className="gls-frais-legend-pill" style={{ cursor: 'default' }}>
                    <span className="gls-frais-legend-dot" style={{ backgroundColor: s.color }} aria-hidden="true" />
                    {s.label}
                </span>
            ))}
        </div>
    );
}

function YAxis({ maxValue, plotHeight, right }: { maxValue: number; plotHeight: number; right: number }) {
    return (
        <>
            {gridLines.map((fraction) => {
                const y = PAD_TOP + plotHeight - fraction * plotHeight;
                return (
                    <g key={fraction}>
                        <line x1={PAD_LEFT} y1={y} x2={right} y2={y} stroke="var(--gls-chart-grid)" strokeWidth={1} />
                        <text x={PAD_LEFT - 8} y={y + 4} textAnchor="end" fontSize={11} fill="var(--gls-chart-muted)">
                            {Math.round(maxValue * fraction)}
                        </text>
                    </g>
                );
            })}
        </>
    );
}

function TooltipRows({ presents, absents }: { presents: number; absents: number }) {
    const total = presents + absents;

    return (
        <>
            {SERIES.map((s) => {
                const value = s.key === 'presents' ? presents : absents;
                return (
                    <div className="gls-frais-tooltip-row" key={s.key}>
                        <span className="gls-frais-tooltip-dot" style={{ backgroundColor: s.color }} />
                        <span className="gls-frais-tooltip-label">{s.label} :</span>
                        <span className="gls-frais-tooltip-value">
                            {value}
                            {total > 0 && <small>{Math.round((value / total) * 100)} %</small>}
                        </span>
                    </div>
                );
            })}
        </>
    );
}

/**
 * « Statistiques de présence » — présences / absences per séance date, one
 * line per series with a marker on every date and a shared crosshair tooltip.
 */
export function PresenceParDateChart({ data }: { data: PresenceParDate[] }) {
    const { ref, width } = useContainerWidth();
    const [hoverIndex, setHoverIndex] = useState<number | null>(null);

    const height = width < 640 ? 260 : 340;
    const padBottom = 32;
    const plotWidth = width - PAD_LEFT - PAD_RIGHT;
    const plotHeight = height - PAD_TOP - padBottom;
    const count = data.length;
    const maxValue = niceMax(Math.max(0, ...data.flatMap((d) => [d.presents, d.absents])));
    // A single date sits in the middle instead of glued to the y-axis.
    const xFor = (i: number) => PAD_LEFT + (count <= 1 ? plotWidth / 2 : (i / (count - 1)) * plotWidth);
    const yFor = (v: number) => PAD_TOP + plotHeight - (v / maxValue) * plotHeight;
    // ~84px per "dd/mm/yyyy" label at 11px.
    const labelStep = Math.max(1, Math.ceil((84 * count) / Math.max(1, plotWidth)));
    const hitWidth = count <= 1 ? plotWidth : plotWidth / (count - 1);

    return (
        <div className="gls-frais-chart">
            <Legend />
            <div className="position-relative" ref={ref}>
                <svg
                    width={width}
                    height={height}
                    viewBox={`0 0 ${width} ${height}`}
                    role="img"
                    aria-label="Statistiques de présence par date"
                    style={{ display: 'block', maxWidth: '100%' }}
                    onMouseLeave={() => setHoverIndex(null)}
                >
                    <YAxis maxValue={maxValue} plotHeight={plotHeight} right={width - PAD_RIGHT} />

                    {hoverIndex !== null && (
                        <line
                            x1={xFor(hoverIndex)}
                            y1={PAD_TOP}
                            x2={xFor(hoverIndex)}
                            y2={PAD_TOP + plotHeight}
                            stroke="var(--gls-chart-muted)"
                            strokeWidth={1}
                            strokeDasharray="4 3"
                        />
                    )}

                    {SERIES.map((s) => (
                        <g key={s.key}>
                            {count > 1 && (
                                <polyline
                                    points={data.map((d, i) => `${xFor(i)},${yFor(d[s.key])}`).join(' ')}
                                    fill="none"
                                    stroke={s.color}
                                    strokeWidth={2}
                                    strokeLinejoin="round"
                                    strokeLinecap="round"
                                />
                            )}
                            {data.map((d, i) => (
                                <circle
                                    key={d.date}
                                    cx={xFor(i)}
                                    cy={yFor(d[s.key])}
                                    r={hoverIndex === i ? 5 : 4}
                                    fill={s.color}
                                    stroke="var(--gls-chart-surface)"
                                    strokeWidth={2}
                                />
                            ))}
                        </g>
                    ))}

                    {data.map((d, i) => (
                        <rect
                            key={`hit-${d.date}`}
                            x={xFor(i) - hitWidth / 2}
                            y={PAD_TOP}
                            width={hitWidth}
                            height={plotHeight}
                            fill="transparent"
                            onMouseEnter={() => setHoverIndex(i)}
                        />
                    ))}

                    {data.map(
                        (d, i) =>
                            i % labelStep === 0 && (
                                <text
                                    key={`label-${d.date}`}
                                    x={xFor(i)}
                                    y={height - 8}
                                    textAnchor="middle"
                                    fontSize={11}
                                    fill="var(--gls-chart-muted)"
                                >
                                    {formatDate(d.date)}
                                </text>
                            ),
                    )}
                </svg>

                {hoverIndex !== null && data[hoverIndex] && (
                    <div
                        className="gls-frais-tooltip"
                        style={{
                            left: `${xFor(hoverIndex)}px`,
                            top: `${PAD_TOP}px`,
                            minWidth: 200,
                            transform:
                                xFor(hoverIndex) > width / 2 ? 'translate(calc(-100% - 8px), 0)' : 'translate(8px, 0)',
                        }}
                    >
                        <div className="gls-frais-tooltip-header">{formatDate(data[hoverIndex].date)}</div>
                        <TooltipRows presents={data[hoverIndex].presents} absents={data[hoverIndex].absents} />
                    </div>
                )}
            </div>
        </div>
    );
}

/** Long names are cut on the axis; the tooltip always carries the full one. */
function shortName(nom: string): string {
    return nom.length > 22 ? `${nom.slice(0, 21)}…` : nom;
}

/**
 * « Suivi de la présence » — présences / absences per student, two bars side
 * by side per student with the value above each. A large group keeps a
 * readable bar width and scrolls horizontally INSIDE the card, never the page.
 */
export function PresenceParEtudiantChart({ data }: { data: PresenceParEtudiant[] }) {
    const { ref, width: containerWidth } = useContainerWidth();
    const [hoverIndex, setHoverIndex] = useState<number | null>(null);

    const MIN_SLOT = 44;
    const height = 420;
    const padBottom = 120;
    const count = data.length;
    const width = Math.max(containerWidth, PAD_LEFT + PAD_RIGHT + count * MIN_SLOT);
    const plotWidth = width - PAD_LEFT - PAD_RIGHT;
    const plotHeight = height - PAD_TOP - padBottom;
    const slot = plotWidth / Math.max(1, count);
    const barWidth = Math.max(4, Math.min(28, (slot * 0.7 - 2) / 2));
    const maxValue = niceMax(Math.max(0, ...data.flatMap((d) => [d.presents, d.absents])));
    const xCenter = (i: number) => PAD_LEFT + slot * i + slot / 2;
    const yFor = (v: number) => PAD_TOP + plotHeight - (v / maxValue) * plotHeight;
    const baseline = PAD_TOP + plotHeight;

    return (
        <div className="gls-frais-chart">
            <Legend />
            <div ref={ref} style={{ overflowX: 'auto' }}>
                <div className="position-relative" style={{ width }}>
                    <svg
                        width={width}
                        height={height}
                        viewBox={`0 0 ${width} ${height}`}
                        role="img"
                        aria-label="Suivi de la présence par étudiant"
                        style={{ display: 'block' }}
                        onMouseLeave={() => setHoverIndex(null)}
                    >
                        <YAxis maxValue={maxValue} plotHeight={plotHeight} right={width - PAD_RIGHT} />

                        {data.map((d, i) =>
                            SERIES.map((s, k) => {
                                const value = d[s.key];
                                // 2px surface gap between the pair (marks spec).
                                const x = k === 0 ? xCenter(i) - barWidth - 1 : xCenter(i) + 1;
                                const y = yFor(value);

                                return (
                                    <g key={`${d.id}-${s.key}`} opacity={hoverIndex === null || hoverIndex === i ? 1 : 0.45}>
                                        {value > 0 && (
                                            <rect
                                                x={x}
                                                y={y}
                                                width={barWidth}
                                                height={Math.max(0, baseline - y)}
                                                rx={Math.min(4, barWidth / 4)}
                                                fill={s.color}
                                            />
                                        )}
                                        <text
                                            x={x + barWidth / 2}
                                            y={y - 5}
                                            textAnchor="middle"
                                            fontSize={11}
                                            fontWeight={600}
                                            fill="var(--gls-chart-muted)"
                                        >
                                            {value}
                                        </text>
                                    </g>
                                );
                            }),
                        )}

                        {data.map((d, i) => (
                            <rect
                                key={`hit-${d.id}`}
                                x={PAD_LEFT + slot * i}
                                y={PAD_TOP}
                                width={slot}
                                height={plotHeight + padBottom}
                                fill="transparent"
                                onMouseEnter={() => setHoverIndex(i)}
                            />
                        ))}

                        {data.map((d, i) => (
                            <text
                                key={`label-${d.id}`}
                                x={xCenter(i)}
                                y={baseline + 12}
                                transform={`rotate(-45 ${xCenter(i)} ${baseline + 12})`}
                                textAnchor="end"
                                fontSize={11}
                                fill="var(--gls-chart-muted)"
                                fontStyle={d.actif ? undefined : 'italic'}
                                style={{ textTransform: 'uppercase', pointerEvents: 'none' }}
                            >
                                {shortName(d.nom)}
                            </text>
                        ))}
                    </svg>

                    {hoverIndex !== null && data[hoverIndex] && (
                        <div
                            className="gls-frais-tooltip"
                            style={{
                                left: `${xCenter(hoverIndex)}px`,
                                top: `${PAD_TOP}px`,
                                minWidth: 220,
                                transform:
                                    xCenter(hoverIndex) > width / 2
                                        ? 'translate(calc(-100% - 8px), 0)'
                                        : 'translate(8px, 0)',
                            }}
                        >
                            <div className="gls-frais-tooltip-header text-uppercase">{data[hoverIndex].nom}</div>
                            <TooltipRows presents={data[hoverIndex].presents} absents={data[hoverIndex].absents} />
                            {!data[hoverIndex].actif && (
                                <div className="gls-frais-tooltip-row fs-12 opacity-75">Inscription close</div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
