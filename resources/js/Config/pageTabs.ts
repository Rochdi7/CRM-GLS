import type { PageTabDef } from '@/Components/Navigation/PageTabs';
import { t } from '@/Lib/i18n';

/**
 * Module tab groups (wimschool-inspired): sibling modules surface as
 * page-level tabs. Finance pages own their bars inline (Paiements'
 * view tabs and the Dépenses/Remboursements/Types bar are query- or
 * state-driven, which PageTabs' path matching can't express).
 * Permissions mirror the sidebar gates; the server enforces everything.
 */
/**
 * Étudiants + son outil de réparation. « Fusion de fiches » n'a pas d'entrée
 * de barre latérale (cf. backofficeNavigation.ts) : cet onglet est son seul
 * point d'entrée visible, et il ne s'affiche que pour qui détient
 * `students.merge` — c'est-à-dire, la permission étant dans
 * PermissionRegistry::superAdminOnly(), le super-admin seul. PageTabs masque
 * la barre entière quand un seul onglet reste visible, donc les autres
 * utilisateurs ne voient rien de plus qu'avant.
 */
export const STUDENTS_TABS: PageTabDef[] = [
    {
        label: t('Students'),
        href: '/backoffice/students',
        icon: 'ti ti-school',
        permissions: ['students.view'],
        // ⚠ PageTabs marque actif sur un PRÉFIXE (`currentPath === path ||
        // startsWith(path + '/')`). Sans restriction, « /backoffice/students »
        // attraperait aussi « /backoffice/students/fusion » et les DEUX
        // onglets s'allumeraient. On force donc l'égalité stricte en ne
        // listant que l'index lui-même — la barre n'est rendue que sur ces
        // deux pages, jamais sur une fiche /students/{id}.
        matchPaths: ['/backoffice/students'],
        exact: true,
    },
    {
        label: t('Merge records'),
        href: '/backoffice/students/fusion',
        icon: 'ti ti-arrow-merge',
        permissions: ['students.merge'],
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
