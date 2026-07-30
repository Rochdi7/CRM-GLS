import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import type { SharedProps } from '@/Types';

/**
 * First-cut Backoffice shell for the Inertia pilot (Phase 1).
 *
 * Deliberately minimal — the full PreSkool header/sidebar/theme-settings
 * adaptation happens in Phase 2 (docs/inertia-react-migration-plan.md §4).
 * This exists only so the pilot page has somewhere to render real shared
 * props (auth user, permissions) end-to-end.
 */
export default function BackofficeLayout({ children }: PropsWithChildren) {
    const { auth } = usePage<SharedProps>().props;

    return (
        <div className="page-wrapper">
            <div className="content container-fluid">
                <div className="d-flex justify-content-between align-items-center mb-4">
                    <Link href="/backoffice/dashboard" className="fw-bold text-decoration-none">
                        GLS CRM
                    </Link>
                    {auth.user && <span className="text-muted">{auth.user.name}</span>}
                </div>
                {children}
            </div>
        </div>
    );
}
