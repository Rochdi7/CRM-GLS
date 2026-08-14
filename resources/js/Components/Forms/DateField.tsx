
import { useEffect, useMemo, useRef, useState } from 'react';
import type { ChangeEventHandler } from 'react';
import FormError from '@/Components/Forms/FormError';

interface DateFieldProps {
    id: string;
    /** Omit for a bare control (e.g. FilterDropdown date fields) — no label row, no outer margin. */
    label?: string;
    /** Always 'yyyy-mm-dd' (backend format); displayed as 'dd-mm-yyyy'. */
    value?: string;
    onChange?: ChangeEventHandler<HTMLInputElement>;
    error?: string;
    required?: boolean;
    disabled?: boolean;
    placeholder?: string;
    /** Preferred horizontal alignment: 'right' anchors the panel to the input's right edge instead of its left. Either way the panel is finally clamped into the viewport, so this only decides which edge it lines up with when there's room for both. */
    panelAlign?: 'left' | 'right';
}

const WEEKDAYS = ['lu', 'ma', 'me', 'je', 've', 'sa', 'di'];

function pad(n: number): string {
    return String(n).padStart(2, '0');
}

function toIso(year: number, month: number, day: number): string {
    return `${year}-${pad(month + 1)}-${pad(day)}`;
}

function parseIso(value: string): { year: number; month: number; day: number } | null {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    if (!match) {
        return null;
    }
    const year = Number(match[1]);
    const month = Number(match[2]) - 1;
    const day = Number(match[3]);
    if (month < 0 || month > 11 || day < 1 || day > 31) {
        return null;
    }
    return { year, month, day };
}

/** Parses what the user types — 'dd-mm-yyyy' (also accepts 'dd/mm/yyyy') — into an ISO date, validating real calendar dates (no 31-02). */
function parseDisplay(text: string): string | null {
    const match = /^(\d{1,2})[-/](\d{1,2})[-/](\d{4})$/.exec(text.trim());
    if (!match) {
        return null;
    }
    const day = Number(match[1]);
    const month = Number(match[2]);
    const year = Number(match[3]);
    const date = new Date(year, month - 1, day);
    if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
        return null;
    }
    return toIso(year, month - 1, day);
}

/**
 * Masks free-typed input into 'dd-mm-yyyy' as the user types: strips
 * non-digits, caps each group (2/2/4 digits — day/month can't exceed 31/12),
 * and auto-inserts the dashes. Backspacing across a dash removes it along
 * with the digit before it, so deleting feels natural.
 */
function maskDateInput(raw: string, previous: string): string {
    const deleting = raw.length < previous.length;
    let digits = raw.replace(/\D/g, '').slice(0, 8);

    if (deleting && previous.endsWith('-') && raw === previous.slice(0, -1)) {
        digits = digits.slice(0, -1);
    }

    let day = digits.slice(0, 2);
    let month = digits.slice(2, 4);
    const year = digits.slice(4, 8);

    if (day.length === 2 && Number(day) > 31) {
        day = day[0];
    }
    if (month.length === 2 && Number(month) > 12) {
        month = month[0];
    }

    let out = day;
    if (digits.length >= 3 || (day.length === 2 && !deleting)) {
        out += '-' + month;
    }
    if (digits.length >= 5) {
        out += '-' + year;
    }
    return out;
}

interface GridDay {
    year: number;
    month: number;
    day: number;
    inMonth: boolean;
}

/**
 * The app's standard date picker — 100% React-native, no
 * bootstrap-datetimepicker/jQuery (CLAUDE.md §6). Value protocol is always
 * 'yyyy-mm-dd' in/out (what the backend expects); the read-only input
 * displays 'dd-mm-yyyy'. Same emit pattern as SelectField (event-like
 * object exposing target.value). Calendar panel is styled on the PreSkool
 * card look: white rounded-3 shadowed panel, circled prev/next buttons,
 * bold French month title, Monday-first French weekday header, muted
 * adjacent-month days, #3D5EE1 selected day.
 */
const PANEL_WIDTH = 300;
/** Rough panel height (header + 6 weeks of 32px rows + padding) — used to decide whether to flip upward. */
const PANEL_HEIGHT = 300;

export default function DateField({
    id,
    label,
    value = '',
    onChange,
    error,
    required,
    disabled = false,
    placeholder,
    panelAlign = 'left',
}: DateFieldProps) {
    const [open, setOpen] = useState(false);
    const wrapperRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);

    // The panel is `position: fixed` rather than absolute: inside a
    // `.table-responsive` (overflow-x: auto) an absolutely-positioned panel is
    // clipped at the table's edge — the bug seen on the group/inscription
    // "Frais" tables, where the calendar was cut in half. Fixed positioning
    // anchored to the input's own bounding rect escapes every scroll
    // container (same fix as RowActions.tsx / ContextSwitcher.tsx).
    const [panelPos, setPanelPos] = useState<{ top: number; left: number } | null>(null);

    function measurePanel() {
        const input = inputRef.current;
        if (!input) {
            return;
        }
        const rect = input.getBoundingClientRect();

        // Prefer aligning to the requested edge, then clamp into the viewport
        // so the panel is always fully visible regardless of table scroll.
        let left = panelAlign === 'right' ? rect.right - PANEL_WIDTH : rect.left;
        left = Math.min(left, window.innerWidth - PANEL_WIDTH - 8);
        left = Math.max(8, left);

        // Flip above the input when there isn't room below.
        const openUpward = rect.bottom + PANEL_HEIGHT > window.innerHeight && rect.top > PANEL_HEIGHT;
        const top = openUpward ? rect.top - PANEL_HEIGHT - 4 : rect.bottom + 4;

        setPanelPos({ top, left });
    }

    const parsed = parseIso(value);
    const formatted = parsed ? `${pad(parsed.day)}-${pad(parsed.month + 1)}-${parsed.year}` : '';
    const today = new Date();
    const [viewYear, setViewYear] = useState(() => parsed?.year ?? today.getFullYear());
    const [viewMonth, setViewMonth] = useState(() => parsed?.month ?? today.getMonth());

    // While the user is actively typing, the input shows their own text
    // (which may be incomplete/invalid) instead of the formatted value.
    const [typedText, setTypedText] = useState<string | null>(null);

    function emit(next: string) {
        onChange?.({ target: { value: next } } as unknown as React.ChangeEvent<HTMLInputElement>);
    }

    function openPanel() {
        // Re-sync the view month with the current value each time it opens.
        const current = parseIso(value);
        setViewYear(current?.year ?? new Date().getFullYear());
        setViewMonth(current?.month ?? new Date().getMonth());
        measurePanel();
        setOpen(true);
    }

    function choose(cell: GridDay) {
        emit(toIso(cell.year, cell.month, cell.day));
        setTypedText(null);
        if (!cell.inMonth) {
            setViewYear(cell.year);
            setViewMonth(cell.month);
        }
        setOpen(false);
    }

    function handleTextChange(text: string) {
        const masked = maskDateInput(text, typedText ?? formatted);
        setTypedText(masked);
        const iso = parseDisplay(masked);
        if (iso) {
            emit(iso);
            const next = parseIso(iso)!;
            setViewYear(next.year);
            setViewMonth(next.month);
        }
    }

    function handleTextBlur() {
        // Whatever wasn't a valid date on blur is discarded — the input
        // falls back to reflecting the last valid `value`.
        setTypedText(null);
    }

    function moveMonth(delta: number) {
        const next = new Date(viewYear, viewMonth + delta, 1);
        setViewYear(next.getFullYear());
        setViewMonth(next.getMonth());
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
        if (open && event.key === 'Escape') {
            event.stopPropagation();
            setOpen(false);
        }
    }

    const grid = useMemo<GridDay[]>(() => {
        const first = new Date(viewYear, viewMonth, 1);
        // Monday-first offset: getDay() is 0=Sunday.
        const lead = (first.getDay() + 6) % 7;
        const cells: GridDay[] = [];
        for (let i = 0; i < 42; i += 1) {
            const d = new Date(viewYear, viewMonth, 1 - lead + i);
            cells.push({
                year: d.getFullYear(),
                month: d.getMonth(),
                day: d.getDate(),
                inMonth: d.getMonth() === viewMonth && d.getFullYear() === viewYear,
            });
        }
        return cells;
    }, [viewYear, viewMonth]);

    const monthTitle = new Date(viewYear, viewMonth, 1).toLocaleDateString('fr-FR', {
        month: 'long',
        year: 'numeric',
    });

    const display = typedText ?? formatted;
    const todayIso = toIso(today.getFullYear(), today.getMonth(), today.getDate());

    return (
        <div className={label ? 'mb-3' : ''}>
            {label && (
                <label className="form-label" htmlFor={id}>
                    {label}
                    {required && <span className="text-danger ms-1">*</span>}
                </label>
            )}
            <div className="position-relative" ref={wrapperRef} onKeyDown={handleKeyDown}>
                <input
                    id={id}
                    ref={inputRef}
                    type="text"
                    inputMode="numeric"
                    autoComplete="off"
                    className={`form-control${error ? ' is-invalid' : ''}`}
                    style={{ paddingRight: '2.25rem' }}
                    value={display}
                    placeholder={placeholder ?? 'jj-mm-aaaa'}
                    disabled={disabled}
                    required={required}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? `${id}-error` : undefined}
                    aria-haspopup="dialog"
                    aria-expanded={open}
                    onChange={(event) => handleTextChange(event.target.value)}
                    onBlur={handleTextBlur}
                    onFocus={() => {
                        if (!disabled) {
                            openPanel();
                        }
                    }}
                />
                <i
                    className="ti ti-calendar text-muted"
                    aria-hidden="true"
                    style={{
                        position: 'absolute',
                        right: '0.75rem',
                        top: '50%',
                        transform: 'translateY(-50%)',
                        pointerEvents: 'none',
                    }}
                />
                {open && panelPos && (
                    <div
                        className="bg-white border rounded-3 shadow p-3"
                        role="dialog"
                        aria-label={monthTitle}
                        style={{
                            position: 'fixed',
                            top: panelPos.top,
                            left: panelPos.left,
                            zIndex: 1070,
                            width: PANEL_WIDTH,
                        }}
                    >
                        <div className="d-flex align-items-center justify-content-between mb-2">
                            <button
                                type="button"
                                className="btn btn-outline-light bg-white btn-icon rounded-circle"
                                aria-label="Mois précédent"
                                onClick={() => moveMonth(-1)}
                            >
                                <i className="ti ti-chevron-left" aria-hidden="true" />
                            </button>
                            <span className="fw-bold text-capitalize">{monthTitle}</span>
                            <button
                                type="button"
                                className="btn btn-outline-light bg-white btn-icon rounded-circle"
                                aria-label="Mois suivant"
                                onClick={() => moveMonth(1)}
                            >
                                <i className="ti ti-chevron-right" aria-hidden="true" />
                            </button>
                        </div>
                        <div
                            style={{
                                display: 'grid',
                                gridTemplateColumns: 'repeat(7, 1fr)',
                                gap: 2,
                                textAlign: 'center',
                            }}
                        >
                            {WEEKDAYS.map((day) => (
                                <span key={day} className="text-muted small fw-semibold py-1">
                                    {day}
                                </span>
                            ))}
                            {grid.map((cell) => {
                                const iso = toIso(cell.year, cell.month, cell.day);
                                const isSelected = value === iso;
                                const isToday = iso === todayIso;
                                return (
                                    <button
                                        key={iso}
                                        type="button"
                                        onClick={() => choose(cell)}
                                        aria-pressed={isSelected}
                                        className={`btn btn-sm p-0${!cell.inMonth && !isSelected ? ' text-muted' : ''}`}
                                        style={{
                                            width: '100%',
                                            height: 32,
                                            lineHeight: '32px',
                                            borderRadius: 6,
                                            border: isToday && !isSelected ? '1px solid #3D5EE1' : '1px solid transparent',
                                            backgroundColor: isSelected ? '#3D5EE1' : 'transparent',
                                            color: isSelected ? '#fff' : undefined,
                                        }}
                                    >
                                        {cell.day}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
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
