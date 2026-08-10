import { useEffect, useRef, useState } from 'react';
import FormError from '@/Components/Forms/FormError';
import type { SelectOption } from '@/Types';

interface MultiSelectFieldProps {
    id: string;
    label?: string;
    options: SelectOption[];
    placeholder?: string;
    error?: string;
    required?: boolean;
    disabled?: boolean;
    /** Selected values as strings (option.value is coerced with String()). */
    values: string[];
    onChange: (values: string[]) => void;
}

/**
 * Multi-select tag picker in the same Select2 visual language as
 * SelectField — selected options render as removable pill tags in the
 * control, the dropdown shows a checkbox per option, and picking toggles
 * membership instead of closing the list. 100% React-owned (no jQuery
 * Select2 plugin, CLAUDE.md §5/§6).
 */
export default function MultiSelectField({
    id,
    label,
    options,
    placeholder,
    error,
    required,
    disabled = false,
    values,
    onChange,
}: MultiSelectFieldProps) {
    const [open, setOpen] = useState(false);
    const wrapperRef = useRef<HTMLDivElement>(null);

    const selectedSet = new Set(values.map(String));
    const selectedOptions = options.filter((o) => selectedSet.has(String(o.value)));

    function toggle(value: string) {
        if (selectedSet.has(value)) {
            onChange(values.filter((v) => String(v) !== value));
        } else {
            onChange([...values, value]);
        }
    }

    function remove(value: string, event: React.MouseEvent) {
        event.stopPropagation();
        onChange(values.filter((v) => String(v) !== value));
    }

    useEffect(() => {
        if (!open) {
            return;
        }

        function handleOutside(event: MouseEvent) {
            if (wrapperRef.current && !wrapperRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', handleOutside);

        return () => document.removeEventListener('mousedown', handleOutside);
    }, [open]);

    function handleKeyDown(event: React.KeyboardEvent) {
        if (event.key === 'Escape') {
            event.stopPropagation();
            setOpen(false);
        }
    }

    return (
        <div className={label ? 'mb-3' : ''}>
            {label && (
                <label className="form-label" htmlFor={id}>
                    {label}
                    {required && <span className="text-danger ms-1">*</span>}
                </label>
            )}
            <div className="position-relative" ref={wrapperRef} onKeyDown={handleKeyDown}>
                <span
                    className={`select2 select2-container select2-container--default select2-container--multiple${open ? ' select2-container--open select2-container--focus' : ''}`}
                    style={{ width: '100%', display: 'block' }}
                >
                    <span className="selection" style={{ display: 'block' }}>
                        <button
                            type="button"
                            id={id}
                            className={`select2-selection select2-selection--multiple w-100 text-start d-flex align-items-center flex-wrap${error ? ' gls-select2-invalid' : ''}`}
                            style={{ display: 'flex', minHeight: 44, padding: '4px 8px', gap: 4 }}
                            role="listbox"
                            aria-multiselectable="true"
                            aria-expanded={open}
                            aria-invalid={error ? true : undefined}
                            aria-describedby={error ? `${id}-error` : undefined}
                            disabled={disabled}
                            onClick={() => {
                                if (!disabled) {
                                    setOpen((o) => !o);
                                }
                            }}
                        >
                            {selectedOptions.length === 0 && (
                                <span className="select2-selection__placeholder text-muted">
                                    {placeholder ?? 'Choisir…'}
                                </span>
                            )}
                            {selectedOptions.map((option) => (
                                <span
                                    key={option.value}
                                    className="badge bg-primary d-inline-flex align-items-center fw-normal fs-13"
                                >
                                    {option.label}
                                    <i
                                        role="button"
                                        aria-label={`Retirer ${option.label}`}
                                        className="ti ti-x ms-1"
                                        onClick={(event) => remove(String(option.value), event)}
                                    />
                                </span>
                            ))}
                            <span className="select2-selection__arrow ms-auto" role="presentation">
                                <b role="presentation" />
                            </span>
                        </button>
                    </span>
                </span>

                {open && (
                    <span
                        className="select2-container select2-container--default select2-container--open"
                        style={{ position: 'absolute', top: '100%', left: 0, width: '100%', zIndex: 1070 }}
                    >
                        <span className="select2-dropdown select2-dropdown--below" style={{ position: 'static', width: '100%' }}>
                            <span className="select2-results">
                                <ul className="select2-results__options" role="listbox" style={{ maxHeight: 220, overflowY: 'auto' }}>
                                    {options.length === 0 && (
                                        <li className="select2-results__option select2-results__message">Aucun résultat</li>
                                    )}
                                    {options.map((option) => {
                                        const value = String(option.value);
                                        const checked = selectedSet.has(value);

                                        return (
                                            <li
                                                key={option.value}
                                                className="select2-results__option d-flex align-items-center"
                                                role="option"
                                                aria-selected={checked}
                                                onMouseDown={(e) => {
                                                    e.preventDefault();
                                                    toggle(value);
                                                }}
                                            >
                                                <input
                                                    type="checkbox"
                                                    className="form-check-input me-2"
                                                    checked={checked}
                                                    readOnly
                                                    tabIndex={-1}
                                                />
                                                {option.label}
                                            </li>
                                        );
                                    })}
                                </ul>
                            </span>
                        </span>
                    </span>
                )}
            </div>
            {error && (
                <div id={`${id}-error`}>
                    <FormError message={error} />
                </div>
            )}
        </div>
    );
}
