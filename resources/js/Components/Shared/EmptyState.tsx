import type { PropsWithChildren } from 'react';

interface EmptyStateProps extends PropsWithChildren {
    title: string;
    message?: string;
    icon?: string;
}

/** Markup matches components/backoffice/ui/empty-state.blade.php exactly. */
export default function EmptyState({ title, message, icon = 'fa fa-database', children }: EmptyStateProps) {
    return (
        <div className="text-center py-5">
            <span className="avatar avatar-xl bg-light rounded-circle mb-3 d-inline-flex align-items-center justify-content-center">
                <i className={`${icon} fs-24 text-muted`} />
            </span>
            <h5 className="mb-1">{title}</h5>
            {message && <p className="text-muted mb-3">{message}</p>}
            {children}
        </div>
    );
}
