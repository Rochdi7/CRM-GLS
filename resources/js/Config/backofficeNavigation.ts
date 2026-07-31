import type { NavGroup } from '@/Types';

/**
 * Mirrors resources/views/components/backoffice/layout/sidebar.blade.php
 * exactly (same groups, same items, same permission gates, same icons) —
 * keep both in sync until the Blade sidebar is retired (Phase 10).
 *
 * `inertia: true` marks the only item with a real Inertia page today
 * (Permissions, Phase 2 pilot). Every other item is a real, working link
 * to its existing Livewire/Blade route — plain anchor navigation, never
 * Inertia-intercepted, until that module gets its own Phase.
 */
export const backofficeNavigation: NavGroup[] = [
    {
        label: 'Main',
        items: [
            {
                label: 'Dashboard',
                href: '/backoffice/dashboard',
                icon: 'ti ti-layout-dashboard',
                matchPaths: ['/backoffice/dashboard'],
            },
        ],
    },
    {
        label: 'People',
        items: [
            {
                label: 'Students',
                href: '/backoffice/students',
                icon: 'ti ti-school',
                permissions: ['students.view'],
                matchPaths: ['/backoffice/students'],
                inertia: true,
            },
            {
                label: 'Employees',
                href: '/backoffice/employees',
                icon: 'ti ti-users',
                permissions: ['employees.view'],
                matchPaths: ['/backoffice/employees'],
                inertia: true,
            },
        ],
    },
    {
        label: 'Academic',
        items: [
            {
                label: 'Registrations',
                href: '/backoffice/inscriptions',
                icon: 'ti ti-clipboard-list',
                permissions: ['registrations.view'],
                matchPaths: ['/backoffice/inscriptions'],
                inertia: true,
            },
            {
                label: 'Groups',
                href: '/backoffice/groups',
                icon: 'ti ti-users-group',
                permissions: ['groups.view'],
                matchPaths: ['/backoffice/groups'],
                inertia: true,
            },
        ],
    },
    {
        label: 'Finance',
        items: [
            {
                label: 'Cash management',
                href: '/backoffice/caisses',
                icon: 'ti ti-cash',
                permissions: ['cash-registers.view', 'cash-transfers.view'],
                matchPaths: ['/backoffice/caisses', '/backoffice/caisse-transfers'],
                inertia: true,
            },
            {
                label: 'Payments',
                href: '/backoffice/encaissements',
                icon: 'ti ti-cash-banknote',
                permissions: ['payments.view'],
                matchPaths: ['/backoffice/encaissements'],
                inertia: true,
            },
            {
                label: 'Expense management',
                href: '/backoffice/depenses',
                icon: 'ti ti-receipt',
                permissions: ['expenses.view', 'refunds.view'],
                matchPaths: ['/backoffice/depenses', '/backoffice/remboursements'],
                inertia: true,
            },
            {
                label: 'Expense types',
                href: '/backoffice/types-depenses',
                icon: 'ti ti-receipt-tax',
                permissions: ['expense-types.view'],
                matchPaths: ['/backoffice/types-depenses'],
                inertia: true,
            },
        ],
    },
    {
        label: 'Administration',
        items: [
            {
                label: 'Settings',
                href: '/backoffice/settings',
                icon: 'ti ti-settings',
                permissions: ['centers.view', 'academic-years.view', 'rooms.view', 'fees.view'],
                matchPaths: ['/backoffice/settings'],
            },
            {
                label: 'Users',
                href: '/backoffice/users',
                icon: 'ti ti-user-cog',
                permissions: ['users.view'],
                matchPaths: ['/backoffice/users'],
                inertia: true,
            },
            {
                label: 'Roles & Permissions',
                href: '/backoffice/roles',
                icon: 'ti ti-shield-lock',
                permissions: ['roles.view'],
                matchPaths: ['/backoffice/roles'],
                inertia: true,
            },
            {
                label: 'Permissions',
                href: '/backoffice/permissions',
                icon: 'ti ti-key',
                permissions: ['permissions.view'],
                matchPaths: ['/backoffice/permissions'],
                inertia: true,
            },
        ],
    },
];
