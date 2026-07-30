import { useState } from 'react';
import type { FlashMessages as FlashMessagesType } from '@/Types';

interface FlashMessagesProps {
    flash: FlashMessagesType;
}

const VARIANTS: Record<keyof FlashMessagesType, string> = {
    success: 'success',
    error: 'danger',
    warning: 'warning',
    info: 'info',
};

/**
 * Markup matches components/backoffice/ui/alert.blade.php, but dismiss is
 * React-owned state (no data-bs-dismiss / Bootstrap JS dependency) — see
 * docs/bootstrap-react-integration-decision.md. Renders nothing when a
 * message is absent (never an empty alert shell).
 */
export default function FlashMessages({ flash }: FlashMessagesProps) {
    const [dismissed, setDismissed] = useState<Record<string, boolean>>({});

    const entries = (Object.keys(flash) as Array<keyof FlashMessagesType>)
        .map((key) => ({ key, message: flash[key] }))
        .filter((entry) => entry.message && !dismissed[entry.key]);

    if (entries.length === 0) {
        return null;
    }

    return (
        <>
            {entries.map(({ key, message }) => (
                <div
                    key={key}
                    className={`alert alert-${VARIANTS[key]} d-flex align-items-center justify-content-between`}
                    role="alert"
                >
                    <div className="d-flex align-items-center">{message}</div>
                    <button
                        type="button"
                        className="btn-close p-0"
                        aria-label="Fermer"
                        onClick={() => setDismissed((prev) => ({ ...prev, [key]: true }))}
                    >
                        <span>
                            <i className="ti ti-x" />
                        </span>
                    </button>
                </div>
            ))}
        </>
    );
}
