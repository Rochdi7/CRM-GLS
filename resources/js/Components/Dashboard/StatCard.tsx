import type { ReactNode } from 'react';

interface StatCardProps {
    /** Tabler icon class, e.g. "ti-school" (no "ti " prefix needed — added here). */
    icon: string;
    iconBg: string;
    value: ReactNode;
    label: string;
    /**
     * Currency unit rendered as a smaller, muted suffix next to `value`
     * (e.g. "MAD"). Money figures are long enough that baking the unit into
     * the value string made the whole line wrap onto two rows and knocked
     * the card out of alignment with its neighbours — keeping the unit
     * separate lets the amount stay on one line and reads as an amount
     * rather than as a longer number.
     */
    unit?: string;
    /**
     * Native tooltip for `value` — used when the displayed figure is
     * abbreviated (e.g. "1,25 M") so the exact amount stays one hover away
     * instead of being lost.
     */
    valueTitle?: string;
    secondaryLabel?: string;
    secondaryValue?: ReactNode;
    /** Free-form footer row (e.g. several status badges) — replaces the secondary label/value pair. */
    footer?: ReactNode;
}

/**
 * Same card layout as before, but the icon is a Tabler icon class
 * (`<i className="ti ti-…">`) inside a colored `.avatar` circle instead of
 * an illustrated SVG — matches how every other icon in the backoffice is
 * rendered (RowActions, StatusBadge, nav items), no separate image assets.
 */
export default function StatCard({ icon, iconBg, value, label, unit, valueTitle, secondaryLabel, secondaryValue, footer }: StatCardProps) {
    return (
        <div className="col-xxl-3 col-sm-6 d-flex">
            <div className="card flex-fill border-0">
                <div className="card-body">
                    <div className="d-flex align-items-center">
                        <div className={`avatar avatar-xl me-2 d-flex align-items-center justify-content-center rounded-circle ${iconBg}`}>
                            <i className={`ti ${icon} fs-24`} aria-hidden="true" />
                        </div>
                        <div className="overflow-hidden flex-fill">
                            <h2 className={`stat-counter${unit ? ' stat-counter-money' : ''}`} title={valueTitle}>
                                <span className="stat-counter-value">{value}</span>
                                {unit && <span className="stat-counter-unit">{unit}</span>}
                            </h2>
                            <p className="text-gray">{label}</p>
                        </div>
                    </div>
                    {footer ? (
                        <div className="d-flex align-items-center flex-wrap gap-1 border-top mt-3 pt-3">
                            {footer}
                        </div>
                    ) : secondaryLabel && (
                        <div className="d-flex align-items-center justify-content-between border-top mt-3 pt-3">
                            <p className="mb-0">
                                {secondaryLabel} : <span className="text-dark fw-semibold">{secondaryValue}</span>
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
