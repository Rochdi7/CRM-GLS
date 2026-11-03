import { useState } from 'react';
import type { NouvellesInscriptionsChartData } from '@/Types';
import { t } from '@/Lib/i18n';

interface InscriptionsBilanChartProps {
    data: NouvellesInscriptionsChartData;
    /** Measured width of the card body (the parent owns the ResizeObserver). */
    width: number;
}

const HEIGHT = 300;
const HEIGHT_MOBILE = 240;
const PAD_LEFT = 44;
const PAD_RIGHT = 12;
const PAD_TOP = 16;
const PAD_BOTTOM = 32;

type SerieKey = 'nouvelles' | 'annulees' | 'archivees' | 'supprimees';

/**
 * Bars drawn per bucket, left → right. `up` ones rise from the zero line
 * (effort gained), the others hang below it (effort lost). A group change is
 * NOT part of this view (owner, 03/11/2026): the student stays, it is neither
 * a gain nor a loss.
 */
const SERIES: { key: SerieKey; label: string; color: string; up: boolean }[] = [
    { key: 'nouvelles', label: 'Registrations: new', color: 'var(--gls-bilan-nouvelles)', up: true },
    { key: 'annulees', label: 'Registrations: cancelled', color: 'var(--gls-bilan-annulee)', up: false },
    { key: 'archivees', label: 'Registrations: archived', color: 'var(--gls-bilan-archivee)', up: false },
    { key: 'supprimees', label: 'Registrations: deleted', color: 'var(--gls-bilan-supprimee)', up: false },
];

/** Grid step: the larger side rounded up to 4 whole steps (same rule as the bars view). */
function niceStep(max: number): number {
    if (max <= 4) {
        return 1;
    }
    const magnitude = 10 ** Math.floor(Math.log10(max / 4));

    return Math.ceil(max / 4 / magnitude) * magnitude;
}

const signed = (v: number): string => (v > 0 ? `+${v.toLocaleString('fr-FR')}` : v.toLocaleString('fr-FR'));

/** A loss reads « −1 »; nothing lost reads « 0 », never « −0 ». */
const perte = (v: number): string => (v > 0 ? `−${v.toLocaleString('fr-FR')}` : '0');

/**
 * « Bilan des inscriptions » — the second view of the « Nouvelles
 * inscriptions » card, behind the swap icon (03/11/2026). Diverging bars per
 * bucket: what the team gained (new registrations, of which the still-Active
 * part is drawn solid) against what it lost (cancellations, archivings,
 * deletions), and the net-result line on top — above zero is a good day,
 * below a bad one. Every number comes from the server; nothing is re-derived
 * here.
 */
export default function InscriptionsBilanChart({ data, width }: InscriptionsBilanChartProps) {
    const [hoverIndex, setHoverIndex] = useState<number | null>(null);
    const { bilan } = data;

    const value = (key: SerieKey, i: number): number => (key === 'nouvelles' ? data.counts[i] : bilan[key][i]) ?? 0;

    const isNarrow = width < 640;
    const heightPx = isNarrow ? HEIGHT_MOBILE : HEIGHT;
    const plotWidth = width - PAD_LEFT - PAD_RIGHT;
    const plotHeight = heightPx - PAD_TOP - PAD_BOTTOM;
    const count = data.counts.length;

    const upMax = Math.max(0, ...data.counts, ...bilan.solde);
    const downMax = Math.max(
        0,
        ...bilan.annulees,
        ...bilan.archivees,
        ...bilan.supprimees,
        ...bilan.solde.map((s) => -s),
    );
    const step = niceStep(Math.max(upMax, downMax));
    const upTicks = Math.max(1, Math.ceil(upMax / step));
    const downTicks = downMax > 0 ? Math.ceil(downMax / step) : 0;
    const pxPerUnit = plotHeight / ((upTicks + downTicks) * step);
    const zeroY = PAD_TOP + upTicks * step * pxPerUnit;
    const yFor = (v: number) => zeroY - v * pxPerUnit;

    const slot = plotWidth / Math.max(1, count);
    const clusterWidth = Math.min(slot * 0.8, SERIES.length * 12);
    const barWidth = Math.max(1.5, clusterWidth / SERIES.length);
    const xCenter = (i: number) => PAD_LEFT + slot * i + slot / 2;
    const xBar = (i: number, s: number) => xCenter(i) - clusterWidth / 2 + s * barWidth;
    const labelStep = Math.max(1, Math.ceil(44 / slot));
    const ticks = Array.from({ length: upTicks + downTicks + 1 }, (_, k) => (upTicks - k) * step);

    const soldePath = bilan.solde.map((v, i) => `${i === 0 ? 'M' : 'L'}${xCenter(i)},${yFor(v)}`).join(' ');
    const soldeColor = (v: number) => (v > 0 ? 'var(--gls-bilan-nouvelles)' : v < 0 ? 'var(--gls-bilan-annulee)' : 'var(--gls-chart-muted)');

    return (
        <>
            <svg
                width={width}
                height={heightPx}
                viewBox={`0 0 ${width} ${heightPx}`}
                role="img"
                aria-label={t('Registrations balance')}
                style={{ display: 'block', maxWidth: '100%' }}
                onMouseLeave={() => setHoverIndex(null)}
            >
                {ticks.map((v) => {
                    const y = yFor(v);
                    return (
                        <g key={v}>
                            <line
                                x1={PAD_LEFT}
                                y1={y}
                                x2={width - PAD_RIGHT}
                                y2={y}
                                stroke={v === 0 ? 'var(--gls-chart-muted)' : 'var(--gls-chart-grid)'}
                                strokeWidth={1}
                            />
                            <text x={PAD_LEFT - 8} y={y + 4} textAnchor="end" fontSize={11} fill="var(--gls-chart-muted)">
                                {v.toLocaleString('fr-FR')}
                            </text>
                        </g>
                    );
                })}

                {data.counts.map((_, i) => (
                    <g key={`cluster-${i}`} opacity={hoverIndex === null || hoverIndex === i ? 1 : 0.45}>
                        {SERIES.map((serie, s) => {
                            const v = value(serie.key, i);
                            if (v <= 0) return null;
                            const h = v * pxPerUnit;
                            const x = xBar(i, s);
                            if (serie.key === 'nouvelles') {
                                // Solid = still Active today, faded = since closed.
                                const actives = Math.min(v, bilan.actives[i] ?? 0);
                                const hActives = actives * pxPerUnit;
                                return (
                                    <g key={serie.key}>
                                        <rect x={x} y={zeroY - h} width={barWidth} height={h - hActives} fill={serie.color} opacity={0.4} />
                                        <rect x={x} y={zeroY - hActives} width={barWidth} height={hActives} fill={serie.color} />
                                    </g>
                                );
                            }
                            return (
                                <rect
                                    key={serie.key}
                                    x={x}
                                    y={serie.up ? zeroY - h : zeroY}
                                    width={barWidth}
                                    height={h}
                                    fill={serie.color}
                                />
                            );
                        })}
                    </g>
                ))}

                <path d={soldePath} fill="none" stroke="var(--gls-bilan-solde)" strokeWidth={2} strokeLinejoin="round" />
                {bilan.solde.map((v, i) => (
                    <circle
                        key={`solde-${i}`}
                        cx={xCenter(i)}
                        cy={yFor(v)}
                        r={hoverIndex === i ? 5 : count > 31 ? 2 : 3.5}
                        fill={soldeColor(v)}
                        stroke="var(--gls-chart-surface)"
                        strokeWidth={1.5}
                    />
                ))}

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
                            y={heightPx - 8}
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
                        transform: xCenter(hoverIndex) > width / 2 ? 'translate(calc(-100% - 8px), 0)' : 'translate(8px, 0)',
                    }}
                >
                    <div className="gls-frais-tooltip-header">{data.labels[hoverIndex]}</div>
                    {SERIES.map((serie) => (
                        <div key={serie.key} className="gls-frais-tooltip-row">
                            <span className="gls-frais-tooltip-dot" style={{ backgroundColor: serie.color }} />
                            <span className="gls-frais-tooltip-label">{t(serie.label)}:</span>
                            <span className="gls-frais-tooltip-value">
                                {serie.up
                                    ? value(serie.key, hoverIndex).toLocaleString('fr-FR')
                                    : perte(value(serie.key, hoverIndex))}
                                {serie.key === 'nouvelles' && (
                                    <span className="opacity-75 fw-normal">
                                        {' '}({t('of which :count active', { count: String(bilan.actives[hoverIndex] ?? 0) })})
                                    </span>
                                )}
                            </span>
                        </div>
                    ))}
                    <div className="gls-frais-tooltip-row mt-1 pt-1 border-top border-secondary">
                        <span className="gls-frais-tooltip-dot" style={{ backgroundColor: soldeColor(bilan.solde[hoverIndex]) }} />
                        <span className="gls-frais-tooltip-label">{t('Net result')}:</span>
                        <span className="gls-frais-tooltip-value">{signed(bilan.solde[hoverIndex])}</span>
                    </div>
                </div>
            )}

            <div className="d-flex flex-wrap align-items-center gap-3 mt-3 fs-13">
                {SERIES.map((serie) => (
                    <span key={serie.key} className="d-inline-flex align-items-center gap-1">
                        <span className="gls-frais-tooltip-dot" style={{ backgroundColor: serie.color }} />
                        <span className="text-muted">{t(serie.label)}</span>
                        <span className="fw-semibold">
                            {serie.up ? bilan.totaux[serie.key].toLocaleString('fr-FR') : perte(bilan.totaux[serie.key])}
                        </span>
                        {serie.key === 'nouvelles' && (
                            <span className="text-muted">({t('of which :count active', { count: String(bilan.totaux.actives) })})</span>
                        )}
                    </span>
                ))}
                <span className="d-inline-flex align-items-center gap-1">
                    <span style={{ width: 16, height: 2, backgroundColor: 'var(--gls-bilan-solde)', display: 'inline-block' }} />
                    <span className="text-muted">{t('Net result')}</span>
                </span>
            </div>
        </>
    );
}
