import type { ReactNode } from 'react';

interface StatCardProps {
    icon: string;
    iconBg: string;
    value: ReactNode;
    label: string;
    secondaryLabel?: string;
    secondaryValue?: ReactNode;
}

/** Markup matches the card blocks in resources/views/livewire/backoffice/dashboard/dashboard-stats.blade.php exactly. */
export default function StatCard({ icon, iconBg, value, label, secondaryLabel, secondaryValue }: StatCardProps) {
    return (
        <div className="col-xxl-3 col-sm-6 d-flex">
            <div className="card flex-fill border-0">
                <div className="card-body">
                    <div className="d-flex align-items-center">
                        <div className={`avatar avatar-xl me-2 p-1 ${iconBg}`}>
                            <img src={icon} alt="" />
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
