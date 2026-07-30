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
 * Plain native <select> styled like the theme's .form-select — used inside
 * modals for a handful of known options (status, center picker). Not
 * Select2: CLAUDE.md's Select2 rule targets jQuery-plugin dropdowns on
 * always-on Blade/Livewire pages; Inertia modals load no jQuery/Select2
 * assets at all (docs/bootstrap-react-integration-decision.md), so a native
 * select is the only option here without introducing a new dependency.
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
