import { useEffect } from 'react';

export type ToastVariant = 'success' | 'error' | 'warning' | 'info';

export interface ToastItem {
    id: string;
    variant: ToastVariant;
    message: string;
}

interface ToastProps extends ToastItem {
    onDismiss: (id: string) => void;
}

const VARIANT_CONFIG: Record<ToastVariant, { icon: string; bg: string }> = {
    success: { icon: 'fa-circle-check', bg: 'text-bg-success' },
    error: { icon: 'fa-circle-exclamation', bg: 'text-bg-danger' },
    warning: { icon: 'fa-triangle-exclamation', bg: 'text-bg-warning' },
    info: { icon: 'fa-circle-info', bg: 'text-bg-info' },
};

const AUTO_DISMISS_MS = 6000;

/**
 * Bootstrap 5 `.toast` markup/classes only — no bootstrap.bundle.js, no
 * data-bs-* attributes. React owns show/auto-dismiss/close, same pattern as
 * Modal.tsx (docs/bootstrap-react-integration-decision.md). Never the
 * vendored plugins/toastr/toastr.js — that's a jQuery plugin, forbidden by
 * CLAUDE.md §6.
 */
export default function Toast({ id, variant, message, onDismiss }: ToastProps) {
    const { icon, bg } = VARIANT_CONFIG[variant];

    useEffect(() => {
        const timer = window.setTimeout(() => onDismiss(id), AUTO_DISMISS_MS);

        return () => window.clearTimeout(timer);
    }, [id, onDismiss]);

    return (
        <div className={`toast show align-items-center border-0 ${bg}`} role="alert" aria-live="assertive" aria-atomic="true">
            <div className="d-flex">
                <div className="toast-body d-flex align-items-center gap-2">
                    <i className={`fa ${icon} fs-18`} />
                    <span>{message}</span>
                </div>
                <button
                    type="button"
                    className="btn-close btn-close-white me-2 m-auto"
                    aria-label="Fermer"
                    onClick={() => onDismiss(id)}
                />
            </div>
        </div>
    );
}
