import { usePage } from '@inertiajs/react';
import { useEffect, useState, type PropsWithChildren, type ReactNode } from 'react';
import Header from '@/Components/Theme/Header';
import Sidebar from '@/Components/Theme/Sidebar';
import Footer from '@/Components/Theme/Footer';
import MobileSidebarOverlay from '@/Components/Theme/MobileSidebarOverlay';
import PageHeader from '@/Components/Theme/PageHeader';
import FlashMessages from '@/Components/Feedback/FlashMessages';
import type { Breadcrumb, SharedProps } from '@/Types';

interface BackofficeLayoutProps extends PropsWithChildren {
    title: string;
    breadcrumbs?: Breadcrumb[];
    /** Rendered in the page-header's actions slot (top-right), matching components/backoffice/layout/page-header.blade.php's x-slot:actions. */
    actions?: ReactNode;
}

/**
 * PreSkool Backoffice shell (docs/inertia-react-migration-plan.md §4,
 * docs/react-theme-file-map.md §3). Structural markup mirrors
 * components/backoffice/layout/app.blade.php exactly (.main-wrapper >
 * header + sidebar + .page-wrapper > .content + footer) so the existing
 * static PreSkool CSS applies unchanged — only the *behavior* (sidebar
 * toggle, dropdowns) is React-owned instead of jQuery/Bootstrap-JS.
 */
export default function BackofficeLayout({ title, breadcrumbs = [], actions, children }: BackofficeLayoutProps) {
    const { auth, context, flash } = usePage<SharedProps>().props;
    const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);

    // Close the mobile sidebar on every navigation (migration plan §"Sidebar state").
    const currentUrl = usePage().url;
    useEffect(() => {
        setMobileSidebarOpen(false);
    }, [currentUrl]);

    useEffect(() => {
        function handleEscape(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setMobileSidebarOpen(false);
            }
        }

        document.addEventListener('keydown', handleEscape);

        return () => document.removeEventListener('keydown', handleEscape);
    }, []);

    const canManageSettings = ['centers.view', 'academic-years.view', 'rooms.view'].some((permission) =>
        auth.permissions.includes(permission),
    );

    return (
        <div className={`main-wrapper${mobileSidebarOpen ? ' slide-nav' : ''}`}>
            <Header
                user={auth.user}
                context={context}
                canManageSettings={canManageSettings}
                onMobileMenuToggle={() => setMobileSidebarOpen((open) => !open)}
            />

            <Sidebar
                permissions={auth.permissions}
                isSuperAdmin={auth.isSuperAdmin}
                mobileOpen={mobileSidebarOpen}
                onNavigate={() => setMobileSidebarOpen(false)}
            />

            <MobileSidebarOverlay visible={mobileSidebarOpen} onClose={() => setMobileSidebarOpen(false)} />

            <div className="page-wrapper">
                <div className="content">
                    <FlashMessages flash={flash} />
                    <PageHeader title={title} breadcrumbs={breadcrumbs}>
                        {actions}
                    </PageHeader>
                    {children}
                </div>
                <Footer />
            </div>
        </div>
    );
}
