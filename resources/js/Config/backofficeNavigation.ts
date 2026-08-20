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
        // Old-CRM grouping (see pic 2): Prospects / Essais & Tests would
        // belong here too, but those modules don't exist in this app yet —
        // omitted rather than linked to nothing (product decision).
        label: t('Registrations management'),
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
        // Old-CRM grouping (see pic 2): Gestion des devoirs / Doc.
        // pédagogique would belong here too, but those modules don't exist
        // in this app yet — omitted rather than linked to nothing.
        label: t('Educational tracking'),
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
                label: t('Attendance'),
                href: '/backoffice/seances',
                icon: 'ti ti-calendar-check',
                permissions: ['attendance.view'],
                matchPaths: ['/backoffice/seances'],
                inertia: true,
            },
            {
                label: t('Timetable'),
                href: '/backoffice/emploi-du-temps',
                icon: 'ti ti-calendar-time',
                permissions: ['attendance.view'],
                matchPaths: ['/backoffice/emploi-du-temps'],
                inertia: true,
            },
        ],
    },
    {
        // Old-CRM grouping (see pic 2/3): Recouvrement / Situation
        // financière would belong here too, but those modules don't exist
        // in this app yet — omitted rather than linked to nothing.
        label: t('Financial management'),
        items: [
            // Finance modules as separate sidebar entries (wimschool-style
            // sidebar: Encaissement / Caisse / Dépenses) — the PageTabs bar
            // on each finance page cross-links them too.
            {
                label: t('Payments'),
                href: '/backoffice/encaissements',
                icon: 'ti ti-cash-banknote',
                // Chèques (physical-check inventory) has no sidebar entry of
                // its own — reachable via this page's PageTabs cross-link,
                // same "stays out of the sidebar" convention as Types de
                // dépenses below. matchPaths (not permissions) is what
                // decides the active-state highlight when actually on the
                // Chèques page — visibility itself still requires
                // payments.view, since that's the page this link opens.
                permissions: ['payments.view'],
                matchPaths: ['/backoffice/encaissements', '/backoffice/cheques'],
                inertia: true,
            },
            {
                label: t('Cash management'),
                href: '/backoffice/caisses',
                icon: 'ti ti-cash',
                permissions: ['cash-registers.view', 'cash-transfers.view'],
                matchPaths: ['/backoffice/caisses', '/backoffice/caisse-transfers'],
                inertia: true,
            },
            {
                label: t('Expense management'),
                href: '/backoffice/depenses',
                icon: 'ti ti-receipt',
                permissions: ['expenses.view', 'refunds.view'],
                matchPaths: ['/backoffice/depenses', '/backoffice/remboursements'],
                inertia: true,
            },
            {
                label: t('Collections management'),
                href: '/backoffice/recouvrement',
                icon: 'ti ti-transfer',
                permissions: ['collections.view'],
                matchPaths: ['/backoffice/recouvrement'],
                inertia: true,
            },
            // Chèques and Types de dépenses stay out of the sidebar —
            // reachable via each other's finance PageTabs (product decision).
        ],
    },
    {
        // Old-CRM grouping (see pic 3): Gestion de la paie would belong
        // here too, but payroll doesn't exist in this app yet — omitted
        // rather than linked to nothing.
        label: t('Human resources'),
        items: [
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
        // Old-CRM grouping (see pic 3): Gestion des services / Activité
        // parascolaire / Communications / Outils would belong here too, but
        // those modules don't exist in this app yet — omitted rather than
        // linked to nothing.
        label: t('Establishment tracking'),
        items: [
            {
                label: t('Stock management'),
                href: '/backoffice/stock',
                icon: 'ti ti-packages',
                permissions: ['stock.view'],
                matchPaths: ['/backoffice/stock'],
                inertia: true,
            },
        ],
    },
    {
        label: t('Configuration'),
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
            // Masque du menu a la demande (route backoffice.import.index reste
            // active et protegee, accessible par URL directe) - retirer les
            // commentaires pour reafficher.
            // {
            //     label: t('Data import'),
            //     href: '/backoffice/import',
            //     icon: 'ti ti-upload',
            //     permissions: ['import.view'],
            //     matchPaths: ['/backoffice/import'],
            //     inertia: true,
            // },
            // Masque du menu a la demande (route backoffice.audit-logs.index
            // reste active et protegee) - retirer les commentaires pour reafficher.
            // {
            //     label: t('Audit journal'),
            //     href: '/backoffice/audit-logs',
            //     icon: 'ti ti-history',
            //     permissions: ['audit-logs.view'],
            //     matchPaths: ['/backoffice/audit-logs'],
            //     inertia: true,
            // },
        ],
    },
];
