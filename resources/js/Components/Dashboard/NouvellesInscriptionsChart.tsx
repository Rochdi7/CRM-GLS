import { useEffect, useRef, useState } from 'react';
import type { NouvellesInscriptionsChartData, NouvellesInscriptionsDuree } from '@/Types';
import { t } from '@/Lib/i18n';

interface NouvellesInscriptionsChartProps {
    data: NouvellesInscriptionsChartData;
    onDureeChange: (duree: NouvellesInscriptionsDuree) => void;
    loading?: boolean;
}

const DUREES: { value: NouvellesInscriptionsDuree; label: string }[] = [
    { value: 'jour', label: 'Today' },
    { value: '7j', label: '7 days' },
    { value: '30j', label: '30 days' },
    { value: '12s', label: '12 weeks' },
    { value: '12m', label: '12 months' },
    { value: 'annee', label: 'Academic year' },
];

const BAR_COLOR = '#3D5EE1';
const FALLBACK_WIDTH = 1000;
const HEIGHT = 280;
const HEIGHT_MOBILE = 220;
const PAD_LEFT = 44;
const PAD_RIGHT = 12;
const PAD_TOP = 24;
const PAD_BOTTOM = 32;

/**
 * Axis top rounded UP to a multiple of 4 whole registrations, so the four
 * gridline steps are integers (a top of 10 drew « 2.5 » rounded to « 3 »).
 */
function niceMax(max: number): number {
    if (max <= 4) {
        return 4;
    }
    const magnitude = 10 ** Math.floor(Math.log10(max / 4));
    const step = Math.ceil(max / 4 / magnitude) * magnitude;

    return step * 4;
}

/**
 * « Nouvelles inscriptions » — super-admin bar chart (GetNouvellesInscriptionsChart).
 * Counts NEW registrations only: the successor row of a « Changement de
 * groupe » is excluded server-side, and « Modification du groupe » never
 * creates a row. The duration buttons re-query the server (partial reload),
 * nothing is filtered client-side. Same measured-width SVG approach as
 * AnnualFraisChart so 11px labels stay 11px on every screen.
 */
export default function NouvellesInscriptionsChart({ data, onDureeChange, loading = false }: NouvellesInscriptionsChartProps) {
    const [hoverIndex, setHoverIndex] = useState<number | null>(null);
    const containerRef = useRef<HTMLDivElement | null>(null);
    const [WIDTH, setWidth] = useState(FALLBACK_WIDTH);

    useEffect(() => {
        const el = containerRef.current;
        if (!el || typeof ResizeObserver === 'undefined') return;

        const observer = new ResizeObserver((entries) => {
            const w = Math.round(entries[0]?.contentRect.width ?? 0);
            if (w > 0) setWidth(w);
        });
        observer.observe(el);

        return () => observer.disconnect();
    }, []);

    const isNarrow = WIDTH < 640;
    const HEIGHT_PX = isNarrow ? HEIGHT_MOBILE : HEIGHT;
    const plotWidth = WIDTH - PAD_LEFT - PAD_RIGHT;
    const plotHeight = HEIGHT_PX - PAD_TOP - PAD_BOTTOM;
    const count = data.counts.length;
    const maxValue = niceMax(Math.max(0, ...data.counts));
    const slot = plotWidth / Math.max(1, count);
    const barWidth = Math.max(2, Math.min(48, slot * 0.7));
    const xCenter = (i: number) => PAD_LEFT + slot * i + slot / 2;
    const yFor = (v: number) => PAD_TOP + plotHeight - (v / maxValue) * plotHeight;
    // Keep ~44px per visible label ("dd/mm" / "mm/yyyy" at 11px).
    const labelStep = Math.max(1, Math.ceil(44 / slot));
    const gridLines = [0, 0.25, 0.5, 0.75, 1];

    return (
        <div className="card gls-frais-chart gls-dash-card">
            <div className="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h4 className="card-title mb-1">{t('New registrations')}</h4>
                    <p className="text-muted mb-0">{data.periode}</p>
                </div>
                <div className="d-flex align-items-center flex-wrap gap-2">
                    <span className="badge badge-soft-primary fs-13">
                        {t('Total')} : {data.total.toLocaleString('fr-FR')}
                    </span>
                    <div className="btn-group btn-group-sm" role="group" aria-label={t('Duration')}>
                        {DUREES.map((d) => (
                            <button
                                key={d.value}
                                type="button"
                                className={`btn ${data.duree === d.value ? 'btn-primary' : 'btn-outline-light text-body'}`}
                                aria-pressed={data.duree === d.value}
                                disabled={loading}
                                onClick={() => d.value !== data.duree && onDureeChange(d.value)}
                            >
                                {t(d.label)}
                            </button>
                        ))}
                    </div>
                </div>
            </div>
            <div className="card-body">
                <div className="position-relative" ref={containerRef} style={{ opacity: loading ? 0.5 : 1 }}>
                    <svg
                        width={WIDTH}
                        height={HEIGHT_PX}
                        viewBox={`0 0 ${WIDTH} ${HEIGHT_PX}`}
                        role="img"
                        aria-label={t('New registrations')}
                        style={{ display: 'block', maxWidth: '100%' }}
                        onMouseLeave={() => setHoverIndex(null)}
                    >
                        {gridLines.map((fraction) => {
                            const y = PAD_TOP + plotHeight - fraction * plotHeight;
                            return (
                                <g key={fraction}>
                                    <line x1={PAD_LEFT} y1={y} x2={WIDTH - PAD_RIGHT} y2={y} stroke="var(--gls-chart-grid)" strokeWidth={1} />
                                    <text x={PAD_LEFT - 8} y={y + 4} textAnchor="end" fontSize={11} fill="var(--gls-chart-muted)">
                                        {Math.round(maxValue * fraction)}
                                    </text>
                                </g>
                            );
                        })}

                        {data.counts.map((v, i) => {
                            const y = yFor(v);
                            return (
                                <rect
                                    key={`bar-${i}`}
                                    x={xCenter(i) - barWidth / 2}
                                    y={y}
                                    width={barWidth}
                                    height={Math.max(0, PAD_TOP + plotHeight - y)}
                                    rx={Math.min(4, barWidth / 4)}
                                    fill={BAR_COLOR}
                                    opacity={hoverIndex === null || hoverIndex === i ? 1 : 0.45}
                                />
                            );
                        })}

                        {/* The count per bar (per day / week / month), written above it —
                            skipped when bars are too narrow for the digits. */}
                        {slot >= 18 && data.counts.map((v, i) => {
                            if (v === 0) return null;
                            return (
                                <text
                                    key={`val-${i}`}
                                    x={xCenter(i)}
                                    y={yFor(v) - 6}
                                    textAnchor="middle"
                                    fontSize={12}
                                    fontWeight={600}
                                    fill="var(--gls-chart-muted)"
                                >
                                    {v.toLocaleString('fr-FR')}
                                </text>
                            );
                        })}

                        {data.counts.map((_, i) => (
                            <rect
                                key={`hit-${i}`}
                                x={PAD_LEFT + slot * i}
                                y={PAD_TOP}
                                width={slot}
                                height={plotHeight}
                                fill="transparent"
                                onMouseEnter={() => setHoverIndex(i)}
                            />
                        ))}

                        {data.labels.map((label, i) =>
                            i % labelStep === 0 && (
                                <text
                                    key={`${label}-${i}`}
                                    x={xCenter(i)}
                                    y={HEIGHT_PX - 8}
                                    textAnchor="middle"
                                    fontSize={11}
                                    fill="var(--gls-chart-muted)"
                                >
                                    {label}
                                </text>
                            ),
                        )}
                    </svg>

                    {hoverIndex !== null && (
                        <div
                            className="gls-frais-tooltip"
                            style={{
                                left: `${xCenter(hoverIndex)}px`,
                                top: `${PAD_TOP}px`,
                                transform:
                                    xCenter(hoverIndex) > WIDTH / 2 ? 'translate(calc(-100% - 8px), 0)' : 'translate(8px, 0)',
                            }}
                        >
                            <div className="gls-frais-tooltip-header">{data.labels[hoverIndex]}</div>
                            <div className="gls-frais-tooltip-row">
                                <span className="gls-frais-tooltip-dot" style={{ backgroundColor: BAR_COLOR }} />
                                <span className="gls-frais-tooltip-label">{t('New registrations')}:</span>
                                <span className="gls-frais-tooltip-value">{data.counts[hoverIndex].toLocaleString('fr-FR')}</span>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
