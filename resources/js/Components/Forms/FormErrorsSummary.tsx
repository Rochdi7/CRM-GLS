interface FormErrorsSummaryProps {
    /** A single general error not tied to a specific field — authorization, business-rule refusal, delete constraint. */
    message?: string;
}

/**
 * General-error banner for a modal/form — authorization failures,
 * business-rule refusals ("this record is still in use"), or unexpected
 * failures the backend converted to a safe French message. Field-level
 * errors stay under their own field (FormError); this is only for errors
 * with no single field to attach to.
 */
export default function FormErrorsSummary({ message }: FormErrorsSummaryProps) {
    if (!message) {
        return null;
    }

    return (
        <div className="alert alert-danger mb-3" role="alert">
            {message}
        </div>
    );
}
