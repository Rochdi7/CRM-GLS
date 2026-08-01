import type { PropsWithChildren, ReactNode } from 'react';

interface CardProps extends PropsWithChildren {
    title?: string;
    tools?: ReactNode;
    className?: string;
    /**
     * PreSkool list cards run the table edge-to-edge with `card-body p-0
     * py-3` (students.html §card) — pass `bodyClassName="p-0 py-3"` on
     * table cards; default keeps the normal padded body for form/detail
     * cards (Phase 13 UI parity).
     */
    bodyClassName?: string;
}

/**
 * PreSkool content card (theme reference students.html/fees-type.html):
 * `card-header d-flex align-items-center justify-content-between flex-wrap
 * pb-0` with the tools right-aligned; each tool should carry `mb-3 me-2`
 * (last `mb-3`) so wrapping stays spaced like the theme.
 */
export default function Card({ title, tools, className = '', bodyClassName = '', children }: CardProps) {
    return (
        <div className={`card${className ? ` ${className}` : ''}`}>
            {(title || tools) && (
                <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                    {title && <h4 className="mb-3">{title}</h4>}
                    {tools && <div className="d-flex align-items-center flex-wrap">{tools}</div>}
                </div>
            )}
            <div className={`card-body${bodyClassName ? ` ${bodyClassName}` : ''}`}>{children}</div>
        </div>
    );
}
