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
const PAD_TOP = 22;
const PAD_BOTTOM = 32;
/** Columns never fill the slot (dataviz mark spec: ≤ 24px, the rest is air). */
const BAR_MAX = 24;
/** Surface gap between touching segments, and between a column and the zero line. */
const GAP = 2;
const RADIUS = 4;

type LossKey = 'annulees' | 'archivees' | 'supprimees';

/**
 * What was LOST, stacked downward from the zero line in this order (the most
 * common gesture sits closest to it). Colours = the inscription statut palette
 * (Lib/inscriptionStatut) — red cancelled, grey archived — plus purple for a
 * deletion. A group change is NOT part of this view (owner, 03/11/2026).
 */
const LOSSES: { key: LossKey; label: string; color: string }[] = [
    { key: 'annulees', label: 'Registrations: cancelled', color: 'var(--gls-bilan-annulee)' },
    { key: 'archivees', label: 'Registrations: archived', color: 'var(--gls-bilan-archivee)' },
    { key: 'supprimees', label: 'Registrations: deleted', color: 'var(--gls-bilan-supprimee)' },
];
const GAIN_COLOR = 'var(--gls-bilan-nouvelles)';

/** Grid step: the larger side rounded up to 4 whole steps (same rule as the bars view). */
function niceStep(max: number): number {
    if (max <= 4) {
        return 1;
    }
    const magnitude = 10 ** Math.floor(Math.log10(max / 4));

    return Math.ceil(max / 4 / magnitude) * magnitude;
}

const fr = (v: number): string => v.toLocaleString('fr-FR');
const signed = (v: number): string => (v > 0 ? `+${fr(v)}` : v < 0 ? `−${fr(-v)}` : '0');
/** A loss reads « −1 »; nothing lost reads « 0 », never « −0 ». */
const perte = (v: number): string => (v > 0 ? `−${fr(v)}` : '0');

/**
 * A rectangle with its DATA end rounded (top for a gain, bottom for a loss)
 * and the baseline end square — the mark spec of every column on this card.
 */
function columnPath(x: number, y: number, w: number, h: number, rounded: 'top' | 'bottom' | 'none'): string {
    const r = rounded === 'none' ? 0 : Math.min(RADIUS, w / 2, h);
    if (r <= 0) {
        return `M${x},${y}h${w}v${h}h${-w}Z`;
    }
    if (rounded === 'top') {
        return `M${x},${y + h}V${y + r}Q${x},${y} ${x + r},${y}H${x + w - r}Q${x + w},${y} ${x + w},${y + r}V${y + h}Z`;
    }

    return `M${x},${y}H${x + w}V${y + h - r}Q${x + w},${y + h} ${x + w - r},${y + h}H${x + r}Q${x},${y + h} ${x},${y + h - r}Z`;
}

/**
 * « Bilan des inscriptions » — the second view of the « Nouvelles
 * inscriptions » card, behind the swap icon (03/11/2026, reworked the same
 * day for readability). Four tiles say the period in one glance (what was
 * gained, what was lost, the net result with its direction spelled out),
 * then ONE column per bucket: new registrations rise above the zero line,
 * cancellations / archivings / deletions hang below it, stacked, and the net
 * result is written at the column's end. Empty buckets draw nothing — the
 * old net-result line zig-zagged through every zero and buried the few
 * buckets that mattered. Every number comes from the server.
 */
export default function InscriptionsBilanChart({ data, width }: InscriptionsBilanChartProps) {
    const [hoverIndex, setHoverIndex] = useState<number | null>(null);
    const { bilan } = data;
    const totaux = bilan.totaux;
    const pertesTotal = totaux.annulees + totaux.archivees + totaux.supprimees;

    const isNarrow = width < 640;
    const heightPx = isNarrow ? HEIGHT_MOBILE : HEIGHT;
    const plotWidth = width - PAD_LEFT - PAD_RIGHT;
    const plotHeight = heightPx - PAD_TOP - PAD_BOTTOM;
    const count = data.counts.length;

    const loss = (i: number): number => LOSSES.reduce((sum, l) => sum + (bilan[l.key][i] ?? 0), 0);
    const upMax = Math.max(0, ...data.counts);
    const downMax = Math.max(0, ...data.counts.map((_, i) => loss(i)));
    const step = niceStep(Math.max(upMax, downMax));
    const upTicks = Math.max(1, Math.ceil(upMax / step));
    const downTicks = Math.max(1, Math.ceil(downMax / step));
    const pxPerUnit = plotHeight / ((upTicks + downTicks) * step);
    const zeroY = PAD_TOP + upTicks * step * pxPerUnit;
    const yFor = (v: number) => zeroY - v * pxPerUnit;

    const slot = plotWidth / Math.max(1, count);
    const barWidth = Math.max(3, Math.min(BAR_MAX, slot * 0.6));
    const xCenter = (i: number) => PAD_LEFT + slot * i + slot / 2;
    const labelStep = Math.max(1, Math.ceil(44 / slot));
    const ticks = Array.from({ length: upTicks + downTicks + 1 }, (_, k) => (upTicks - k) * step);
    // The net-result label only where the column is wide enough to own it.
    const showNetLabels = slot >= 18;

    const tendance =
        totaux.solde > 0
            ? { icon: 'ti-trending-up', label: t('Balance: growing'), variant: 'success' }
            : totaux.solde < 0
              ? { icon: 'ti-trending-down', label: t('Balance: declining'), variant: 'danger' }
              : { icon: 'ti-minus', label: t('Balance: stable'), variant: 'secondary' };

    return (
        <>
            <div className="row g-2 mb-3">
                <div className="col-6 col-md-3">
                    <div className="border rounded p-2 h-100">
                        <div className="d-flex align-items-center gap-1 text-muted fs-13">
                            <span className="gls-frais-tooltip-dot" style={{ backgroundColor: GAIN_COLOR }} />
                            {t('Balance: gained')}
                        </div>
                        <div className="fs-20 fw-semibold">{fr(totaux.nouvelles)}</div>
                        <div className="text-muted fs-12">
                            {t('Balance: new (count)')} · {t('of which :count active', { count: fr(totaux.actives) })}
                        </div>
                    </div>
                </div>
                <div className="col-6 col-md-3">
                    <div className="border rounded p-2 h-100">
                        <div className="d-flex align-items-center gap-1 text-muted fs-13">
                            <span className="gls-frais-tooltip-dot" style={{ backgroundColor: 'var(--gls-bilan-annulee)' }} />
                            {t('Balance: lost')}
                        </div>
                        <div className="fs-20 fw-semibold">{perte(pertesTotal)}</div>
                        <div className="text-muted fs-12">
                            {LOSSES.map((l) => `${fr(totaux[l.key])} ${t(l.label).toLowerCase()}`).join(' · ')}
                        </div>
                    </div>
                </div>
                <div className="col-12 col-md-6">
                    <div className="border rounded p-2 h-100 d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <div className="text-muted fs-13">{t('Net result')}</div>
                            <div className="fs-20 fw-semibold">{signed(totaux.solde)}</div>
                            <div className="text-muted fs-12">
                                {fr(totaux.nouvelles)} {t('Balance: new (count)')} − {fr(pertesTotal)} {t('Balance: lost (count)')}
                            </div>
                        </div>
                        <span className={`badge badge-soft-${tendance.variant} fs-13 d-inline-flex align-items-center gap-1`}>
                            <i className={`ti ${tendance.icon}`} />
                            {tendance.label}
                        </span>
                    </div>
                </div>
            </div>

            <div className="position-relative">
                <svg
                    width={width}
                    height={heightPx}
                    viewBox={`0 0 ${width} ${heightPx}`}
                    role="img"
                    aria-label={t('Registrations balance')}
                    style={{ display: 'block', maxWidth: '100%' }}
                    onMouseLeave={() => setHoverIndex(null)}
                >
                    {/* Hovered bucket: a recessive band, so the column and its tooltip read together. */}
                    {hoverIndex !== null && (
                        <rect
                            x={PAD_LEFT + slot * hoverIndex}
                            y={PAD_TOP}
                            width={slot}
                            height={plotHeight}
                            fill="var(--gls-chart-grid)"
                            opacity={0.6}
                        />
                    )}

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
                                    {v < 0 ? `−${fr(-v)}` : fr(v)}
                                </text>
                            </g>
                        );
                    })}

                    {data.counts.map((nouvelles, i) => {
                        const x = xCenter(i) - barWidth / 2;
                        const pertes = LOSSES.map((l) => ({ ...l, v: bilan[l.key][i] ?? 0 })).filter((l) => l.v > 0);
                        let cursor = zeroY + GAP;
                        const net = bilan.solde[i] ?? 0;
                        const actif = nouvelles > 0 || pertes.length > 0;

                        return (
                            <g key={`col-${i}`}>
                                {nouvelles > 0 && (
                                    <path
                                        d={columnPath(x, yFor(nouvelles), barWidth, Math.max(1, nouvelles * pxPerUnit - GAP), 'top')}
                                        fill={GAIN_COLOR}
                                    />
                                )}
                                {pertes.map((l, k) => {
                                    const h = Math.max(1, l.v * pxPerUnit - GAP);
                                    const d = columnPath(x, cursor, barWidth, h, k === pertes.length - 1 ? 'bottom' : 'none');
                                    cursor += h + GAP;
                                    return <path key={l.key} d={d} fill={l.color} />;
                                })}
                                {actif && showNetLabels && (
                                    <text
                                        x={xCenter(i)}
                                        y={net >= 0 ? yFor(nouvelles) - 6 : cursor + 12}
                                        textAnchor="middle"
                                        fontSize={12}
                                        fontWeight={600}
                                        fill="var(--gls-chart-muted)"
                                    >
                                        {signed(net)}
                                    </text>
                                )}
                            </g>
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
                        <div className="gls-frais-tooltip-row">
                            <span className="gls-frais-tooltip-dot" style={{ backgroundColor: GAIN_COLOR }} />
                            <span className="gls-frais-tooltip-label">{t('Registrations: new')}:</span>
                            <span className="gls-frais-tooltip-value">
                                {fr(data.counts[hoverIndex])}
                                <span className="opacity-75 fw-normal">
                                    {' '}({t('of which :count active', { count: fr(bilan.actives[hoverIndex] ?? 0) })})
                                </span>
                            </span>
                        </div>
                        {LOSSES.map((l) => (
                            <div key={l.key} className="gls-frais-tooltip-row">
                                <span className="gls-frais-tooltip-dot" style={{ backgroundColor: l.color }} />
                                <span className="gls-frais-tooltip-label">{t(l.label)}:</span>
                                <span className="gls-frais-tooltip-value">{perte(bilan[l.key][hoverIndex] ?? 0)}</span>
                            </div>
                        ))}
                        <div className="gls-frais-tooltip-row mt-1 pt-1 border-top border-secondary">
                            <span className="gls-frais-tooltip-label fw-semibold">{t('Net result')}:</span>
                            <span className="gls-frais-tooltip-value">{signed(bilan.solde[hoverIndex] ?? 0)}</span>
                        </div>
                    </div>
                )}
            </div>

            <div className="d-flex flex-wrap align-items-center gap-3 mt-2 fs-13 text-muted">
                <span className="d-inline-flex align-items-center gap-1">
                    <span className="gls-frais-tooltip-dot" style={{ backgroundColor: GAIN_COLOR }} />
                    {t('Registrations: new')}
                </span>
                {LOSSES.map((l) => (
                    <span key={l.key} className="d-inline-flex align-items-center gap-1">
                        <span className="gls-frais-tooltip-dot" style={{ backgroundColor: l.color }} />
                        {t(l.label)}
                    </span>
                ))}
                <span className="ms-md-auto fs-12">{t('The number above or below each column is its net result.')}</span>
            </div>
        </>
    );
}
