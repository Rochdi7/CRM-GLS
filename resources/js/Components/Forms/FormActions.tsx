interface FormActionsProps {
    onCancel: () => void;
    processing: boolean;
    submitLabel?: string;
    processingLabel?: string;
    /**
     * REQUIRED whenever FormActions renders in a Modal `footer` slot: the
     * footer is a sibling of the <form> in the modal body, so without an
     * explicit form="<form-id>" association the submit button submits
     * nothing (Phase 12 UX audit — this silently broke saving on the
     * Caisses transfer, Dépenses, Remboursements and Encaissements modals).
     */
    form?: string;
}

/** Cancel/Save button pair matching every Settings/TypesDepenses modal footer exactly. */
export default function FormActions({
    onCancel,
    processing,
    submitLabel = 'Enregistrer',
    processingLabel = 'Enregistrement…',
    form,
}: FormActionsProps) {
    return (
        <>
            <button type="button" className="btn btn-light" onClick={onCancel} disabled={processing}>
                Annuler
            </button>
            <button type="submit" form={form} className="btn btn-primary" disabled={processing}>
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
