import type { NavGroup } from '@/Types';
import { t } from '@/Lib/i18n';

/**
 * The backoffice sidebar navigation. Every module is now a real Inertia
 * page (Phase 11 completed the Livewire→Inertia migration — see
 * docs/phase-11-final-verification.md) — every item below carries
 * `inertia: true` so NavLink renders an Inertia `<Link>` (SPA navigation)
 * instead of a plain anchor (full page reload). Add `inertia: true` to any
 * new item once its route is confirmed to return a real Inertia response.
 */
export const backofficeNavigation: NavGroup[] = [
    {
        label: t('Main'),
        items: [
            {
                label: t('Dashboard'),
                href: '/backoffice/dashboard',
                icon: 'ti ti-layout-dashboard',
                matchPaths: ['/backoffice/dashboard'],
                inertia: true,
            },
        ],
    },
    {
        label: t('People'),
        items: [
            {
                label: t('Students'),
                href: '/backoffice/students',
                icon: 'ti ti-school',
                permissions: ['students.view'],
                matchPaths: ['/backoffice/students'],
                inertia: true,
            },
            {
                label: t('Employees'),
                href: '/backoffice/employees',
                icon: 'ti ti-users',
                permissions: ['employees.view'],
                matchPaths: ['/backoffice/employees'],
                inertia: true,
            },
        ],
    },
    {
        label: t('Academic'),
        items: [
            {
                label: t('Registrations'),
                href: '/backoffice/inscriptions',
                icon: 'ti ti-clipboard-list',
                permissions: ['registrations.view'],
                matchPaths: ['/backoffice/inscriptions'],
                inertia: true,
            },
            {
                label: t('Groups'),
                href: '/backoffice/groups',
                icon: 'ti ti-users-group',
                permissions: ['groups.view'],
                matchPaths: ['/backoffice/groups'],
                inertia: true,
            },
            // Hidden by product decision — the page stays reachable at
            // /backoffice/groups-historique (groups.view); re-enable when
            // it should appear in the sidebar.
            // {
            //     label: t('Groups History'),
            //     href: '/backoffice/groups-historique',
            //     icon: 'ti ti-archive',
            //     permissions: ['groups.view'],
            //     matchPaths: ['/backoffice/groups-historique'],
            //     inertia: true,
            // },
        ],
    },
    {
        label: t('Finance'),
        items: [
            // Hidden by product decision — routes stay active and
            // permission-gated; re-enable when ready to expose Cash
            // management in the sidebar.
            // {
            //     label: t('Cash management'),
            //     href: '/backoffice/caisses',
            //     icon: 'ti ti-cash',
            //     permissions: ['cash-registers.view', 'cash-transfers.view'],
            //     matchPaths: ['/backoffice/caisses', '/backoffice/caisse-transfers'],
            //     inertia: true,
            // },
            {
                label: t('Payments'),
                href: '/backoffice/encaissements',
                icon: 'ti ti-cash-banknote',
                permissions: ['payments.view'],
                matchPaths: ['/backoffice/encaissements'],
                inertia: true,
            },
            // Hidden by product decision — re-enable when ready to expose
            // Expense management in the sidebar.
            // {
            //     label: t('Expense management'),
            //     href: '/backoffice/depenses',
            //     icon: 'ti ti-receipt',
            //     permissions: ['expenses.view', 'refunds.view'],
            //     matchPaths: ['/backoffice/depenses', '/backoffice/remboursements'],
            //     inertia: true,
            // },
            // Hidden by product decision — re-enable when ready to expose
            // Expense types in the sidebar.
            // {
            //     label: t('Expense types'),
            //     href: '/backoffice/types-depenses',
            //     icon: 'ti ti-receipt-tax',
            //     permissions: ['expense-types.view'],
            //     matchPaths: ['/backoffice/types-depenses'],
            //     inertia: true,
            // },
        ],
    },
    {
        label: t('Administration'),
        items: [
            {
                label: t('Settings'),
                href: '/backoffice/settings',
                icon: 'ti ti-settings',
                permissions: ['centers.view', 'academic-years.view', 'rooms.view', 'fees.view'],
                matchPaths: ['/backoffice/settings'],
                inertia: true,
            },
            {
                label: t('Users'),
                href: '/backoffice/users',
                icon: 'ti ti-user-cog',
                permissions: ['users.view'],
                matchPaths: ['/backoffice/users'],
                inertia: true,
            },
            {
                label: t('Roles & Permissions'),
                href: '/backoffice/roles',
                icon: 'ti ti-shield-lock',
                permissions: ['roles.view'],
                matchPaths: ['/backoffice/roles'],
                inertia: true,
            },
            {
                label: t('Permissions'),
                href: '/backoffice/permissions',
                icon: 'ti ti-key',
                permissions: ['permissions.view'],
                matchPaths: ['/backoffice/permissions'],
                inertia: true,
            },
        ],
    },
];
