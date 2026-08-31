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
     * The ability that opens EVERY centre (« Tous les centres »). It is an
     * ability NAME only — answered by Gate::before for super-admins — and is
     * NEVER grantable: not to a role (preset or custom), not as a direct
     * permission. « Centres affectés » on the employee form is the one
     * authority on centre reach for everyone else (CLAUDE.md §16,
     * 24/08/2026). `grantable()` excludes it, both Roles/Autorisations
     * Form Requests validate against `grantable()`, and
     * RolesAndPermissionsSeeder strips any stale grant on every run.
     */
    public const GLOBAL_CENTER_ACCESS = 'centers.access-all';

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
            // Deliberately absent from every role in matrix() below — only
            // the Gate::before super-admin bypass can manage them.
            "Raisons d'annulation" => [
                'cancellation-reasons.view' => "Consulter les raisons d'annulation ou archivage",
                'cancellation-reasons.create' => "Créer une raison d'annulation",
                'cancellation-reasons.update' => "Modifier une raison d'annulation",
                'cancellation-reasons.delete' => "Supprimer une raison d'annulation",
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
            // Deliberately absent from every role preset in matrix() below
            // (superAdminOnly()) — a legacy import writes thousands of
            // students / inscriptions / encaissements in one pass.
            'Import de données' => [
                'import.view' => 'Consulter les imports de données',
                'import.create' => "Importer des données depuis l'ancien CRM",
            ],
            'Inscriptions' => [
                'registrations.view' => 'Consulter les inscriptions',
                'registrations.create' => 'Créer une inscription',
                'registrations.update' => 'Modifier une inscription',
                'registrations.delete' => 'Supprimer une inscription',
                'registrations.manage-fees' => 'Gérer les frais d\'inscription',
                'registrations.change-group' => 'Changer le groupe d\'une inscription',
            ],
            'Groupes' => [
                'groups.view' => 'Consulter les groupes et leur historique',
                'groups.create' => 'Créer un groupe',
                'groups.update' => 'Modifier un groupe',
                // Deliberately SEPARATE from groups.update: every role
                // holds it (31/08/2026 business decision) so anyone can
                // fix or clear a group's teacher without also gaining the
                // right to rename it, move its salle or touch its frais.
                'groups.change-teacher' => "Changer ou retirer l'enseignant d'un groupe",
                'groups.archive' => 'Clôturer un groupe (Fin de formation)',
                'groups.move-year' => 'Déplacer un groupe vers une autre année scolaire (avec ses inscriptions, séances et paiements)',
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
            // The « Comptes de caisse » tab of Gestion de la caisse — where
            // every dirham sits, one account per row.
            //
            // `cash-accounts.view` is held by the five management roles
            // (31/08/2026). It is NOT a global view for them: GetComptesCaisse
            // follows the top-bar context like every other screen, and
            // « Tous les centres » is super-admin-only by construction
            // (GLOBAL_CENTER_ACCESS), so a manager sees exactly the centres
            // assigned on their employee form.
            //
            // create/update/delete stay super-admin-only (superAdminOnly()):
            // method accounts are provisioned WITH the centre and a hand-made
            // duplicate would split a centre's money across two rows.
            //
            // Distinct from `cash-registers.*`, which stays the center-scoped
            // "consult a till" permission every finance role keeps.
            'Comptes de caisse' => [
                'cash-accounts.view' => 'Consulter les comptes de caisse',
                'cash-accounts.create' => 'Créer un compte de caisse',
                'cash-accounts.update' => 'Modifier un compte de caisse',
                'cash-accounts.delete' => 'Supprimer un compte de caisse',
            ],
            'Encaissements' => [
                'payments.view' => 'Consulter les encaissements',
                'payments.create' => 'Enregistrer un encaissement',
                'payments.update' => 'Modifier un encaissement',
                // Correcting the DATE of a recorded payment relocates the row
                // in the caisse journal and in the annual summary — after a
                // month may already have been reconciled. Deliberately in NO
                // role preset (superAdminOnly() below): only a super-admin
                // may re-date money. Every other role keeps `payments.update`
                // for the note / cheque identity fields (30/08/2026).
                'payments.update-date' => "Modifier la date de paiement d'un encaissement",
                // Deliberately in NO role preset below. Money records are
                // append-only by default (CLAUDE.md §11); a super-admin grants
                // this one by hand when a real correction case needs it.
                'payments.delete' => 'Supprimer un encaissement',
                // Bulk re-allocation of already-recorded payments to another
                // group/année. Like groups.move-year it rewrites which
                // registration (and therefore which year) money belongs to,
                // so it is super-admin only — see superAdminOnly() below.
                'payments.reallocate' => 'Déplacer des encaissements vers un autre groupe / une autre année',
            ],
            'Recouvrement' => [
                'collections.view' => 'Consulter la gestion des recouvrements',
            ],
            // Deliberately absent from every role in matrix() below — only
            // the Gate::before super-admin bypass may flip system switches.
            'Système' => [
                'system-settings.view' => 'Consulter les paramètres système',
                'system-settings.update' => 'Modifier les paramètres système',
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
                // Approving is what actually debits the till when
                // « Validation des dépenses » is ON (Paramètres → Système).
                // Deliberately in NO role preset below — like payments.delete,
                // a super-admin grants it by hand.
                'expenses.approve' => 'Approuver ou refuser une dépense',
            ],
            'Remboursements' => [
                'refunds.view' => 'Consulter les remboursements',
                'refunds.create' => 'Enregistrer un remboursement',
                'refunds.update' => 'Modifier un remboursement',
            ],
            'Chèques' => [
                'cheques.view' => 'Consulter les chèques',
                'cheques.create' => 'Enregistrer un chèque',
                'cheques.update' => 'Modifier un chèque',
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
            'Types de stock' => [
                'stock-types.view' => 'Consulter les types de stock',
                'stock-types.create' => 'Créer un type de stock',
                'stock-types.update' => 'Modifier un type de stock',
                'stock-types.delete' => 'Supprimer un type de stock',
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
     * Machine names that MAY be granted to a role or a user — every
     * permission except GLOBAL_CENTER_ACCESS (super-admin by construction).
     *
     * @return list<string>
     */
    public static function grantable(): array
    {
        return array_values(array_filter(
            self::names(),
            static fn (string $name): bool => $name !== self::GLOBAL_CENTER_ACCESS,
        ));
    }

    public static function isGrantable(string $permission): bool
    {
        return $permission !== self::GLOBAL_CENTER_ACCESS && self::exists($permission);
    }

    /**
     * `grouped()` without the un-grantable abilities — what the Roles form
     * and the Autorisations screen offer as checkboxes.
     *
     * @return array<string, array<string, string>>
     */
    public static function groupedGrantable(): array
    {
        $groups = [];

        foreach (self::grouped() as $group => $permissions) {
            unset($permissions[self::GLOBAL_CENTER_ACCESS]);

            if ($permissions !== []) {
                $groups[$group] = $permissions;
            }
        }

        return $groups;
    }

    /**
     * Role catalogue: [machine name => French label].
     *
     * One role per `Employee::CATEGORIES` job title, so every employee the
     * Employees form can create already has a matching role to grant on the
     * Autorisations screen. `Autre` deliberately has NO role: an employee
     * with no defined post gets no access until someone grants one by hand.
     *
     * Employee `categorie` remains a SEPARATE concept — never used in an
     * authorization check. The names simply line up so granting is obvious;
     * the mapping used when seeding staff lives in
     * `Database\Seeders\GlsStaffSeeder::ROLE_PAR_CATEGORIE`, and nothing in
     * the app derives permissions from `categorie` at runtime.
     *
     * @return array<string, string>
     */
    public static function roles(): array
    {
        return [
            'super-admin' => 'Super administrateur',
            'director' => 'Directeur',
            'operations-director' => 'Directeur des opérations',
            'financial-director' => 'Directeur financier',
            'quality-director' => 'Directeur Qualité et Amélioration continue',
            'pedagogical-director' => 'Directrice pédagogique',
            'accountant' => 'Comptable',
            'consultant' => 'Consultant',
            'hr-manager' => 'Responsable RH',
            'marketing-manager' => 'Responsable marketing',
            'administrative-manager' => 'Responsable administrative',
            'administrative-assistant' => 'Assistante administrative',
            'teacher' => 'Enseignant',
        ];
    }

    /**
     * Default role for an employee job title (`Employee::CATEGORIES` value),
     * or null for « Autre » / unknown — no defined post, no access until a
     * role is granted by hand on the Autorisations screen.
     *
     * Single source of truth for the catégorie → role DEFAULT, used by
     * `EmployeeObserver` (UI/import creations) and `GlsStaffSeeder` alike.
     * It is a DEFAULT at creation time only: nothing re-derives permissions
     * from `categorie` afterwards, and a role changed on the Autorisations
     * screen is never overwritten (both callers assign only when the user
     * holds no role at all).
     */
    public static function defaultRoleFor(string $categorie): ?string
    {
        return match ($categorie) {
            \App\Models\Employee::CATEGORIE_DIRECTEUR => 'director',
            \App\Models\Employee::CATEGORIE_DIRECTEUR_OPERATIONS => 'operations-director',
            \App\Models\Employee::CATEGORIE_DIRECTEUR_FINANCIER => 'financial-director',
            \App\Models\Employee::CATEGORIE_DIRECTEUR_QUALITE => 'quality-director',
            \App\Models\Employee::CATEGORIE_DIRECTRICE_PEDAGOGIQUE => 'pedagogical-director',
            \App\Models\Employee::CATEGORIE_COMPTABLE => 'accountant',
            \App\Models\Employee::CATEGORIE_CONSULTANT => 'consultant',
            \App\Models\Employee::CATEGORIE_RESPONSABLE_RH => 'hr-manager',
            \App\Models\Employee::CATEGORIE_RESPONSABLE_ADMINISTRATIVE => 'administrative-manager',
            \App\Models\Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE => 'administrative-assistant',
            \App\Models\Employee::CATEGORIE_ENSEIGNANT => 'teacher',
            \App\Models\Employee::CATEGORIE_RESPONSABLE_MARKETING => 'marketing-manager',
            // The only catégorie that maps to super-admin: the person who
            // administers the system itself. Not a preset in roles() —
            // Role::SUPER_ADMIN bypasses every permission via Gate::before.
            \App\Models\Employee::CATEGORIE_RESPONSABLE_SYSTEME => \App\Models\Role::SUPER_ADMIN,
            default => null,
        };
    }

    /**
     * Permissions NO role preset may hold — reachable only through the
     * `super-admin` Gate::before bypass, or granted one at a time by hand on
     * the Autorisations screen when a real case needs it.
     *
     * Two families:
     *
     * 1. **Every destructive `*.delete`** — deleting a student, an
     *    inscription, an employee, une salle, un frais, un article de
     *    stock… is a super-admin act. A director who mis-typed a record
     *    edits it; only a super-admin erases it. `groups.archive` is NOT
     *    here: archiving a group is the sanctioned "close it" path that
     *    writes a `groups_historique` snapshot and never deletes
     *    (CLAUDE.md 11), so it stays with the roles that run groups.
     * 2. **Pre-existing super-admin-only abilities** kept from the previous
     *    matrix: system switches, banks, cancellation reasons, the global
     *    cash-accounts view and `expenses.approve`. (`payments.delete` needs
     *    no entry of its own — family 1 already covers it.)
     *
     * `matrix()` filters every preset through this list, so a new `*.delete`
     * added to `grouped()` later is locked down automatically — it can never
     * leak into a role by being forgotten here.
     *
     * @return list<string>
     */
    public static function superAdminOnly(): array
    {
        // ⚠ The ONE deliberately delegated delete (31/08/2026, business
        // decision): every role may delete an inscription. It is the only
        // record in the app that can be created by mistake with nothing
        // attached to it yet — a wrong student, a wrong group, caught the
        // same minute — and the front desk that created it must be able to
        // undo it without calling a super-admin. It stays safe because a
        // registration that received ANY money is refused outright
        // (InscriptionController@destroy checks the payments first, and the
        // FK restrict on encaissements.inscription_fee_id backs it up), and
        // the deletion is journaled like every other change. Cancelling
        // (with a reason, keeping the history) remains the right move for a
        // registration that actually happened.
        $delegatedDeletes = ['registrations.delete'];

        $deletes = array_values(array_filter(
            self::names(),
            static fn (string $name): bool => str_ends_with($name, '.delete')
                && ! in_array($name, $delegatedDeletes, true),
        ));

        return array_values(array_unique(array_merge($deletes, [
            'system-settings.view', 'system-settings.update',
            'banks.view', 'banks.create', 'banks.update',
            'cancellation-reasons.view', 'cancellation-reasons.create', 'cancellation-reasons.update',
            // ⚠ `cash-accounts.view` is NOT here since 31/08/2026: the five
            // management roles read the « Comptes de caisse » tab (see
            // $comptesCaisse in presets()). CREATING or EDITING an account
            // stays super-admin-only — one dirham is one row, and the
            // method accounts are provisioned with the centre, never by hand
            // (CLAUDE.md §11).
            'cash-accounts.create', 'cash-accounts.update',
            'expenses.approve',
            // Re-dating a recorded payment moves it between reconciled
            // periods — see payments.update-date in grouped() (30/08/2026).
            'payments.update-date',
            // Moving a group between années rewrites the year of every
            // inscription, séance (and therefore payment) hanging off it —
            // a history-altering act reserved to super-admins (24/08/2026).
            'groups.move-year',
            // Re-allocating a payment changes the inscription — and therefore
            // the année — the money is booked against. Same history-altering
            // class as groups.move-year (26/08/2026).
            'payments.reallocate',
            // « Centres affectés » is the ONE authority on which centers a
            // user reaches (employee_etablissement pivot, CLAUDE.md §16) —
            // no ROLE may widen it to the whole network. Someone who needs
            // more centers gets them assigned on the employee form. Since
            // 24/08/2026 it is not hand-grantable either (see
            // GLOBAL_CENTER_ACCESS / grantable()): ONLY super-admins, via
            // Gate::before, ever see « Tous les centres ».
            self::GLOBAL_CENTER_ACCESS,
            // L'import de l'ancien CRM ecrit en masse des etudiants, des
            // inscriptions et de l'argent (encaissements) : une seule passe
            // peut creer des milliers de lignes qui ne se suppriment pas
            // (CLAUDE.md §11, les enregistrements d'argent ne sont jamais
            // supprimes). Reserve aux super-admins depuis le 31/08/2026 ;
            // la route reste active et accessible par URL directe.
            'import.view', 'import.create',
        ])));
    }

    /**
     * Default role → permission matrix (docs/roles-and-permissions.md).
     *
     * `super-admin` is intentionally EMPTY: it bypasses everything via
     * Gate::before and must not depend on synced rows.
     *
     * Every other preset is filtered through `superAdminOnly()`, so listing
     * a `*.delete` in `presets()` has no effect — by design. Presets are
     * written as the full job description and let the filter do the locking:
     * the day a delete is deliberately delegated, it is one line removed
     * from `superAdminOnly()`, not a re-audit of thirteen roles.
     *
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        $forbidden = array_flip(self::superAdminOnly());

        $presets = self::presets();

        foreach ($presets as $role => $permissions) {
            if ($role === 'super-admin') {
                continue;
            }

            $presets[$role] = array_values(array_filter(
                array_unique($permissions),
                static fn (string $p): bool => ! isset($forbidden[$p]) && self::exists($p),
            ));
        }

        return $presets;
    }

    /**
     * Raw, unfiltered role presets — the intent per job title, before
     * `matrix()` strips the super-admin-only abilities.
     *
     * @return array<string, list<string>>
     */
    private static function presets(): array
    {
        // Front-desk operational baseline (30/08/2026 rework).
        //
        // What this scope MAY MODIFY is deliberately limited to the four
        // academic objects a front desk owns: étudiants, inscriptions,
        // groupes and séances (emploi du temps). Everything financial is
        // CREATE-ONLY: a cashier records an encaissement, an avance, un
        // chèque, une dépense, un remboursement, un transfert — and corrects
        // a mistake with a compensating entry, never by rewriting the
        // document (CLAUDE.md §11: money records are append-only, and no
        // role holds any `*.delete` — superAdminOnly() strips them all).
        //
        // `payments.update` is the ONE financial edit kept, because the note
        // and the chèque identity of a payment genuinely need correcting;
        // `payments.update-date` is NOT here (super-admin only), so a
        // front-desk edit can never re-date money into a reconciled month.
        //
        // Converting / applying an avance and registering a chèque stay in
        // scope: those are essential day-to-day payment operations, not
        // corrections (they create rows, they never rewrite one).
        //
        // Stock is READ-ONLY here — since 30/08/2026 the physical inventory
        // and its mouvements belong to the marketing manager alone (plus the
        // super-admin bypass), so a book leaves the shelf in exactly one
        // place.
        //
        // Shared verbatim by consultant + assistante administrative, which
        // the business treats as the same job: their access can never
        // silently drift apart.
        $operations = [
            'dashboard.view',
            'centers.view',
            'academic-years.view',
            // Salles : creation ET modification ouvertes a TOUS les roles
            // (31/08/2026, demande metier) — l'ecran Parametres > Salles est
            // de la logistique quotidienne, pas un reglage sensible. La
            // portee reste celle des « Centres affectes » de l'employe :
            // Store/UpdateSalleRequest valident `etablissement_id` contre
            // GetAccessibleCenterOptions, et SallePolicy passe par
            // CenterAccessService — donc personne ne cree ou ne modifie une
            // salle hors de SES centres. `rooms.delete` reste super-admin
            // (aucun preset ne porte de `*.delete`, cf. superAdminOnly()).
            'rooms.view', 'rooms.create', 'rooms.update',
            'fees.view',
            'students.view', 'students.create', 'students.update',
            'registrations.view', 'registrations.create', 'registrations.update',
            // Delegated to every role (31/08/2026) — only a registration with
            // no payment at all can actually be deleted, see superAdminOnly().
            'registrations.delete',
            'registrations.manage-fees', 'registrations.change-group',
            'groups.view', 'groups.create', 'groups.update', 'groups.archive',
            'groups.change-teacher',
            'attendance.view', 'attendance.create', 'attendance.update', 'attendance.mark',
            'cash-registers.view',
            'payments.view', 'payments.create', 'payments.update',
            'collections.view',
            'expense-types.view',
            'expenses.view', 'expenses.create',
            'refunds.view', 'refunds.create',
            'cheques.view', 'cheques.create',
            'cash-transfers.view', 'cash-transfers.create',
            'stock.view',
            'stock-types.view',
        ];

        // The five management roles (directeur, directeur des opérations,
        // directeur financier, directrice pédagogique, responsable RH) share
        // the front-desk scope and add back the finance EDITS a manager
        // arbitrates — `expenses.update` above all, the one the business
        // named explicitly (30/08/2026).
        //
        // ⚠ `refunds.update` / `cheques.update` / `cash-transfers.update`
        // are the other corrective edits on documents these roles own.
        // `encaissements.methode` is NOT among them at any level: it is
        // frozen with the row by a money invariant (CLAUDE.md §11 — it
        // decided WHICH caisse account was credited, so changing the label
        // without moving the money desynchronises the till). Correcting a
        // wrong method is a remboursement + a new encaissement, and
        // `EncaissementController@update` refuses a changed value for
        // everyone, super-admin included.
        $managementEdits = [
            'expenses.update',
            'refunds.update',
            'cheques.update',
            'cash-transfers.update',
            // Read the « Comptes de caisse » tab — where every dirham sits
            // (31/08/2026). Scoped like every other screen: the tab follows
            // the top-bar centre, and « Tous les centres » is super-admin
            // only, so a manager sees only their affected centres. Creating
            // or editing an account stays super-admin-only.
            'cash-accounts.view',
        ];

        // Read-only across every finance screen — the accounting/oversight
        // baseline the finance roles build on.
        $financeReadOnly = [
            'dashboard.view',
            'centers.view',
            'academic-years.view',
            // Voir §$operations : les salles sont ouvertes a tous les roles,
            // scopees aux centres affectes.
            'rooms.view', 'rooms.create', 'rooms.update',
            'fees.view',
            'students.view',
            'registrations.view',
            'registrations.delete',
            'groups.view',
            'groups.change-teacher',
            'cash-registers.view',
            'payments.view',
            'collections.view',
            'expense-types.view',
            'expenses.view',
            'refunds.view',
            'cheques.view',
            'cash-transfers.view',
            'stock.view',
            'stock-types.view',
        ];

        return [
            'super-admin' => [],

            // Runs a center end to end, plus the catalogs and the staff file.
            // Validates cash transfers into their own till (the recipient
            // rule still applies — CLAUDE.md §11) and reads the audit journal.
            //
            // ⚠ Deliberately NO centers.access-all — like EVERY role now
            // (it sits in superAdminOnly()): « Centres affectés » on the
            // employee form is the ONE authority on which centers a user
            // reaches. Someone who needs wider reach gets more centers
            // assigned, or a super-admin hand-grants it on the Autorisations
            // screen.
            'director' => [
                ...$operations,
                ...$managementEdits,
                'academic-years.create', 'academic-years.update',
                'fees.create', 'fees.update',
                'employees.view', 'employees.update',
                'users.view', 'users.assign-roles',
                'roles.view',
                'permissions.view',
                'cash-registers.create', 'cash-registers.update',
                'expense-types.create', 'expense-types.update',
                'cash-transfers.validate',
                'audit-logs.view',
            ],

            // Cross-center operations: everything academic and logistic,
            // plus the front-desk money scope of the centers it runs and the
            // management edits.
            'operations-director' => [
                ...$operations,
                ...$managementEdits,
                'fees.create', 'fees.update',
                'employees.view', 'employees.update',
                'users.view',
                'audit-logs.view',
            ],

            // Owns the money across every center: records the finance
            // documents, corrects the ones a manager arbitrates, and
            // validates transfers into their own till. Approving dépenses
            // and re-dating a payment stay super-admin-only.
            'financial-director' => [
                ...$operations,
                ...$managementEdits,
                'employees.view',
                'cash-registers.create', 'cash-registers.update',
                'expense-types.create', 'expense-types.update',
                'cash-transfers.validate',
                'audit-logs.view',
            ],

            // Same money scope as before: books the finance entries, does
            // not arbitrate them. Deliberately UNCHANGED by the 30/08/2026
            // rework — the accountant's job description already matched.
            'accountant' => [
                ...$financeReadOnly,
                'payments.create', 'payments.update',
                'expenses.create', 'expenses.update',
                'refunds.create', 'refunds.update',
                'cheques.create', 'cheques.update',
                'cash-transfers.create', 'cash-transfers.update',
                'audit-logs.view',
            ],

            // Oversight across all centers: sees everything, including the
            // audit journal, and changes nothing.
            'quality-director' => [
                ...$financeReadOnly,
                'employees.view',
                'attendance.view',
                'users.view',
                'roles.view',
                'permissions.view',
                'audit-logs.view',
            ],

            // Academic authority — groups, séances, teachers and the fee
            // catalog that prices them — on the shared front-desk base, plus
            // the management edits.
            'pedagogical-director' => [
                ...$operations,
                ...$managementEdits,
                'fees.create', 'fees.update',
                'employees.view',
                'audit-logs.view',
            ],

            // Front desk / sales: the full operational scope — inscriptions,
            // groupes, étudiants, séances, encaissements, avances, chèques —
            // in their OWN center only. No delete anywhere, no finance edit
            // beyond a payment's note, no stock movement.
            'consultant' => $operations,

            // The business treats this as the SAME job as the consultant:
            // identical scope, on purpose (30/08/2026).
            'administrative-assistant' => $operations,

            // The assistante's scope plus the staff file and the audit
            // journal for their center.
            'administrative-manager' => [
                ...$operations,
                'employees.view', 'employees.update',
                'users.view',
                'audit-logs.view',
            ],

            // Staff file plus the shared front-desk scope and the management
            // edits (30/08/2026 — the business asked for the same base as
            // the other management roles).
            'hr-manager' => [
                ...$operations,
                ...$managementEdits,
                'employees.view', 'employees.update',
                'users.view',
                'audit-logs.view',
            ],

            // ⚠ The ONE role that runs the physical inventory (30/08/2026).
            // Stock articles and their mouvements were removed from every
            // other preset so a book leaves the shelf in exactly one place;
            // only this role and the super-admin bypass manage them.
            // Prospects/marketing-reports permissions will be added with
            // those modules.
            'marketing-manager' => [
                'dashboard.view',
                'centers.view',
                'rooms.view', 'rooms.create', 'rooms.update',
                'students.view',
                'registrations.view',
                'registrations.delete',
                'groups.view',
                'groups.change-teacher',
                'stock.view', 'stock.create', 'stock.update', 'stock.move',
                'stock-types.view', 'stock-types.create', 'stock-types.update',
            ],

            // Academic scope only — no financial data.
            'teacher' => [
                'dashboard.view',
                'rooms.view', 'rooms.create', 'rooms.update',
                'groups.view',
                'groups.change-teacher',
                'students.view',
                'registrations.view', 'registrations.delete',
                'attendance.view', 'attendance.create', 'attendance.mark',
            ],
        ];
    }
}
