import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { t } from '@/Lib/i18n';

export type StatVariant = 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'teal' | 'skyblue';

interface StatCardProps {
    /** Tabler icon class, e.g. "ti-school" (no "ti " prefix needed — added here). */
    icon: string;
    /** Theme colour family — drives the icon's `bg-*-transparent` circle. */
    variant: StatVariant;
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
    /**
     * Module the figure comes from. When set (the caller checks the user's
     * permission first — UI convenience only, §5) the whole card becomes an
     * Inertia link and shows an « Ouvrir » affordance on hover, so a KPI is
     * one click away from the list that explains it.
     */
    href?: string;
}

/**
 * Dashboard KPI card. Same PreSkool `.card` + `.avatar` markup as the
 * theme's admin dashboard, plus an optional link to the underlying module. The icon is a Tabler icon class
 * (`<i className="ti ti-…">`) inside a `bg-*-transparent` circle — matches
 * how every other icon in the backoffice is rendered (RowActions,
 * StatusBadge, nav items), no separate image assets.
 */
export default function StatCard({
    icon,
    variant,
    value,
    label,
    unit,
    valueTitle,
    secondaryLabel,
    secondaryValue,
    footer,
    href,
}: StatCardProps) {
    const body = (
        <div className="card-body">
            <div className="d-flex align-items-center">
                <div className={`avatar avatar-xl me-2 d-flex align-items-center justify-content-center rounded-circle bg-${variant}-transparent gls-stat-icon`}>
                    <i className={`ti ${icon} fs-24`} aria-hidden="true" />
                </div>
                <div className="overflow-hidden flex-fill">
                    <h2 className={`stat-counter${unit ? ' stat-counter-money' : ''}`} title={valueTitle}>
                        <span className="stat-counter-value">{value}</span>
                        {unit && <span className="stat-counter-unit">{unit}</span>}
                    </h2>
                    <p className="text-gray">{label}</p>
                </div>
                {href && (
                    <span className="gls-stat-open" aria-hidden="true">
                        {t('Open the list')} <i className="ti ti-arrow-up-right" />
                    </span>
                )}
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
    );

    const className = 'card flex-fill border-0 gls-stat-card';

    return (
        <div className="col-xxl-3 col-xl-4 col-sm-6 d-flex">
            {href ? (
                <Link href={href} className={`${className} gls-stat-card-link`} aria-label={`${label} — ${t('Open the list')}`}>
                    {body}
                </Link>
            ) : (
                <div className={className}>{body}</div>
            )}
        </div>
    );
}
