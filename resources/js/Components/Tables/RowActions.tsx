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
    const menuRef = useRef<HTMLDivElement>(null);

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

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleEscape);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleEscape);
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
                        type="button"
                        className="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                        onClick={() => setOpen((value) => !value)}
                        aria-expanded={open}
                        aria-label="Actions"
                    >
                        <i className="ti ti-dots-vertical fs-14" />
                    </button>
                    <ul className={`dropdown-menu dropdown-menu-end p-3${open ? ' show' : ''}`}>
                        {children}
                    </ul>
                </div>
            )}
        </div>
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
