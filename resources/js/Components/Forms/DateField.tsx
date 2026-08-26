
import { useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import type { ChangeEventHandler } from 'react';
import FormError from '@/Components/Forms/FormError';
import { t } from '@/Lib/i18n';

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
    /** Allows emptying the field (shows a clear × button when it holds a value). Defaults to true for optional fields, false when `required`. */
    clearable?: boolean;
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
    // A blank inside the mask means a slot the user hasn't filled yet (e.g.
    // ' 5-02-2026' after clearing the day's first digit). trim() would turn
    // that into a valid-looking '5-02-2026', silently committing a date that
    // was never typed — so reject any value still holding a blank slot.
    if (/\s/.test(text)) {
        return null;
    }
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
 * The mask is a fixed 10-character template `DD-MM-YYYY` over 8 digit slots.
 * Slot i lives at these string offsets (2 and 5 are the literal dashes).
 */
const SLOT_OFFSETS = [0, 1, 3, 4, 6, 7, 8, 9];

/** String offset -> the digit slot the caret sits in front of. */
function offsetToSlot(offset: number): number {
    for (let i = 0; i < SLOT_OFFSETS.length; i += 1) {
        if (SLOT_OFFSETS[i] >= offset) {
            return i;
        }
    }
    return SLOT_OFFSETS.length;
}

/** Renders 8 digit slots (blank = undefined) as 'dd-mm-yyyy', trimmed of trailing blanks. */
function renderSlots(slots: (string | undefined)[]): string {
    let out = '';
    for (let i = 0; i < 8; i += 1) {
        if (i === 2 || i === 4) {
            out += '-';
        }
        out += slots[i] ?? ' ';
    }
    // Trailing blanks (and the dash before them) are not shown, so a partly
    // typed date reads '25-02' rather than '25-02-    '.
    return out.replace(/[\s-]+$/, '');
}

/** Reads 'dd-mm-yyyy' (possibly partial) back into 8 digit slots. */
function textToSlots(text: string): (string | undefined)[] {
    const slots: (string | undefined)[] = new Array(8).fill(undefined);
    for (let i = 0; i < 8; i += 1) {
        const ch = text[SLOT_OFFSETS[i]];
        if (ch && /\d/.test(ch)) {
            slots[i] = ch;
        }
    }
    return slots;
}

interface MaskResult {
    text: string;
    caret: number;
}

/**
 * Masks free-typed input into 'dd-mm-yyyy' using FIXED digit slots, so editing
 * in the middle of the value overwrites the digit under the caret instead of
 * re-flowing every following digit. The old implementation concatenated all
 * digits and re-split them 2/2/4, which meant typing a digit into the month
 * pushed the rest rightwards and mangled the year (25-02-2026 -> 25-02-0267)
 * while the caret jumped to the end.
 *
 * `raw`/`caret` are the browser's post-edit value and caret; `previous` is the
 * masked text before the edit. Returns the new masked text and where the caret
 * should land.
 */
function maskDateInput(raw: string, caret: number, previous: string): MaskResult {
    const prevSlots = textToSlots(previous);

    // Locate the edit by diffing `previous` against `raw` from both ends: the
    // common prefix is what the user left untouched before the caret, the
    // common suffix what they left after it. Everything between was replaced
    // by whatever now sits in raw[prefix..caret]. This handles plain typing,
    // backspace, and selection-replacement uniformly — inferring the operation
    // from raw.length vs previous.length misread a selection replacement as a
    // deletion and silently threw away the typed digits.
    let prefix = 0;
    while (prefix < previous.length && prefix < raw.length && raw[prefix] === previous[prefix] && prefix < caret) {
        prefix += 1;
    }

    let suffix = 0;
    while (
        suffix < previous.length - prefix &&
        suffix < raw.length - caret &&
        raw[raw.length - 1 - suffix] === previous[previous.length - 1 - suffix]
    ) {
        suffix += 1;
    }

    const inserted = raw.slice(prefix, caret).replace(/\D/g, '');
    // The span of `previous` that disappeared — its slots are cleared before
    // the typed digits are written in.
    const removedFrom = prefix;
    const removedTo = previous.length - suffix;

    const slots = prevSlots.slice();
    for (let offset = removedFrom; offset < removedTo; offset += 1) {
        const slot = SLOT_OFFSETS.indexOf(offset);
        if (slot !== -1) {
            slots[slot] = undefined;
        }
    }

    if (inserted === '') {
        // Pure deletion. Backspacing onto a dash removes the digit before it
        // instead, so the key always takes away something visible.
        if (removedTo - removedFrom === 1 && SLOT_OFFSETS.indexOf(removedFrom) === -1) {
            const slot = offsetToSlot(removedFrom) - 1;
            if (slot >= 0) {
                slots[slot] = undefined;
            }
        }
        return { text: renderSlots(slots), caret: removedFrom };
    }

    let slot = offsetToSlot(removedFrom);
    for (const digit of inserted) {
        if (slot > 7) {
            break;
        }
        slots[slot] = digit;
        slot += 1;
    }

    clampSlots(slots);

    // Land the caret after the last slot written, skipping over a dash so the
    // next keystroke goes straight into the following group.
    const nextOffset = slot >= 8 ? 10 : SLOT_OFFSETS[slot];
    return { text: renderSlots(slots), caret: nextOffset };
}

/**
 * Keeps a complete day/month group inside its calendar range, in place — a
 * typed '35' becomes '31', '19' as a month becomes '12'. Never re-flows other
 * slots (that is what broke mid-string editing).
 */
function clampSlots(slots: (string | undefined)[]): void {
    if (slots[0] !== undefined && slots[1] !== undefined) {
        const day = Number(slots[0] + slots[1]);
        if (day > 31) {
            slots[0] = '3';
            slots[1] = '1';
        } else if (day === 0) {
            slots[0] = '0';
            slots[1] = '1';
        }
    }
    if (slots[2] !== undefined && slots[3] !== undefined) {
        const month = Number(slots[2] + slots[3]);
        if (month > 12) {
            slots[2] = '1';
            slots[3] = '2';
        } else if (month === 0) {
            slots[2] = '0';
            slots[3] = '1';
        }
    }
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
    clearable,
    panelAlign = 'left',
}: DateFieldProps) {
    // A required field must stay filled, so it gets no clear button unless
    // the caller explicitly asks for one.
    const canClear = clearable ?? !required;
    const [open, setOpen] = useState(false);
    const wrapperRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);
    /** Set while programmatically refocusing after a clear, so onFocus doesn't reopen the panel. */
    const suppressOpenRef = useRef(false);
    /** Caret offset to restore after the masked value re-renders. */
    const pendingCaretRef = useRef<number | null>(null);

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

    function handleTextChange(text: string, caret: number) {
        const { text: masked, caret: nextCaret } = maskDateInput(text, caret, typedText ?? formatted);
        setTypedText(masked);
        // React re-renders with the masked value, which would otherwise drop
        // the caret at the end — put it back where the edit happened.
        pendingCaretRef.current = nextCaret;

        // Emptying the input clears the value straight away — that is how a
        // date FILTER is removed («from this date» no longer applies). Without
        // this the old value silently came back on blur and the filter was
        // impossible to unset.
        if (masked === '') {
            if (value !== '') {
                emit('');
            }
            return;
        }

        const iso = parseDisplay(masked);
        if (iso) {
            emit(iso);
            const next = parseIso(iso)!;
            setViewYear(next.year);
            setViewMonth(next.month);
        }
    }

    function clear() {
        setTypedText(null);
        if (value !== '') {
            emit('');
        }
        setOpen(false);
        // Keep the caret in the field, but don't let the refocus reopen the
        // panel we just closed — clearing a filter should leave the calendar
        // shut, not pop it straight back up.
        suppressOpenRef.current = true;
        inputRef.current?.focus();
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

    useLayoutEffect(() => {
        const caret = pendingCaretRef.current;
        if (caret === null || !inputRef.current) {
            return;
        }
        pendingCaretRef.current = null;
        inputRef.current.setSelectionRange(caret, caret);
    });

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
            return;
        }
        // Delete wipes the whole field in one keystroke (the panel is open on
        // focus, so users reach for Delete rather than 10 backspaces).
        if (canClear && event.key === 'Delete' && !disabled) {
            event.preventDefault();
            clear();
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
    const showClear = canClear && !disabled && display !== '';
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
                    onChange={(event) =>
                        handleTextChange(event.target.value, event.target.selectionStart ?? event.target.value.length)
                    }
                    onBlur={handleTextBlur}
                    onFocus={() => {
                        if (suppressOpenRef.current) {
                            suppressOpenRef.current = false;
                            return;
                        }
                        if (!disabled) {
                            openPanel();
                        }
                    }}
                />
                {showClear ? (
                    <button
                        type="button"
                        className="btn btn-sm p-0 border-0 bg-transparent text-muted lh-1"
                        aria-label={t('Clear date')}
                        title={t('Clear date')}
                        // mousedown fires before the input's blur, so the click
                        // isn't swallowed by the outside-click close.
                        onMouseDown={(event) => {
                            event.preventDefault();
                            clear();
                        }}
                        style={{
                            position: 'absolute',
                            right: '0.6rem',
                            top: '50%',
                            transform: 'translateY(-50%)',
                            zIndex: 2,
                        }}
                    >
                        <i className="ti ti-x fs-16" aria-hidden="true" />
                    </button>
                ) : (
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
                )}
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
                                aria-label={t('Previous month')}
                                onClick={() => moveMonth(-1)}
                            >
                                <i className="ti ti-chevron-left" aria-hidden="true" />
                            </button>
                            <span className="fw-bold text-capitalize">{monthTitle}</span>
                            <button
                                type="button"
                                className="btn btn-outline-light bg-white btn-icon rounded-circle"
                                aria-label={t('Next month')}
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
