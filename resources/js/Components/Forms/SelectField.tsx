import type { SelectHTMLAttributes } from 'react';
import FormError from '@/Components/Forms/FormError';
import type { SelectOption } from '@/Types';

interface SelectFieldProps extends Omit<SelectHTMLAttributes<HTMLSelectElement>, 'id'> {
    id: string;
    label: string;
    options: SelectOption[];
    placeholder?: string;
    error?: string;
    required?: boolean;
}

/**
 * Plain native <select> styled like the theme's .form-select — the standard
 * dropdown for every React form (CLAUDE.md §5: never Select2 or any jQuery
 * plugin; no jQuery/Select2 assets load on any backoffice page). If a page
 * ever genuinely needs async/searchable options, that means a new
 * React-native combobox component — not a jQuery bridge.
 */
export default function SelectField({
    id,
    label,
    options,
    placeholder,
    error,
    required,
    className,
    ...selectProps
}: SelectFieldProps) {
    return (
        <div className="mb-3">
            <label className="form-label" htmlFor={id}>
                {label}
                {required && <span className="text-danger ms-1">*</span>}
            </label>
            <select
                id={id}
                className={`form-select${error ? ' is-invalid' : ''}${className ? ` ${className}` : ''}`}
                required={required}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? `${id}-error` : undefined}
                {...selectProps}
            >
                {placeholder && <option value="">{placeholder}</option>}
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            {error && (
                <div id={`${id}-error`}>
                    <FormError message={error} />
                </div>
            )}
        </div>
    );
}
