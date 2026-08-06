import { useEffect, useRef, useState } from 'react';
import type { ChangeEventHandler } from 'react';
import FormError from '@/Components/Forms/FormError';
import type { SelectOption } from '@/Types';

interface SelectFieldProps {
    id: string;
    /** Omit for a bare control (e.g. PhoneField country picker) — no label row, no outer margin. */
    label?: string;
    options: SelectOption[];
    placeholder?: string;
    error?: string;
    required?: boolean;
    disabled?: boolean;
    value?: string | number;
    onChange?: ChangeEventHandler<HTMLSelectElement>;
}

/**
 * The app's standard dropdown, rendered in the theme's Select2 style
 * (form-select2.blade.php "Select With Placeholder" reference) — but 100%
 * React-native: only select2.min.css is loaded (app.blade.php), never the
 * jQuery plugin (CLAUDE.md §5/§6). Same API as the previous native-select
 * version (onChange receives an event-like object exposing target.value).
 * No search box, matching the add-student.blade.php selects (product
 * decision); keyboard navigation (arrows/Enter/Escape) still works.
 */
export default function SelectField({
    id,
    label,
    options,
    placeholder,
    error,
    required,
    disabled = false,
    value = '',
    onChange,
}: SelectFieldProps) {
    const [open, setOpen] = useState(false);
    const [highlight, setHighlight] = useState(0);
    const wrapperRef = useRef<HTMLDivElement>(null);

    const selected = options.find((o) => String(o.value) === String(value));

    function emit(next: string) {
        onChange?.({ target: { value: next } } as unknown as React.ChangeEvent<HTMLSelectElement>);
    }

    function choose(option: SelectOption | null) {
        emit(option === null ? '' : String(option.value));
        setOpen(false);
    }

    useEffect(() => {
        if (!open) {
            return;
        }

        setHighlight(0);

        function handleOutside(event: MouseEvent) {
            if (wrapperRef.current && !wrapperRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', handleOutside);

        return () => document.removeEventListener('mousedown', handleOutside);
    }, [open]);

    function handleKeyDown(event: React.KeyboardEvent) {
        if (!open) {
            return;
        }

        if (event.key === 'Escape') {
            event.stopPropagation();
            setOpen(false);
        } else if (event.key === 'ArrowDown') {
            event.preventDefault();
            setHighlight((h) => Math.min(h + 1, options.length - 1));
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setHighlight((h) => Math.max(h - 1, 0));
        } else if (event.key === 'Enter') {
            event.preventDefault();
            if (options[highlight]) {
                choose(options[highlight]);
            }
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
                    className={`select2 select2-container select2-container--default${open ? ' select2-container--open select2-container--focus' : ''}`}
                    style={{ width: '100%', display: 'block' }}
                >
                    <span className="selection" style={{ display: 'block' }}>
                        <button
                            type="button"
                            id={id}
                            className={`select2-selection select2-selection--single w-100 text-start${error ? ' gls-select2-invalid' : ''}`}
                            style={{ display: 'block' }}
                            role="combobox"
                            aria-expanded={open}
                            aria-haspopup="listbox"
                            aria-invalid={error ? true : undefined}
                            aria-describedby={error ? `${id}-error` : undefined}
                            disabled={disabled}
                            onClick={() => {
                                if (!disabled) {
                                    setOpen((o) => !o);
                                }
                            }}
                        >
                            <span className="select2-selection__rendered">
                                {selected ? (
                                    selected.label
                                ) : (
                                    <span className="select2-selection__placeholder">{placeholder ?? 'Choisir…'}</span>
                                )}
                            </span>
                            <span className="select2-selection__arrow" role="presentation">
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
                                    {placeholder && (
                                        <li
                                            className="select2-results__option"
                                            role="option"
                                            aria-selected={!selected}
                                            onMouseDown={(e) => {
                                                e.preventDefault();
                                                choose(null);
                                            }}
                                        >
                                            {placeholder}
                                        </li>
                                    )}
                                    {options.length === 0 && (
                                        <li className="select2-results__option select2-results__message">Aucun résultat</li>
                                    )}
                                    {options.map((option, index) => (
                                        <li
                                            key={option.value}
                                            className={`select2-results__option${index === highlight ? ' select2-results__option--highlighted' : ''}`}
                                            role="option"
                                            aria-selected={selected ? String(option.value) === String(selected.value) : false}
                                            onMouseEnter={() => setHighlight(index)}
                                            onMouseDown={(e) => {
                                                e.preventDefault();
                                                choose(option);
                                            }}
                                        >
                                            {option.label}
                                        </li>
                                    ))}
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
