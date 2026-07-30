import type { PropsWithChildren } from 'react';
import Breadcrumbs from '@/Components/Theme/Breadcrumbs';
import type { Breadcrumb } from '@/Types';

interface PageHeaderProps extends PropsWithChildren {
    title: string;
    breadcrumbs?: Breadcrumb[];
}

/** Markup matches components/backoffice/layout/page-header.blade.php exactly. `children` fills the actions slot. */
export default function PageHeader({ title, breadcrumbs = [], children }: PageHeaderProps) {
    return (
        <div className="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div className="my-auto mb-2">
                <h3 className="page-title mb-1">{title}</h3>
                {breadcrumbs.length > 0 && <Breadcrumbs items={breadcrumbs} />}
            </div>
            {children && <div className="d-flex my-xl-auto right-content align-items-center flex-wrap">{children}</div>}
        </div>
    );
}
