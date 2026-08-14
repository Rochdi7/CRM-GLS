import { useEffect, useRef, useState } from 'react';
import type { PropsWithChildren, ReactNode } from 'react';

interface RowActionsProps extends PropsWithChildren {
    /** Detail-page link — renders the eye button, matching action-menu.blade.php. Omit when there is no detail page. */
    view?: string;
    viewLabel?: string;
}

interface RowActionItemProps {
    icon?: string;
    href?: string;
    danger?: boolean;
    onClick?: () => void;
    children: ReactNode;
}

/**
 * Row action menu — React port of components/backoffice/ui/action-menu.blade.php.
 * The Blade version opens via Bootstrap's `data-bs-toggle="dropdown"` (DOM
 * scanning JS); Inertia pages load no Bootstrap JS at all
 * (docs/bootstrap-react-integration-decision.md), so open/close state,
 * click-outside, and Escape are React-owned here — same technique already
 * used by Header.tsx's user-menu dropdown.
 */
export default function RowActions({ view, viewLabel = 'Voir', children }: RowActionsProps) {
    const [open, setOpen] = useState(false);
    // The menu renders position:fixed, measured from the trigger — inside
    // .table-responsive (overflow-x:auto) an absolutely-positioned menu gets
    // clipped at the card edge (the theme demo escapes this via Popper,
    // which this app deliberately doesn't load). Fixed positioning escapes
    // every scroll container; flips upward near the viewport bottom.
    const [pos, setPos] = useState<{ top?: number; bottom?: number; right: number } | null>(null);
    const menuRef = useRef<HTMLDivElement>(null);
    const buttonRef = useRef<HTMLButtonElement>(null);

    function toggle() {
        if (!open && buttonRef.current) {
            const rect = buttonRef.current.getBoundingClientRect();
            const right = Math.max(8, window.innerWidth - rect.right);
            const openUpward = rect.bottom + 220 > window.innerHeight;
            setPos(
                openUpward
                    ? { bottom: window.innerHeight - rect.top + 4, right }
                    : { top: rect.bottom + 4, right },
            );
        }
        setOpen((value) => !value);
    }

    useEffect(() => {
        if (!open) {
            return;
        }

        function handleClickOutside(event: MouseEvent) {
            if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        function handleEscape(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        }

        // A fixed-position menu would drift from its trigger on any scroll
        // (window or the table's own horizontal scroll) — close instead.
        function handleScroll() {
            setOpen(false);
        }

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleEscape);
        window.addEventListener('scroll', handleScroll, true);
        window.addEventListener('resize', handleScroll);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleEscape);
            window.removeEventListener('scroll', handleScroll, true);
            window.removeEventListener('resize', handleScroll);
        };
    }, [open]);

    const hasItems = Array.isArray(children) ? children.some(Boolean) : Boolean(children);

    return (
        <div className="d-flex align-items-center justify-content-end">
            {view && (
                <a
                    href={view}
                    className="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-2"
                    title={viewLabel}
                >
                    <i className="ti ti-eye" />
                </a>
            )}
            {hasItems && (
                <div className="dropdown" ref={menuRef}>
                    <button
                        ref={buttonRef}
                        type="button"
                        className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                        onClick={toggle}
                        aria-expanded={open}
                        aria-label="Actions"
                    >
                        <i className="ti ti-dots-vertical fs-14" />
                    </button>
                    <ul
                        className={`dropdown-menu dropdown-menu-end p-3${open ? ' show' : ''}`}
                        // zIndex restores Bootstrap's 1000: the theme drops
                        // .dropdown-menu to 10 and lifts every closed
                        // .select2-container to 99 (style.css), so without it
                        // the menu paints under the toolbar's SelectFields.
                        style={open && pos ? { position: 'fixed', left: 'auto', zIndex: 1000, ...pos } : undefined}
                    >
                        {children}
                    </ul>
                </div>
            )}
        </div>
    );
}

/** Visual separator between groups of related row actions (Bootstrap's own `.dropdown-divider`). */
export function RowActionDivider() {
    return (
        <li>
            <hr className="dropdown-divider" />
        </li>
    );
}

export function RowActionItem({ icon, href, danger = false, onClick, children }: RowActionItemProps) {
    const className = `dropdown-item rounded-1${danger ? ' text-danger' : ''}`;

    return (
        <li>
            {href ? (
                <a href={href} className={className}>
                    {icon && <i className={`ti ${icon} me-2`} />}
                    {children}
                </a>
            ) : (
                <button type="button" className={`${className} w-100 text-start border-0 bg-transparent`} onClick={onClick}>
                    {icon && <i className={`ti ${icon} me-2`} />}
                    {children}
                </button>
            )}
        </li>
    );
}
