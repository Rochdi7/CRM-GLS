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
        // Étudiants archivés (19/10/2026) : fiches mises en pause (retour
        // prévu), sorties de la liste courante — même page, route à part.
        label: t('Archived students'),
        href: '/backoffice/students/archives',
        icon: 'ti ti-archive',
        permissions: ['students.view'],
    },
    {
        // Transferts d'étudiants entre centres (25/09/2026) : la liste des
        // demandes, à côté des fiches — c'est depuis une fiche qu'on demande.
        label: t('Student transfers'),
        href: '/backoffice/student-transfers',
        icon: 'ti ti-arrows-exchange',
        permissions: ['student-transfers.view'],
    },
    {
        // Remise d'un livre en masse à un groupe (12/10/2026).
        label: t('Books'),
        href: '/backoffice/students/livres',
        icon: 'ti ti-book',
        permissions: ['registrations.manage-fees'],
    },
    {
        label: t('Merge records'),
        href: '/backoffice/students/fusion',
        icon: 'ti ti-arrow-merge',
        permissions: ['students.merge'],
    },
];

/**
 * Inscriptions + sa corbeille (19/10/2026) : les dossiers supprimés, par qui
 * et quand. « Inscriptions » est en correspondance exacte — sinon le préfixe
 * attraperait /backoffice/inscriptions/corbeille et les deux s'allumeraient.
 */
export const INSCRIPTIONS_TABS: PageTabDef[] = [
    {
        label: t('Registrations'),
        href: '/backoffice/inscriptions',
        icon: 'ti ti-clipboard-list',
        permissions: ['registrations.view'],
        matchPaths: ['/backoffice/inscriptions'],
        exact: true,
    },
    {
        label: t('Trash'),
        href: '/backoffice/inscriptions/corbeille',
        icon: 'ti ti-trash',
        permissions: ['registrations.view'],
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
