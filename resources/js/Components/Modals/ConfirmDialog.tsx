import Modal from '@/Components/Modals/Modal';
import { t } from '@/Lib/i18n';

interface ConfirmDialogProps {
    show: boolean;
    title: string;
    /** The record label shown in the warning, e.g. the center/room/fee name. */
    recordLabel: string;
    message: string;
    error?: string;
    processing: boolean;
    onConfirm: () => void;
    onCancel: () => void;
    /** Icon class (without the `fa` prefix) shown above the title. Defaults to the delete mark. */
    icon?: string;
    /** Confirm button style. Defaults to danger (delete/destructive actions). */
    variant?: 'danger' | 'primary';
    /** Confirm button label when idle. Defaults to the delete wording. */
    confirmLabel?: string;
    /** Confirm button label while processing. Defaults to the delete wording. */
    processingLabel?: string;
}

/**
 * Generic confirm dialog in the PreSkool style (students.html #delete-modal
 * — Phase 13 UI parity): headerless centered body with an icon mark, title,
 * message, and centered Cancel/confirm pair. Defaults to the original
 * delete-confirmation wording/icon/color (still every existing destroy
 * caller's exact previous behavior); pass icon/variant/confirmLabel/
 * processingLabel to reuse it for a non-destructive confirmation (e.g. a
 * status change) without ever showing delete-flavored copy for a
 * non-deleting action. Still renders the record's own label (never a
 * generic "delete this?") and surfaces the server's refusal message
 * verbatim — never a raw SQL/constraint error (CLAUDE.md safe-message rule).
 */
export default function ConfirmDialog({
    show,
    title,
    recordLabel,
    message,
    error,
    processing,
    onConfirm,
    onCancel,
    icon = 'fa-trash',
    variant = 'danger',
    confirmLabel,
    processingLabel,
}: ConfirmDialogProps) {
    return (
        <Modal show={show} title={title} onClose={onCancel} processing={processing} hideHeader>
            <div className="text-center">
                <span className="delete-icon">
                    <i className={`fa ${icon}`} aria-hidden="true" />
                </span>
                <h4>{title}</h4>
                <p className="mb-1">{message}</p>
                <p className="fw-medium">{recordLabel}</p>
                {error && (
                    <div className="alert alert-danger text-start" role="alert">
                        {error}
                    </div>
                )}
                <div className="d-flex justify-content-center">
                    <button type="button" className="btn btn-light me-3" onClick={onCancel} disabled={processing}>
                        {t('Cancel')}
                    </button>
                    <button type="button" className={`btn btn-${variant}`} onClick={onConfirm} disabled={processing}>
                        {processing ? (
                            <>
                                <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" />
                                {processingLabel ?? t('Deleting…')}
                            </>
                        ) : (
                            confirmLabel ?? t('Yes, delete')
                        )}
                    </button>
                </div>
            </div>
        </Modal>
    );
}
