import { useEffect, useRef } from 'react';
import type { PropsWithChildren, ReactNode } from 'react';

interface ModalProps extends PropsWithChildren {
    show: boolean;
    title: string;
    onClose: () => void;
    /** Disables Escape/backdrop close while a request is in flight (e.g. save/delete). */
    processing?: boolean;
    size?: 'default' | 'lg';
    footer?: ReactNode;
    /**
     * Headerless variant — the PreSkool delete-confirmation modal
     * (students.html #delete-modal) has no .modal-header; the body carries
     * icon+title+buttons itself. `title` still labels the dialog for AT.
     */
    hideHeader?: boolean;
}

/**
 * React-controlled modal — visuals match the theme's Bootstrap 5 modal
 * markup exactly (.modal/.modal-dialog/.modal-content classes,
 * .modal-backdrop), but React owns every behavior: open/close state,
 * Escape, backdrop click, focus trap/restore, body-scroll lock. No
 * bootstrap.bundle.js, no data-bs-toggle/data-bs-dismiss, no jQuery
 * (docs/bootstrap-react-integration-decision.md's Phase 6/7 revisit —
 * hand-rolled Modal chosen over react-bootstrap for Phase 7's first
 * add/edit modals).
 *
 * Adapted from the existing Alpine-driven modal markup already used by
 * the Employees/Users Livewire pages
 * (resources/views/livewire/backoffice/{employees,users}/*.blade.php) —
 * same `.modal-dialog-centered` + `.modal-backdrop` visual structure,
 * translated from `x-data="{ show: @entangle(...) }"` to React state. The
 * theme's own React modal demo (theme-reference/preskool-react/src/
 * feature-module/uiInterface/base-ui/modals.tsx) was inspected and
 * rejected — it is lorem-ipsum demo content wired to
 * data-bs-toggle/data-bs-target and react-router-dom, not a real
 * controlled component (docs/react-theme-file-map.md).
 */
export default function Modal({ show, title, onClose, processing = false, size = 'default', children, footer, hideHeader = false }: ModalProps) {
    const dialogRef = useRef<HTMLDivElement>(null);
    const triggerRef = useRef<Element | null>(null);

    useEffect(() => {
        if (!show) {
            return;
        }

        triggerRef.current = document.activeElement;
        document.body.style.overflow = 'hidden';

        const dialog = dialogRef.current;
        const focusable = dialog?.querySelectorAll<HTMLElement>(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
        );
        focusable?.[0]?.focus();

        function handleKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape' && !processing) {
                onClose();

                return;
            }

            if (event.key !== 'Tab' || !focusable || focusable.length === 0) {
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('keydown', handleKeyDown);
            document.body.style.overflow = '';
            (triggerRef.current as HTMLElement | null)?.focus?.();
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show, processing]);

    if (!show) {
        return null;
    }

    function handleBackdropClick() {
        if (!processing) {
            onClose();
        }
    }

    return (
        <>
            <div
                className="modal fade show"
                tabIndex={-1}
                role="dialog"
                aria-modal="true"
                aria-labelledby={hideHeader ? undefined : 'modal-title'}
                aria-label={hideHeader ? title : undefined}
                style={{ display: 'block', zIndex: 1060 }}
                onMouseDown={(event) => {
                    if (event.target === event.currentTarget) {
                        handleBackdropClick();
                    }
                }}
            >
                <div
                    ref={dialogRef}
                    className={`modal-dialog modal-dialog-centered${size === 'lg' ? ' modal-lg' : ''}`}
                >
                    <div className="modal-content">
                        {!hideHeader && (
                            <div className="modal-header">
                                <h4 className="modal-title" id="modal-title">
                                    {title}
                                </h4>
                                <button
                                    type="button"
                                    className="btn-close custom-btn-close"
                                    onClick={onClose}
                                    disabled={processing}
                                    aria-label="Fermer"
                                >
                                    <i className="ti ti-x" />
                                </button>
                            </div>
                        )}
                        <div className="modal-body">{children}</div>
                        {footer && <div className="modal-footer">{footer}</div>}
                    </div>
                </div>
            </div>
            <div className="modal-backdrop fade show" style={{ zIndex: 1055 }} />
        </>
    );
}
