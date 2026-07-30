import type { InputHTMLAttributes } from 'react';

interface CheckboxFieldProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'id' | 'type'> {
    id: string;
    label: string;
}

/** Matches the theme's .form-check markup used by every Settings tab modal. */
export default function CheckboxField({ id, label, className, ...inputProps }: CheckboxFieldProps) {
    return (
        <div className="form-check mb-2">
            <input id={id} type="checkbox" className={`form-check-input${className ? ` ${className}` : ''}`} {...inputProps} />
            <label className="form-check-label" htmlFor={id}>
                {label}
            </label>
        </div>
    );
}
