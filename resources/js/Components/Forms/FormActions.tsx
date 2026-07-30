interface FormActionsProps {
    onCancel: () => void;
    processing: boolean;
    submitLabel?: string;
    processingLabel?: string;
}

/** Cancel/Save button pair matching every Settings/TypesDepenses modal footer exactly. */
export default function FormActions({
    onCancel,
    processing,
    submitLabel = 'Enregistrer',
    processingLabel = 'Enregistrement…',
}: FormActionsProps) {
    return (
        <>
            <button type="button" className="btn btn-light" onClick={onCancel} disabled={processing}>
                Annuler
            </button>
            <button type="submit" className="btn btn-primary" disabled={processing}>
                {processing ? (
                    <>
                        <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" />
                        {processingLabel}
                    </>
                ) : (
                    submitLabel
                )}
            </button>
        </>
    );
}
