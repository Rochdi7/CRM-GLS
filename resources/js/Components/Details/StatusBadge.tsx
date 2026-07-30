interface StatusBadgeProps {
    label: string;
    variant?: 'info' | 'success' | 'warning' | 'danger' | 'secondary';
}

/** Matches the `.badge.badge-soft-*` convention used across every existing detail Blade view. */
export default function StatusBadge({ label, variant = 'info' }: StatusBadgeProps) {
    return <span className={`badge badge-soft-${variant}`}>{label}</span>;
}
