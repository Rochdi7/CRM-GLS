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

const VARIANT_CONFIG: Record<ToastVariant, { icon: string; iconClass: string; barClass: string }> = {
    success: { icon: 'ti-circle-check', iconClass: 'gls-toast-icon-success', barClass: 'bg-success' },
    error: { icon: 'ti-alert-circle', iconClass: 'gls-toast-icon-danger', barClass: 'bg-danger' },
    warning: { icon: 'ti-alert-triangle', iconClass: 'gls-toast-icon-warning', barClass: 'bg-warning' },
    info: { icon: 'ti-info-circle', iconClass: 'gls-toast-icon-info', barClass: 'bg-info' },
};

const AUTO_DISMISS_MS = 5000;

/**
 * Bootstrap 5 `.toast` markup/classes only — no bootstrap.bundle.js, no
 * data-bs-* attributes. React owns show/auto-dismiss/close, same pattern as
 * Modal.tsx (docs/bootstrap-react-integration-decision.md). Never the
 * vendored plugins/toastr/toastr.js — that's a jQuery plugin, forbidden by
 * CLAUDE.md §6. White-card style (colored icon badge, dark text) rather than
 * a solid-color band — better contrast for longer messages and matches the
 * PreSkool card/alert look elsewhere. The countdown bar is a pure-CSS
 * width transition (`gls-toast-bar` in app.css) driven by an inline
 * `animationDuration`, not a JS-ticked progress value — one less timer to
 * keep in sync with the actual dismiss timeout.
 */
export default function Toast({ id, variant, message, onDismiss }: ToastProps) {
    const { icon, iconClass, barClass } = VARIANT_CONFIG[variant];

    useEffect(() => {
        const timer = window.setTimeout(() => onDismiss(id), AUTO_DISMISS_MS);

        return () => window.clearTimeout(timer);
    }, [id, onDismiss]);

    return (
        <div className="toast show gls-toast border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div className="d-flex align-items-center">
                <div className={`gls-toast-icon ${iconClass}`}>
                    <i className={`ti ${icon}`} />
                </div>
                <div className="toast-body flex-grow-1">{message}</div>
                <button
                    type="button"
                    className="btn-close me-2"
                    aria-label="Fermer"
                    onClick={() => onDismiss(id)}
                />
            </div>
            <div className="gls-toast-progress">
                <div
                    className={`gls-toast-progress-bar ${barClass}`}
                    style={{ animationDuration: `${AUTO_DISMISS_MS}ms` }}
                />
            </div>
        </div>
    );
}
