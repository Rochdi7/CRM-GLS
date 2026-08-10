
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
export default function DateField({
    id,
    label,
    value = '',
    onChange,
    error,
    required,
    disabled = false,
    placeholder,
}: DateFieldProps) {
    const [open, setOpen] = useState(false);
    const wrapperRef = useRef<HTMLDivElement>(null);

    const parsed = parseIso(value);
    const today = new Date();
    const [viewYear, setViewYear] = useState(() => parsed?.year ?? today.getFullYear());
    const [viewMonth, setViewMonth] = useState(() => parsed?.month ?? today.getMonth());

    function emit(next: string) {
        onChange?.({ target: { value: next } } as unknown as React.ChangeEvent<HTMLInputElement>);
    }

    function openPanel() {
        // Re-sync the view month with the current value each time it opens.
        const current = parseIso(value);
        setViewYear(current?.year ?? new Date().getFullYear());
        setViewMonth(current?.month ?? new Date().getMonth());
        setOpen(true);
    }

    function choose(cell: GridDay) {
        emit(toIso(cell.year, cell.month, cell.day));
        if (!cell.inMonth) {
            setViewYear(cell.year);
            setViewMonth(cell.month);
        }
        setOpen(false);
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

        document.addEventListener('mousedown', handleOutside);

        return () => document.removeEventListener('mousedown', handleOutside);
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

    const display = parsed ? `${pad(parsed.day)}-${pad(parsed.month + 1)}-${parsed.year}` : '';
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
                    type="text"
                    readOnly
                    className={`form-control${error ? ' is-invalid' : ''}`}
                    style={{ cursor: disabled ? undefined : 'pointer', paddingRight: '2.25rem' }}
                    value={display}
                    placeholder={placeholder ?? 'jj-mm-aaaa'}
                    disabled={disabled}
                    required={required}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? `${id}-error` : undefined}
                    aria-haspopup="dialog"
                    aria-expanded={open}
                    onClick={() => {
                        if (!disabled) {
                            if (open) {
                                setOpen(false);
                            } else {
                                openPanel();
                            }
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
                {open && (
                    <div
                        className="bg-white border rounded-3 shadow p-3"
                        role="dialog"
                        aria-label={monthTitle}
                        style={{ position: 'absolute', top: '100%', left: 0, zIndex: 1070, width: 300, marginTop: 4 }}
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
