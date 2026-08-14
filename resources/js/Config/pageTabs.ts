import type { PageTabDef } from '@/Components/Navigation/PageTabs';
import { t } from '@/Lib/i18n';

/**
 * Module tab groups (wimschool-inspired): sibling modules surface as
 * page-level tabs. Finance pages own their bars inline (Paiements'
 * view tabs and the Dépenses/Remboursements/Types bar are query- or
 * state-driven, which PageTabs' path matching can't express).
 * Permissions mirror the sidebar gates; the server enforces everything.
 */
export const GROUPS_TABS: PageTabDef[] = [
    {
        label: t('Groups'),
        href: '/backoffice/groups',
        icon: 'fa fa-people-group',
        permissions: ['groups.view'],
    },
    {
        label: t('Groups History'),
        href: '/backoffice/groups-historique',
        icon: 'fa fa-box-archive',
        permissions: ['groups.view'],
    },
];
