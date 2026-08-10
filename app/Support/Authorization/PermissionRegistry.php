<?php

declare(strict_types=1);

namespace App\Support\Authorization;

/**
 * Single source of truth for authorization (docs/authorization-architecture.md).
 *
 * - Permission machine names: `module.action` (kebab-case modules, dot actions).
 * - Permissions exist ONLY for implemented modules — when a new module ships
 *   (attendance, stock, reports…), add its permissions HERE, re-run
 *   `php artisan db:seed --class=RolesAndPermissionsSeeder`, then protect its
 *   routes/policies.
 * - French labels are used by the Backoffice UI (roles form, permissions list).
 *
 * Never copy permission strings around: reference them from here or use them
 * verbatim in `can()` / policies / route middleware.
 */
final class PermissionRegistry
{
    /**
     * Permissions grouped by module: [French group => [permission => French label]].
     *
     * @return array<string, array<string, string>>
     */
    public static function grouped(): array
    {
        return [
            'Tableau de bord' => [
                'dashboard.view' => 'Consulter le tableau de bord',
            ],
            'Centres' => [
                'centers.view' => 'Consulter les centres',
                'centers.create' => 'Créer un centre',
                'centers.update' => 'Modifier un centre',
                'centers.delete' => 'Supprimer un centre',
                'centers.access-all' => 'Accéder aux données de tous les centres',
            ],
            'Années scolaires' => [
                'academic-years.view' => 'Consulter les années scolaires',
                'academic-years.create' => 'Créer une année scolaire',
                'academic-years.update' => 'Modifier une année scolaire',
                'academic-years.delete' => 'Supprimer une année scolaire',
            ],
            'Salles' => [
                'rooms.view' => 'Consulter les salles',
                'rooms.create' => 'Créer une salle',
                'rooms.update' => 'Modifier une salle',
                'rooms.delete' => 'Supprimer une salle',
            ],
            'Frais' => [
                'fees.view' => 'Consulter le catalogue des frais',
                'fees.create' => 'Créer un frais',
                'fees.update' => 'Modifier un frais',
                'fees.delete' => 'Supprimer un frais',
            ],
            // Deliberately absent from every role in matrix() below — only
            // the Gate::before super-admin bypass can manage banks.
            'Banques' => [
                'banks.view' => 'Consulter le catalogue des banques',
                'banks.create' => 'Créer une banque',
                'banks.update' => 'Modifier une banque',
                'banks.delete' => 'Supprimer une banque',
            ],
            'Employés' => [
                'employees.view' => 'Consulter les employés',
                'employees.create' => 'Créer un employé',
                'employees.update' => 'Modifier un employé',
                'employees.delete' => 'Supprimer un employé',
            ],
            'Utilisateurs' => [
                'users.view' => 'Consulter les utilisateurs',
                'users.assign-roles' => 'Attribuer des rôles aux utilisateurs',
                'users.assign-permissions' => 'Attribuer des permissions directes aux utilisateurs',
            ],
            'Rôles' => [
                'roles.view' => 'Consulter les rôles',
                'roles.create' => 'Créer un rôle',
                'roles.update' => 'Modifier un rôle',
                'roles.delete' => 'Supprimer un rôle',
            ],
            'Permissions' => [
                'permissions.view' => 'Consulter la liste des permissions',
            ],
            'Étudiants' => [
                'students.view' => 'Consulter les étudiants',
                'students.create' => 'Créer un étudiant',
                'students.update' => 'Modifier un étudiant',
                'students.delete' => 'Supprimer un étudiant',
            ],
            'Inscriptions' => [
                'registrations.view' => 'Consulter les inscriptions',
                'registrations.create' => 'Créer une inscription',
                'registrations.update' => 'Modifier une inscription',
                'registrations.delete' => 'Supprimer une inscription',
                'registrations.manage-fees' => 'Gérer les frais d\'inscription',
            ],
            'Groupes' => [
                'groups.view' => 'Consulter les groupes et leur historique',
                'groups.create' => 'Créer un groupe',
                'groups.update' => 'Modifier un groupe',
                'groups.archive' => 'Clôturer un groupe (Fin de formation)',
            ],
            'Présences' => [
                'attendance.view' => 'Consulter les séances et présences',
                'attendance.create' => 'Créer une séance',
                'attendance.update' => 'Modifier une séance',
                'attendance.delete' => 'Supprimer une séance',
                'attendance.mark' => 'Faire l\'appel (enregistrer les présences)',
            ],
            'Caisses' => [
                'cash-registers.view' => 'Consulter les caisses',
                'cash-registers.create' => 'Créer une caisse',
                'cash-registers.update' => 'Modifier une caisse',
                'cash-registers.delete' => 'Supprimer une caisse',
            ],
            'Encaissements' => [
                'payments.view' => 'Consulter les encaissements',
                'payments.create' => 'Enregistrer un encaissement',
                'payments.update' => 'Modifier un encaissement',
            ],
            'Types de dépenses' => [
                'expense-types.view' => 'Consulter les types de dépenses',
                'expense-types.create' => 'Créer un type de dépense',
                'expense-types.update' => 'Modifier un type de dépense',
                'expense-types.delete' => 'Supprimer un type de dépense',
            ],
            'Dépenses' => [
                'expenses.view' => 'Consulter les dépenses',
                'expenses.create' => 'Enregistrer une dépense',
                'expenses.update' => 'Modifier une dépense',
            ],
            'Remboursements' => [
                'refunds.view' => 'Consulter les remboursements',
                'refunds.create' => 'Enregistrer un remboursement',
                'refunds.update' => 'Modifier un remboursement',
            ],
            'Transferts de caisse' => [
                'cash-transfers.view' => 'Consulter les transferts de caisse',
                'cash-transfers.create' => 'Demander un transfert de caisse',
                'cash-transfers.update' => 'Modifier ou annuler un transfert en attente',
                'cash-transfers.validate' => 'Valider un transfert de caisse',
            ],
            'Stock' => [
                'stock.view' => 'Consulter le stock',
                'stock.create' => 'Créer un article de stock',
                'stock.update' => 'Modifier un article de stock',
                'stock.delete' => 'Supprimer un article de stock',
                'stock.move' => 'Enregistrer un mouvement de stock',
            ],
            'Journal d\'audit' => [
                'audit-logs.view' => 'Consulter le journal d\'audit',
            ],
        ];
    }

    /**
     * Flat list: [permission => French label].
     *
     * @return array<string, string>
     */
    public static function permissions(): array
    {
        return array_merge(...array_values(self::grouped()));
    }

    /**
     * All machine names.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::permissions());
    }

    public static function exists(string $permission): bool
    {
        return array_key_exists($permission, self::permissions());
    }

    /**
     * Role catalogue: [machine name => French label].
     * Employee `categorie` is a related but SEPARATE concept — never used in
     * authorization checks, and no automatic category→role mapping exists.
     *
     * @return array<string, string>
     */
    public static function roles(): array
    {
        return [
            'super-admin' => 'Super administrateur',
            'director' => 'Directeur',
            'operations-director' => 'Directeur des opérations',
            'administrative-assistant' => 'Assistante administrative',
            'teacher' => 'Enseignant',
            'marketing-manager' => 'Responsable marketing',
        ];
    }

    /**
     * Default role → permission matrix (docs/roles-and-permissions.md).
     * `super-admin` is intentionally EMPTY: it bypasses everything via
     * Gate::before and must not depend on synced rows.
     *
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        return [
            'super-admin' => [],

            'director' => [
                'dashboard.view',
                'centers.view', 'centers.access-all',
                'academic-years.view', 'academic-years.create', 'academic-years.update',
                'rooms.view', 'rooms.create', 'rooms.update', 'rooms.delete',
                'fees.view', 'fees.create', 'fees.update', 'fees.delete',
                'employees.view', 'employees.create', 'employees.update', 'employees.delete',
                'users.view', 'users.assign-roles',
                'roles.view',
                'permissions.view',
                'students.view', 'students.create', 'students.update', 'students.delete',
                'registrations.view', 'registrations.create', 'registrations.update',
                'registrations.delete', 'registrations.manage-fees',
                'groups.view', 'groups.create', 'groups.update', 'groups.archive',
                'attendance.view', 'attendance.create', 'attendance.update', 'attendance.delete', 'attendance.mark',
                'cash-registers.view', 'cash-registers.create', 'cash-registers.update', 'cash-registers.delete',
                'payments.view', 'payments.create', 'payments.update',
                'expense-types.view', 'expense-types.create', 'expense-types.update', 'expense-types.delete',
                'expenses.view', 'expenses.create', 'expenses.update',
                'refunds.view', 'refunds.create', 'refunds.update',
                'cash-transfers.view', 'cash-transfers.create', 'cash-transfers.update', 'cash-transfers.validate',
                'stock.view', 'stock.create', 'stock.update', 'stock.delete', 'stock.move',
                'audit-logs.view',
            ],

            'operations-director' => [
                'dashboard.view',
                'centers.view', 'centers.access-all',
                'academic-years.view',
                'rooms.view', 'rooms.create', 'rooms.update', 'rooms.delete',
                'fees.view', 'fees.create', 'fees.update', 'fees.delete',
                'employees.view', 'employees.update',
                'students.view', 'students.create', 'students.update', 'students.delete',
                'registrations.view', 'registrations.create', 'registrations.update',
                'registrations.delete', 'registrations.manage-fees',
                'groups.view', 'groups.create', 'groups.update', 'groups.archive',
                'attendance.view', 'attendance.create', 'attendance.update', 'attendance.mark',
                'cash-registers.view',
                'payments.view',
                'expense-types.view',
                'expenses.view',
                'refunds.view',
                'cash-transfers.view',
                'stock.view', 'stock.create', 'stock.update', 'stock.delete', 'stock.move',
            ],

            // Center-scoped day-to-day operator: records payments/expenses and
            // requests transfers, but validation stays at director level and
            // she never sees other centers (no centers.access-all).
            'administrative-assistant' => [
                'dashboard.view',
                'centers.view',
                'academic-years.view',
                'rooms.view',
                'students.view', 'students.create', 'students.update',
                'registrations.view', 'registrations.create', 'registrations.update', 'registrations.manage-fees',
                'groups.view',
                'attendance.view', 'attendance.create', 'attendance.mark',
                'cash-registers.view',
                'payments.view', 'payments.create',
                'expense-types.view',
                'expenses.view', 'expenses.create',
                'refunds.view', 'refunds.create',
                'cash-transfers.view', 'cash-transfers.create',
                'stock.view', 'stock.move',
            ],

            // Academic scope only — no financial data.
            'teacher' => [
                'dashboard.view',
                'groups.view',
                'students.view',
                'attendance.view', 'attendance.create', 'attendance.mark',
            ],

            // Prospects/marketing-reports permissions will be added with those
            // modules; until then, read-only funnel-relevant data.
            'marketing-manager' => [
                'dashboard.view',
                'centers.view',
                'students.view',
                'registrations.view',
            ],
        ];
    }
}
