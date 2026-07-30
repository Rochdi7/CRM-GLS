import { Link } from '@inertiajs/react';
import type { Breadcrumb } from '@/Types';

interface BreadcrumbsProps {
    items: Breadcrumb[];
}

/** Markup matches components/backoffice/layout/breadcrumbs.blade.php exactly. */
export default function Breadcrumbs({ items }: BreadcrumbsProps) {
    return (
        <nav>
            <ol className="breadcrumb mb-0">
                {items.map((item) =>
                    item.href ? (
                        <li className="breadcrumb-item" key={item.label}>
                            {item.inertia ? <Link href={item.href}>{item.label}</Link> : <a href={item.href}>{item.label}</a>}
                        </li>
                    ) : (
                        <li className="breadcrumb-item active" aria-current="page" key={item.label}>
                            {item.label}
                        </li>
                    ),
                )}
            </ol>
        </nav>
    );
}
