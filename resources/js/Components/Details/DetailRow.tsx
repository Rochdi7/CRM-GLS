import type { ReactNode } from 'react';

interface DetailRowProps {
    label: string;
    value: ReactNode;
    /** Adds the border-top spacer used between grouped rows in the existing Blade cards. */
    borderTop?: boolean;
}

/** Matches the `d-flex justify-content-between` label/value row repeated across every existing detail Blade view. */
export default function DetailRow({ label, value, borderTop = false }: DetailRowProps) {
    return (
        <div className={`d-flex justify-content-between mb-2${borderTop ? ' border-top pt-2' : ''}`}>
            <span className="text-muted">{label}</span>
            <span className="fw-medium text-end ms-2 text-truncate">{value ?? '—'}</span>
        </div>
    );
}
