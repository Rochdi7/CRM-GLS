import type { PageTabDef } from '@/Components/Navigation/PageTabs';
import { t } from '@/Lib/i18n';

/**
 * Module tab groups (wimschool-inspired): related modules surface as
 * page-level tabs instead of separate sidebar entries (the sidebar shows
 * one entry per group — see backofficeNavigation). Permissions mirror the
 * sidebar gates exactly; the server keeps enforcing everything.
 */
export const FINANCE_TABS: PageTabDef[] = [
    {
        label: t('Payments'),
        href: '/backoffice/encaissements',
        icon: 'ti ti-cash-banknote',
        permissions: ['payments.view'],
    },
    {
        label: t('Expense management'),
        href: '/backoffice/depenses',
        icon: 'ti ti-receipt',
        permissions: ['expenses.view', 'refunds.view'],
        matchPaths: ['/backoffice/depenses', '/backoffice/remboursements'],
    },
    {
        label: t('Expense types'),
        href: '/backoffice/types-depenses',
        icon: 'ti ti-receipt-tax',
        permissions: ['expense-types.view'],
    },
    {
        label: t('Cash management'),
        href: '/backoffice/caisses',
        icon: 'ti ti-cash',
        permissions: ['cash-registers.view', 'cash-transfers.view'],
        matchPaths: ['/backoffice/caisses', '/backoffice/caisse-transfers'],
    },
];

export const GROUPS_TABS: PageTabDef[] = [
    {
        label: t('Groups'),
        href: '/backoffice/groups',
        icon: 'ti ti-users-group',
        permissions: ['groups.view'],
    },
    {
        label: t('Groups History'),
        href: '/backoffice/groups-historique',
        icon: 'ti ti-archive',
        permissions: ['groups.view'],
    },
];
