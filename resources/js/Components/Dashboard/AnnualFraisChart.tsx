import { useMemo, useState } from 'react';
import type { AnnualFraisSummary } from '@/Types';
import { t } from '@/Lib/i18n';

interface AnnualFraisChartProps {
    data: AnnualFraisSummary;
    year: number;
    years: number[];
    onYearChange: (year: number) => void;
}

interface SeriesSpec {
    key: keyof Omit<AnnualFraisSummary, 'months'>;
    label: string;
    color: string;
}

/**
 * Theme colors (public/assets/preskool/css/style.css) — matches the app's
 * existing badge/status palette (StatusBadge variants) rather than a new
 * chart-specific one, so the dashboard stays visually consistent with the
 * rest of the backoffice.
 */
const SERIES: SeriesSpec[] = [
    { key: 'chiffreAffaire', label: 'Revenue (billed)', color: '#E82646' },
    { key: 'collecte', label: 'Collected on billed fees', color: '#3D5EE1' },
    { key: 'resteAPayer', label: 'Remaining to collect', color: '#FD7E14' },
    { key: 'depenses', label: 'Expenses', color: '#1ABE17' },
    { key: 'encaissements', label: 'All payments received', color: '#E83E8C' },
];

const WIDTH = 1000;
const HEIGHT = 320;
const PAD_LEFT = 56;
const PAD_RIGHT = 16;
const PAD_TOP = 16;
const PAD_BOTTOM = 32;

function niceMax(max: number): number {
    if (max <= 0) {
        return 100;
    }
    const magnitude = 10 ** Math.floor(Math.log10(max));
    const normalized = max / magnitude;
    const step = normalized <= 1 ? 1 : normalized <= 2 ? 2 : normalized <= 4 ? 4 : normalized <= 8 ? 8 : 10;

    return step * magnitude;
}

/**
 * Custom SVG area chart — no new chart-library dependency (project has none
 * installed; this stays consistent with "own code only" §1). Multi-series
 * overlapping filled areas + a shared crosshair/tooltip on hover (dataviz
 * skill's interaction rules), legend always shown (5 series), thin 2px
 * strokes, recessive gridlines.
 */
export default function AnnualFraisChart({ data, year, years, onYearChange }: AnnualFraisChartProps) {
    const [hoverIndex, setHoverIndex] = useState<number | null>(null);

    const values = useMemo(
        () =>
            SERIES.reduce<Record<string, number[]>>((acc, s) => {
                acc[s.key] = data[s.key].map((v) => Number(v));
                return acc;
            }, {}),
        [data],
    );

    const maxValue = useMemo(() => {
        const all = SERIES.flatMap((s) => values[s.key]);
        return niceMax(Math.max(1, ...all));
    }, [values]);

    const plotWidth = WIDTH - PAD_LEFT - PAD_RIGHT;
    const plotHeight = HEIGHT - PAD_TOP - PAD_BOTTOM;
    const count = data.months.length;
    const xFor = (i: number) => PAD_LEFT + (count <= 1 ? 0 : (i / (count - 1)) * plotWidth);
    const yFor = (v: number) => PAD_TOP + plotHeight - (v / maxValue) * plotHeight;

    function areaPath(key: SeriesSpec['key']): string {
        const points = values[key];
        const top = points.map((v, i) => `${i === 0 ? 'M' : 'L'} ${xFor(i)} ${yFor(v)}`).join(' ');
        const bottom = `L ${xFor(points.length - 1)} ${yFor(0)} L ${xFor(0)} ${yFor(0)} Z`;

        return `${top} ${bottom}`;
    }

    function linePath(key: SeriesSpec['key']): string {
        return values[key].map((v, i) => `${i === 0 ? 'M' : 'L'} ${xFor(i)} ${yFor(v)}`).join(' ');
    }

    const gridLines = [0, 0.25, 0.5, 0.75, 1];

    return (
        <div className="card gls-frais-chart">
            <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                <div className="mb-3">
                    <h4 className="mb-1">{t('Annual fees summary')}</h4>
                    <p className="text-muted mb-0">{t('Annual fees overview')}</p>
                </div>
                <div className="d-flex align-items-center gap-2 mb-3">
                    <i className="fa fa-calendar text-muted" />
                    <label className="text-muted mb-0" htmlFor="frais-chart-year">
                        {t('Year')}
                    </label>
                    <select
                        id="frais-chart-year"
                        className="form-select form-select-sm"
                        style={{ width: 100 }}
                        value={year}
                        onChange={(event) => onYearChange(Number(event.target.value))}
                    >
                        {years.map((y) => (
                            <option key={y} value={y}>
                                {y}
                            </option>
                        ))}
                    </select>
                </div>
            </div>
            <div className="card-body pt-2">
                <div className="position-relative">
                    <svg
                        viewBox={`0 0 ${WIDTH} ${HEIGHT}`}
                        role="img"
                        aria-label={t('Annual fees overview')}
                        className="w-100"
                        style={{ display: 'block' }}
                        onMouseLeave={() => setHoverIndex(null)}
                    >
                        <defs>
                            {SERIES.map((s) => (
                                <linearGradient key={s.key} id={`gls-frais-grad-${s.key}`} x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stopColor={s.color} stopOpacity={0.35} />
                                    <stop offset="100%" stopColor={s.color} stopOpacity={0.03} />
                                </linearGradient>
                            ))}
                        </defs>

                        {gridLines.map((fraction) => {
                            const y = PAD_TOP + plotHeight - fraction * plotHeight;
                            return (
                                <g key={fraction}>
                                    <line x1={PAD_LEFT} y1={y} x2={WIDTH - PAD_RIGHT} y2={y} stroke="var(--gls-chart-grid)" strokeWidth={1} />
                                    <text x={PAD_LEFT - 8} y={y + 4} textAnchor="end" fontSize={11} fill="var(--gls-chart-muted)">
                                        {Math.round(maxValue * fraction).toLocaleString('fr-FR')}
                                    </text>
                                </g>
                            );
                        })}

                        {SERIES.map((s) => (
                            <path key={`area-${s.key}`} d={areaPath(s.key)} fill={`url(#gls-frais-grad-${s.key})`} stroke="none" />
                        ))}
                        {SERIES.map((s) => (
                            <path
                                key={`line-${s.key}`}
                                d={linePath(s.key)}
                                fill="none"
                                stroke={s.color}
                                strokeWidth={2}
                                strokeLinejoin="round"
                                strokeLinecap="round"
                            />
                        ))}

                        {data.months.map((_, i) => (
                            <rect
                                key={`hit-${i}`}
                                x={xFor(i) - plotWidth / Math.max(1, count - 1) / 2}
                                y={PAD_TOP}
                                width={plotWidth / Math.max(1, count - 1)}
                                height={plotHeight}
                                fill="transparent"
                                onMouseEnter={() => setHoverIndex(i)}
                            />
                        ))}

                        {hoverIndex !== null && (
                            <>
                                <line
                                    x1={xFor(hoverIndex)}
                                    y1={PAD_TOP}
                                    x2={xFor(hoverIndex)}
                                    y2={PAD_TOP + plotHeight}
                                    stroke="var(--gls-chart-muted)"
                                    strokeWidth={1}
                                    strokeDasharray="4 3"
                                />
                                {SERIES.map((s) => (
                                    <circle
                                        key={`dot-${s.key}`}
                                        cx={xFor(hoverIndex)}
                                        cy={yFor(values[s.key][hoverIndex])}
                                        r={4}
                                        fill={s.color}
                                        stroke="var(--gls-chart-surface)"
                                        strokeWidth={2}
                                    />
                                ))}
                            </>
                        )}

                        {data.months.map((label, i) => (
                            <text
                                key={label}
                                x={xFor(i)}
                                y={HEIGHT - 8}
                                textAnchor="middle"
                                fontSize={11}
                                fill="var(--gls-chart-muted)"
                            >
                                {label}
                            </text>
                        ))}
                    </svg>

                    {hoverIndex !== null && (
                        <div
                            className="gls-frais-tooltip"
                            style={{
                                left: `${(xFor(hoverIndex) / WIDTH) * 100}%`,
                                top: `${(PAD_TOP / HEIGHT) * 100}%`,
                            }}
                        >
                            <div className="gls-frais-tooltip-header">{data.months[hoverIndex]}</div>
                            {SERIES.map((s) => (
                                <div className="gls-frais-tooltip-row" key={s.key}>
                                    <span className="gls-frais-tooltip-dot" style={{ backgroundColor: s.color }} />
                                    <span className="gls-frais-tooltip-label">{t(s.label)}:</span>
                                    <span className="gls-frais-tooltip-value">
                                        {values[s.key][hoverIndex].toLocaleString('fr-FR')}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="d-flex flex-wrap gap-3 justify-content-center mt-3">
                    {SERIES.map((s) => (
                        <span key={s.key} className="d-inline-flex align-items-center gap-1 text-muted fs-13">
                            <span
                                className="d-inline-block rounded-circle"
                                style={{ width: 10, height: 10, backgroundColor: s.color }}
                                aria-hidden="true"
                            />
                            {t(s.label)}
                        </span>
                    ))}
                </div>
            </div>
        </div>
    );
}
