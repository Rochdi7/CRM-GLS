import type { ReactNode } from 'react';

interface StatCardProps {
    /** Tabler icon class, e.g. "ti-school" (no "ti " prefix needed — added here). */
    icon: string;
    iconBg: string;
    value: ReactNode;
    label: string;
    secondaryLabel?: string;
    secondaryValue?: ReactNode;
}

/**
 * Same card layout as before, but the icon is a Tabler icon class
 * (`<i className="ti ti-…">`) inside a colored `.avatar` circle instead of
 * an illustrated SVG — matches how every other icon in the backoffice is
 * rendered (RowActions, StatusBadge, nav items), no separate image assets.
 */
export default function StatCard({ icon, iconBg, value, label, secondaryLabel, secondaryValue }: StatCardProps) {
    return (
        <div className="col-xxl-3 col-sm-6 d-flex">
            <div className="card flex-fill border-0">
                <div className="card-body">
                    <div className="d-flex align-items-center">
                        <div className={`avatar avatar-xl me-2 d-flex align-items-center justify-content-center rounded-circle ${iconBg}`}>
                            <i className={`ti ${icon} fs-24`} aria-hidden="true" />
                        </div>
                        <div className="overflow-hidden flex-fill">
                            <h2 className="stat-counter">{value}</h2>
                            <p className="text-gray">{label}</p>
                        </div>
                    </div>
                    {secondaryLabel && (
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
