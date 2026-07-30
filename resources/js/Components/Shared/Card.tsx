import type { PropsWithChildren, ReactNode } from 'react';

interface CardProps extends PropsWithChildren {
    title?: string;
    tools?: ReactNode;
    className?: string;
}

/** Markup matches components/backoffice/ui/card.blade.php exactly. */
export default function Card({ title, tools, className = '', children }: CardProps) {
    return (
        <div className={`card${className ? ` ${className}` : ''}`}>
            {(title || tools) && (
                <div className="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                    {title && <h4 className="mb-3">{title}</h4>}
                    {tools && <div className="d-flex align-items-center flex-wrap mb-3">{tools}</div>}
                </div>
            )}
            <div className="card-body">{children}</div>
        </div>
    );
}
