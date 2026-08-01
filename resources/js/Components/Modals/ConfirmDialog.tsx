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
}

/**
 * Delete-confirmation dialog in the PreSkool style (students.html
 * #delete-modal — Phase 13 UI parity): headerless centered body with the
 * `delete-icon` + `ti-trash-x` mark, title, message, and centered
 * Cancel/`btn-danger` pair. Still renders the record's own label (never a
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
}: ConfirmDialogProps) {
    return (
        <Modal show={show} title={title} onClose={onCancel} processing={processing} hideHeader>
            <div className="text-center">
                <span className="delete-icon">
                    <i className="ti ti-trash-x" aria-hidden="true" />
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
                    <button type="button" className="btn btn-danger" onClick={onConfirm} disabled={processing}>
                        {processing ? (
                            <>
                                <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" />
                                {t('Deleting…')}
                            </>
                        ) : (
                            t('Yes, delete')
                        )}
                    </button>
                </div>
            </div>
        </Modal>
    );
}
