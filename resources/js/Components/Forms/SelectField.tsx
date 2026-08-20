import { useEffect, useRef, useState } from 'react';
import type { ChangeEventHandler } from 'react';
import FormError from '@/Components/Forms/FormError';
import { t } from '@/Lib/i18n';
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
    /** Hide the dropdown search box (defaults to shown). */
    searchable?: boolean;
}

/** Accent/case-insensitive normalisation so "eleve" matches "Élève". */
export function normalizeSearch(text: string): string {
    return text
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .trim();
}

/**
 * The app's standard dropdown, rendered in the theme's Select2 style
 * (form-select2.blade.php "Select With Placeholder" reference) — but 100%
 * React-native: only select2.min.css is loaded (app.blade.php), never the
 * jQuery plugin (CLAUDE.md §5/§6). Same API as the previous native-select
 * version (onChange receives an event-like object exposing target.value).
 *
 * The dropdown carries a real `<input type="search">` so the filter text can be
 * pasted (Ctrl+V, right-click → Coller, mobile paste), selected and copied —
 * the previous keydown-buffer on the button could not support any of that.
 * Keyboard navigation (arrows/Enter/Escape/Tab) still works.
 */
/** Rough dropdown height (search box + results list) — used to decide whether to flip upward. */
const PANEL_MAX_HEIGHT = 320;

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
    searchable = true,
}: SelectFieldProps) {
    const [open, setOpen] = useState(false);
    const [highlight, setHighlight] = useState(0);
    const [query, setQuery] = useState('');
    const wrapperRef = useRef<HTMLDivElement>(null);
    const searchRef = useRef<HTMLInputElement>(null);
    const buttonRef = useRef<HTMLButtonElement>(null);

    // The dropdown is `position: fixed` rather than absolute: inside a
    // `.table-responsive` (overflow-x: auto) or any other scroll container an
    // absolutely-positioned panel is clipped at the container's edge — the bug
    // seen on the group/inscription "Frais" tables, where the Classification
    // dropdown was cut off and slid under the following rows. Fixed
    // positioning anchored to the button's own bounding rect escapes every
    // scroll container (same fix as DateField.tsx / RowActions.tsx).
    const [panelPos, setPanelPos] = useState<{ top: number; left: number; width: number; maxHeight: number } | null>(null);

    function measurePanel() {
        const button = buttonRef.current;
        if (!button) {
            return;
        }
        const rect = button.getBoundingClientRect();

        // Flip above the control when there isn't room below.
        const openUpward = rect.bottom + PANEL_MAX_HEIGHT > window.innerHeight && rect.top > PANEL_MAX_HEIGHT;
        const height = Math.min(PANEL_MAX_HEIGHT, openUpward ? rect.top - 8 : window.innerHeight - rect.bottom - 8);

        setPanelPos({
            top: openUpward ? rect.top - height - 4 : rect.bottom + 4,
            left: Math.max(8, Math.min(rect.left, window.innerWidth - rect.width - 8)),
            width: rect.width,
            maxHeight: height,
        });
    }

    const selected = options.find((o) => String(o.value) === String(value));
    const needle = normalizeSearch(query);
    const shown = needle === '' ? options : options.filter((o) => normalizeSearch(o.label).includes(needle));

    function emit(next: string) {
        onChange?.({ target: { value: next } } as unknown as React.ChangeEvent<HTMLSelectElement>);
    }

    function close(focusButton = false) {
        setOpen(false);
        setQuery('');

        if (focusButton) {
            buttonRef.current?.focus();
        }
    }

    function choose(option: SelectOption | null) {
        emit(option === null ? '' : String(option.value));
        close(true);
    }

    useEffect(() => {
        if (!open) {
            return;
        }

        setHighlight(0);
        searchRef.current?.focus();

        function handleOutside(event: MouseEvent) {
            if (wrapperRef.current && !wrapperRef.current.contains(event.target as Node)) {
                setOpen(false);
                setQuery('');
            }
        }

        // A fixed panel doesn't follow its anchor, so re-measure whenever
        // anything scrolls (capture phase catches the table's own scroller)
        // or the viewport resizes.
        function reposition() {
            measurePanel();
        }

        document.addEventListener('mousedown', handleOutside);
        window.addEventListener('scroll', reposition, true);
        window.addEventListener('resize', reposition);

        return () => {
            document.removeEventListener('mousedown', handleOutside);
            window.removeEventListener('scroll', reposition, true);
            window.removeEventListener('resize', reposition);
        };
    }, [open]);

    function handleKeyDown(event: React.KeyboardEvent) {
        if (!open) {
            if (!disabled && (event.key === 'ArrowDown' || event.key === 'Enter')) {
                event.preventDefault();
                measurePanel();
                setOpen(true);
            }

            return;
        }

        if (event.key === 'Escape') {
            event.stopPropagation();
            close(true);
        } else if (event.key === 'ArrowDown') {
            event.preventDefault();
            setHighlight((h) => Math.min(h + 1, shown.length - 1));
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setHighlight((h) => Math.max(h - 1, 0));
        } else if (event.key === 'Enter') {
            event.preventDefault();
            if (shown[highlight]) {
                choose(shown[highlight]);
            }
        } else if (event.key === 'Tab') {
            close();
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
                            ref={buttonRef}
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
                                    if (!open) {
                                        measurePanel();
                                    }
                                    setOpen((o) => !o);
                                }
                            }}
                        >
                            <span className="select2-selection__rendered">
                                {selected ? (
                                    selected.icon ? (
                                        <span className="d-inline-flex align-items-center gap-2">
                                            {selected.icon}
                                            {selected.shortLabel ?? selected.label}
                                        </span>
                                    ) : (
                                        selected.shortLabel ?? selected.label
                                    )
                                ) : (
                                    <span className="select2-selection__placeholder">{placeholder ?? t('Choose…')}</span>
                                )}
                            </span>
                            <span className="select2-selection__arrow" role="presentation">
                                <b role="presentation" />
                            </span>
                        </button>
                    </span>
                </span>

                {open && panelPos && (
                    <span
                        className="select2-container select2-container--default select2-container--open gls-select2-panel"
                        style={{
                            position: 'fixed',
                            top: panelPos.top,
                            left: panelPos.left,
                            width: panelPos.width,
                            maxHeight: panelPos.maxHeight,
                            zIndex: 1070,
                        }}
                    >
                        {/*
                          * `position: static` inline is not enough here: the plugin stylesheet
                          * ships `.select2-dropdown { position: absolute; left: -100000px;
                          * width: 100% }`, which — once the outer container became `fixed` —
                          * blew the panel out to viewport width and detached it from its
                          * trigger. `.gls-select2-panel > .select2-dropdown` in app.css pins
                          * it back to the measured anchor box.
                          */}
                        <span className="select2-dropdown select2-dropdown--below">
                            {searchable && (
                                <span className="gls-select2-search">
                                    <span className="gls-select2-search__wrap">
                                        <i className="ti ti-search gls-select2-search__icon" aria-hidden="true" />
                                        <input
                                            ref={searchRef}
                                            type="search"
                                            className="gls-select2-search__field"
                                            value={query}
                                            placeholder={t('Search…')}
                                            aria-label={t('Search…')}
                                            autoComplete="off"
                                            autoCorrect="off"
                                            autoCapitalize="off"
                                            spellCheck={false}
                                            onChange={(event) => {
                                                setQuery(event.target.value);
                                                setHighlight(0);
                                            }}
                                        />
                                        {query !== '' && (
                                            <button
                                                type="button"
                                                className="gls-select2-search__clear"
                                                aria-label={t('Clear')}
                                                onMouseDown={(e) => e.preventDefault()}
                                                onClick={() => {
                                                    setQuery('');
                                                    setHighlight(0);
                                                    searchRef.current?.focus();
                                                }}
                                            >
                                                <i className="ti ti-x" aria-hidden="true" />
                                            </button>
                                        )}
                                    </span>
                                </span>
                            )}
                            <span className="select2-results">
                                <ul
                                    className="select2-results__options"
                                    role="listbox"
                                    style={{ maxHeight: panelPos.maxHeight - (searchable ? 56 : 8), overflowY: 'auto' }}
                                >
                                    {placeholder && needle === '' && (
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
                                    {shown.length === 0 && (
                                        <li className="select2-results__option select2-results__message">{t('No results')}</li>
                                    )}
                                    {shown.map((option, index) => (
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
                                            {option.icon ? (
                                                <span className="d-inline-flex align-items-center gap-2">
                                                    {option.icon}
                                                    {option.label}
                                                </span>
                                            ) : (
                                                option.label
                                            )}
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
