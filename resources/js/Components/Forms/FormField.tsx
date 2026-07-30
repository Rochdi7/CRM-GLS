import type { InputHTMLAttributes } from 'react';
import FormError from '@/Components/Forms/FormError';

interface FormFieldProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'id'> {
    id: string;
    label: string;
    error?: string;
    icon?: string;
    required?: boolean;
}

/** Matches the `.input-icon` + label markup used by the existing auth Blade pages. */
export default function FormField({ id, label, error, icon, required, className, ...inputProps }: FormFieldProps) {
    return (
        <div className="mb-3">
            <label className="form-label" htmlFor={id}>
                {label}
            </label>
            <div className={icon ? 'input-icon mb-3 position-relative' : undefined}>
                {icon && (
                    <span className="input-icon-addon">
                        <i className={icon} />
                    </span>
                )}
                <input
                    id={id}
                    className={`form-control${error ? ' is-invalid' : ''}${className ? ` ${className}` : ''}`}
                    required={required}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? `${id}-error` : undefined}
                    {...inputProps}
                />
            </div>
            {error && (
                <div id={`${id}-error`}>
                    <FormError message={error} />
                </div>
            )}
        </div>
    );
}
