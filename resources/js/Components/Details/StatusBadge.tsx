interface StatusBadgeProps {
    label: string;
    variant?: 'info' | 'success' | 'warning' | 'danger' | 'secondary' | 'primary' | 'dark';
    /** Leading status dot, exactly as the PreSkool list pages render it. */
    dot?: boolean;
}

/**
 * PreSkool status badge (`students.html`/`fees-type.html` reference):
 * `badge badge-soft-* d-inline-flex align-items-center` with an optional
 * `fa-circle` dot (Phase 13 UI parity).
 */
export default function StatusBadge({ label, variant = 'info', dot = false }: StatusBadgeProps) {
    return (
        <span className={`badge badge-soft-${variant} d-inline-flex align-items-center`}>
            {dot && <i className="fa fa-circle fs-5 me-1" aria-hidden="true" />}
            {label}
        </span>
    );
}
