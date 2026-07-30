import type { ButtonHTMLAttributes, PropsWithChildren } from 'react';

interface SubmitButtonProps extends PropsWithChildren, ButtonHTMLAttributes<HTMLButtonElement> {
    processing: boolean;
    processingLabel?: string;
}

/** Matches the spinner pattern used by the existing profile Blade forms (`wire:loading` → React `processing` state). */
export default function SubmitButton({
    processing,
    processingLabel = 'Enregistrement…',
    className = 'btn btn-primary w-100',
    children,
    ...rest
}: SubmitButtonProps) {
    return (
        <button type="submit" className={className} disabled={processing} {...rest}>
            {processing ? (
                <>
                    <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" />
                    {processingLabel}
                </>
            ) : (
                children
            )}
        </button>
    );
}
