import type { TextareaHTMLAttributes } from 'react';
import FormError from '@/Components/Forms/FormError';

interface TextareaFieldProps extends Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'id'> {
    id: string;
    label: string;
    error?: string;
    required?: boolean;
}

export default function TextareaField({ id, label, error, required, className, ...textareaProps }: TextareaFieldProps) {
    return (
        <div className="mb-3">
            <label className="form-label" htmlFor={id}>
                {label}
                {required && <span className="text-danger ms-1">*</span>}
            </label>
            <textarea
                id={id}
                className={`form-control${error ? ' is-invalid' : ''}${className ? ` ${className}` : ''}`}
                required={required}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? `${id}-error` : undefined}
                {...textareaProps}
            />
            {error && (
                <div id={`${id}-error`}>
                    <FormError message={error} />
                </div>
            )}
        </div>
    );
}
