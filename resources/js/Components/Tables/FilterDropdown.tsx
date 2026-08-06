import { useEffect, useRef, useState } from 'react';
import DateField from '@/Components/Forms/DateField';
import { t } from '@/Lib/i18n';

export interface FilterFieldOption {
    value: string | number;
    label: string;
}

export interface FilterFieldDef {
    name: string;
    label: string;
    /** `select` (default) or `date`. */
    type?: 'select' | 'date';
    /** Currently APPLIED value (from the page's filter state). */
    value: string;
    options?: FilterFieldOption[];
    /** First option label for selects, e.g. "Tous les niveaux". */
    placeholder?: string;
}

interface FilterDropdownProps {
    fields: FilterFieldDef[];
    /** Called with the drafted values when "Appliquer" is clicked. */
    onApply: (values: Record<string, string>) => void;
    /** Called when "Réinitialiser" is clicked (page clears its filters). */
    onReset: () => void;
}

/**
 * PreSkool filter dropdown (students.html card-header tools — Phase 13 UI
 * parity): white-outline trigger + a form panel with labeled fields and a
 * Reset/Apply footer. React owns open/close (no Bootstrap JS). Values are
 * drafted locally and only hit the server on Apply — the page's own
 * Inertia reload keeps doing the real work, so server-side filtering,
 * query-string state, and back/forward behavior are unchanged.
 */
export default function FilterDropdown({ fields, onApply, onReset }: FilterDropdownProps) {
    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState<Record<string, string>>({});
    const wrapperRef = useRef<HTMLDivElement>(null);

    const applied = Object.fromEntries(fields.map((f) => [f.name, f.value]));
    const activeCount = fields.filter((f) => f.value !== '').length;

    useEffect(() => {
        if (open) {
            setDraft(applied);
        }
        // Draft (re)seeds from the applied values each time the panel opens.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    useEffect(() => {
        if (!open) {
            return;
        }

        function handleOutside(event: MouseEvent) {
            if (wrapperRef.current && !wrapperRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        function handleKey(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', handleOutside);
        document.addEventListener('keydown', handleKey);

        return () => {
            document.removeEventListener('mousedown', handleOutside);
            document.removeEventListener('keydown', handleKey);
        };
    }, [open]);

    return (
        <div className="dropdown mb-3 me-2" ref={wrapperRef}>
            <button
                type="button"
                className="btn btn-outline-light bg-white dropdown-toggle"
                aria-expanded={open}
                onClick={() => setOpen((o) => !o)}
            >
                <i className="ti ti-filter me-2" aria-hidden="true" />
                {t('Filter')}
                {activeCount > 0 && <span className="badge badge-soft-primary ms-2">{activeCount}</span>}
            </button>
            {open && (
                // Markup copied from theme-reference/preskool/hrm/payroll.blade.php's
                // filter dropdown (drop-width = the theme's 350px panel).
                // dropdown-menu-end anchors the panel to the trigger's right
                // edge in pure CSS — the theme demo gets the same result from
                // Bootstrap's Popper, which this app deliberately doesn't load.
                <div className="dropdown-menu dropdown-menu-end drop-width show" data-bs-popper="static">
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            setOpen(false);
                            onApply(draft);
                        }}
                    >
                        <div className="d-flex align-items-center border-bottom p-3">
                            <h4>{t('Filter')}</h4>
                        </div>
                        <div className="p-3 border-bottom">
                            <div className="row">
                                {fields.map((field) => (
                                    <div className="col-md-6" key={field.name}>
                                        <div className="mb-3">
                                            <label className="form-label" htmlFor={`filter-${field.name}`}>
                                                {field.label}
                                            </label>
                                            {field.type === 'date' ? (
                                                <DateField
                                                    id={`filter-${field.name}`}
                                                    value={draft[field.name] ?? ''}
                                                    onChange={(e) => setDraft((d) => ({ ...d, [field.name]: e.target.value }))}
                                                />
                                            ) : (
                                                <select
                                                    id={`filter-${field.name}`}
                                                    className="form-select"
                                                    value={draft[field.name] ?? ''}
                                                    onChange={(e) => setDraft((d) => ({ ...d, [field.name]: e.target.value }))}
                                                >
                                                    <option value="">{field.placeholder ?? t('All')}</option>
                                                    {field.options?.map((option) => (
                                                        <option key={option.value} value={option.value}>
                                                            {option.label}
                                                        </option>
                                                    ))}
                                                </select>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div className="p-3 d-flex align-items-center justify-content-end">
                            <button
                                type="button"
                                className="btn btn-light me-3"
                                onClick={() => {
                                    setOpen(false);
                                    onReset();
                                }}
                            >
                                {t('Reset')}
                            </button>
                            <button type="submit" className="btn btn-primary">
                                {t('Apply')}
                            </button>
                        </div>
                    </form>
                </div>
            )}
        </div>
    );
}
