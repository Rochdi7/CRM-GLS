import Modal from '@/Components/Modals/Modal';

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
 * Delete-confirmation dialog built on the shared Modal primitive. Renders
 * the record's own label in the warning (never a generic "delete this?")
 * and surfaces the server's own refusal message verbatim (e.g. "This center
 * is still in use and cannot be deleted.") — never a raw SQL/constraint
 * error (CLAUDE.md safe-message rule).
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
        <Modal
            show={show}
            title={title}
            onClose={onCancel}
            processing={processing}
            footer={
                <>
                    <button type="button" className="btn btn-light" onClick={onCancel} disabled={processing}>
                        Annuler
                    </button>
                    <button type="button" className="btn btn-danger" onClick={onConfirm} disabled={processing}>
                        {processing ? (
                            <>
                                <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" />
                                Suppression…
                            </>
                        ) : (
                            'Supprimer'
                        )}
                    </button>
                </>
            }
        >
            <p className="mb-1">{message}</p>
            <p className="fw-medium mb-0">{recordLabel}</p>
            {error && (
                <div className="alert alert-danger mt-3 mb-0" role="alert">
                    {error}
                </div>
            )}
        </Modal>
    );
}
