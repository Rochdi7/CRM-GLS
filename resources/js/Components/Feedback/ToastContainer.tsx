import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import Toast, { type ToastItem, type ToastVariant } from './Toast';
import { t } from '@/Lib/i18n';
import type { FlashMessages as FlashMessagesType } from '@/Types';

interface ToastContainerProps {
    flash: FlashMessagesType;
}

let nextId = 0;

/**
 * Single mount point (BackofficeLayout) turning the `flash` shared prop into
 * toasts — replaces the old inline-banner FlashMessages component. Also
 * listens globally for Inertia's `httpException` event (unhandled 500s,
 * 403s) so those stop failing silently, per CLAUDE.md's "never trust the
 * client for anything security-relevant" — this is UI feedback only, never
 * a substitute for the server-side check. `flash.status` is still read as a
 * `success` toast defensively — it's Laravel's password-broker convention,
 * reserved for guest auth pages (rendered there via AuthStatus, which don't
 * use this layout) — but nothing under BackofficeLayout should flash it
 * anymore now that every CRUD controller flashes `success` directly.
 */
export default function ToastContainer({ flash }: ToastContainerProps) {
    const [toasts, setToasts] = useState<ToastItem[]>([]);
    const lastFlashRef = useRef<FlashMessagesType | null>(null);

    function pushToast(variant: ToastVariant, message: string) {
        nextId += 1;
        const id = `toast-${nextId}`;
        setToasts((prev) => [...prev, { id, variant, message }]);
    }

    function dismissToast(id: string) {
        setToasts((prev) => prev.filter((toast) => toast.id !== id));
    }

    useEffect(() => {
        const previous = lastFlashRef.current;
        lastFlashRef.current = flash;

        const messages: Array<{ variant: ToastVariant; key: keyof FlashMessagesType; value: string | null | undefined }> = [
            { variant: 'success', key: 'success', value: flash.success },
            { variant: 'error', key: 'error', value: flash.error },
            { variant: 'warning', key: 'warning', value: flash.warning },
            { variant: 'info', key: 'info', value: flash.info },
            // Laravel's password-broker flash key, reused by ~11 CRUD
            // controllers as a plain success message (see the controller
            // audit) — treated as `success` here.
            { variant: 'success', key: 'status', value: flash.status },
        ];

        for (const { key, variant, value } of messages) {
            if (value && value !== previous?.[key]) {
                pushToast(variant, value);
            }
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [flash.success, flash.error, flash.warning, flash.info, flash.status]);

    useEffect(() => {
        // Field-level validation errors (422) are already shown inline next
        // to each form field via useForm().errors — Inertia's `error` event
        // fires for exactly those, so it is NOT hooked here to avoid
        // double-reporting. `httpException` is the event for everything
        // else (500s, 403s, any non-precognition-validation failure).
        return router.on('httpException', (event) => {
            const status = event.detail.response.status;

            if (status === 403) {
                pushToast('error', t('You are not authorized to perform this action.'));

                return;
            }

            pushToast('error', t('An unexpected error occurred. Please try again.'));
        });
    }, []);

    if (toasts.length === 0) {
        return null;
    }

    return (
        <div className="toast-container position-fixed top-0 end-0 p-3" style={{ zIndex: 1080 }}>
            {toasts.map((toast) => (
                <Toast key={toast.id} {...toast} onDismiss={dismissToast} />
            ))}
        </div>
    );
}
