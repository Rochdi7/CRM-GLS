<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Authorization;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Support\Authorization\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class RolesAndPermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_seeder_creates_all_roles_with_french_labels(): void
    {
        $this->assertSame(count(PermissionRegistry::roles()), Role::count());

        foreach (PermissionRegistry::roles() as $name => $label) {
            $role = Role::findByName($name);
            $this->assertSame($label, $role->label, "Label mismatch for [$name]");
        }
    }

    public function test_seeder_creates_all_registry_permissions(): void
    {
        $this->assertSame(count(PermissionRegistry::names()), Permission::count());

        foreach (PermissionRegistry::names() as $name) {
            $this->assertDatabaseHas('permissions', ['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $roleCount = Role::count();
        $permissionCount = Permission::count();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame($roleCount, Role::count());
        $this->assertSame($permissionCount, Permission::count());
    }

    public function test_roles_receive_the_expected_permission_sets(): void
    {
        $director = Role::findByName('director');
        $this->assertTrue($director->hasPermissionTo('cash-transfers.validate'));
        // « Centres affectés » is the ONE authority on center reach: NO role
        // preset may carry centers.access-all (it sits in superAdminOnly()).
        // Wider reach = more centers assigned on the employee form, or a
        // super-admin hand-grants the permission to one user.
        foreach (array_keys(PermissionRegistry::roles()) as $name) {
            $this->assertFalse(
                Role::findByName($name)->hasPermissionTo('centers.access-all'),
                "[$name] must not hold centers.access-all",
            );
        }
        $this->assertTrue($director->hasPermissionTo('groups.archive'));
        $this->assertFalse($director->hasPermissionTo('roles.create'));

        $teacher = Role::findByName('teacher');
        $this->assertEqualsCanonicalizing(
            [
                'dashboard.view', 'groups.view', 'students.view',
                'attendance.view', 'attendance.create', 'attendance.mark',
            ],
            $teacher->permissions()->pluck('name')->all(),
        );

        $assistant = Role::findByName('administrative-assistant');
        $this->assertTrue($assistant->hasPermissionTo('payments.create'));
        $this->assertFalse($assistant->hasPermissionTo('centers.access-all'));
        $this->assertFalse($assistant->hasPermissionTo('cash-transfers.validate'));
        $this->assertFalse($assistant->hasPermissionTo('roles.view'));
    }

    /**
     * The operational scope the front-desk roles were created for:
     * inscriptions, groupes, étudiants, encaissements and the caisse — in
     * their own center only, and identical across the three roles that share
     * the `$operations` preset.
     */
    public function test_front_office_roles_share_the_full_operational_scope(): void
    {
        $expected = [
            'students.view', 'students.create', 'students.update',
            'registrations.view', 'registrations.create', 'registrations.update',
            'registrations.manage-fees', 'registrations.change-group',
            'groups.view', 'groups.create', 'groups.update', 'groups.archive',
            'cash-registers.view',
            'payments.view', 'payments.create', 'payments.update',
            'expenses.view', 'expenses.create',
            'cheques.view', 'cheques.create',
            'cash-transfers.view', 'cash-transfers.create',
            'collections.view',
        ];

        foreach (['consultant', 'administrative-assistant'] as $name) {
            $role = Role::findByName($name);

            foreach ($expected as $permission) {
                $this->assertTrue(
                    $role->hasPermissionTo($permission),
                    "[$name] should hold [$permission]",
                );
            }

            // Center-scoped: they never see other centers…
            $this->assertFalse($role->hasPermissionTo('centers.access-all'), $name);
            // …and never arbitrate their own money.
            $this->assertFalse($role->hasPermissionTo('cash-transfers.validate'), $name);
            $this->assertFalse($role->hasPermissionTo('expenses.approve'), $name);
        }

        $this->assertEqualsCanonicalizing(
            Role::findByName('consultant')->permissions()->pluck('name')->all(),
            Role::findByName('administrative-assistant')->permissions()->pluck('name')->all(),
        );
    }

    /**
     * 30/08/2026 — the physical inventory belongs to ONE role.
     *
     * A book must leave the shelf in exactly one place, so `stock.create` /
     * `stock.update` / `stock.move` (and the stock-types catalog) were pulled
     * out of every other preset. Everyone else keeps `stock.view`: a front
     * desk still needs to see whether a title is available.
     */
    public function test_only_the_marketing_manager_manages_stock(): void
    {
        $managing = ['stock.create', 'stock.update', 'stock.move',
            'stock-types.create', 'stock-types.update'];

        $marketing = Role::findByName('marketing-manager');

        foreach ($managing as $permission) {
            $this->assertTrue(
                $marketing->hasPermissionTo($permission),
                "marketing-manager should hold [$permission]",
            );
        }

        foreach (Role::with('permissions')->get() as $role) {
            if (in_array($role->name, ['marketing-manager', Role::SUPER_ADMIN], true)) {
                continue;
            }

            $held = $role->permissions->pluck('name')->all();

            $this->assertEmpty(
                array_intersect($held, $managing),
                "Role [{$role->name}] must not manage stock",
            );
        }

        // …and a super-admin still does, via Gate::before.
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->assertTrue($user->can('stock.move'));
    }

    /**
     * 30/08/2026 — re-dating a recorded payment is super-admin only.
     *
     * Moving `date_paiement` relocates the row in the caisse journal and in
     * the annual summary, possibly into a month already reconciled. Every
     * role keeps `payments.update` for the note / chèque identity fields.
     */
    public function test_no_role_may_change_a_payment_date(): void
    {
        foreach (Role::with('permissions')->get() as $role) {
            $this->assertFalse(
                $role->hasPermissionTo('payments.update-date'),
                "Role [{$role->name}] must not re-date a payment",
            );
        }

        // The roles that correct payments still hold the ordinary edit.
        foreach (['consultant', 'administrative-assistant', 'accountant', 'director'] as $name) {
            $this->assertTrue(Role::findByName($name)->hasPermissionTo('payments.update'), $name);
        }

        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->assertTrue($user->can('payments.update-date'));
    }

    /**
     * 30/08/2026 — hiring is a super-admin act.
     *
     * `employees.create` mints a login (EmployeeObserver) and, for the
     * « Responsable de système » catégorie, a super-admin — so no preset
     * creates staff. Managing an existing file (`employees.update`) stays
     * with the roles that run one.
     */
    public function test_no_role_may_create_an_employee(): void
    {
        foreach (Role::with('permissions')->get() as $role) {
            $this->assertFalse(
                $role->hasPermissionTo('employees.create'),
                "Role [{$role->name}] must not create employees",
            );
        }

        foreach (['director', 'hr-manager', 'administrative-manager'] as $name) {
            $this->assertTrue(Role::findByName($name)->hasPermissionTo('employees.update'), $name);
        }
    }

    /**
     * The five management roles share the front-desk base and add back the
     * finance EDITS a manager arbitrates — `expenses.update` above all.
     * The front-desk roles themselves must NOT hold them.
     */
    public function test_management_roles_add_the_finance_edits_the_front_desk_lacks(): void
    {
        $edits = ['expenses.update', 'refunds.update', 'cheques.update', 'cash-transfers.update'];

        foreach (['director', 'operations-director', 'financial-director',
            'pedagogical-director', 'hr-manager'] as $name) {
            $role = Role::findByName($name);

            foreach ($edits as $permission) {
                $this->assertTrue($role->hasPermissionTo($permission), "[$name] should hold [$permission]");
            }

            // They build on the same operational base.
            $this->assertTrue($role->hasPermissionTo('registrations.update'), $name);
            $this->assertTrue($role->hasPermissionTo('attendance.update'), $name);
        }

        foreach (['consultant', 'administrative-assistant'] as $name) {
            $role = Role::findByName($name);

            foreach ($edits as $permission) {
                $this->assertFalse($role->hasPermissionTo($permission), "[$name] must not hold [$permission]");
            }
        }
    }

    /**
     * 31/08/2026 — the five management roles READ « Comptes de caisse ».
     *
     * It is not a global view for them: GetComptesCaisse follows the top-bar
     * centre like every other screen, and « Tous les centres » is
     * super-admin-only by construction (GLOBAL_CENTER_ACCESS is un-grantable),
     * so a manager sees exactly the centres assigned on their employee form.
     * Creating or editing an account stays super-admin-only.
     */
    public function test_management_roles_read_the_cash_accounts_tab(): void
    {
        $readers = ['director', 'operations-director', 'financial-director',
            'pedagogical-director', 'hr-manager'];

        foreach ($readers as $name) {
            $role = Role::findByName($name);

            $this->assertTrue($role->hasPermissionTo('cash-accounts.view'), $name);

            // …but never a global reach: centre scope comes from the
            // employee form, never from a role.
            $this->assertFalse($role->hasPermissionTo(PermissionRegistry::GLOBAL_CENTER_ACCESS), $name);
        }

        foreach (Role::with('permissions')->get() as $role) {
            $held = $role->permissions->pluck('name')->all();

            if (! in_array($role->name, $readers, true)) {
                $this->assertNotContains('cash-accounts.view', $held, $role->name);
            }

            // Nobody creates or edits an account by hand.
            $this->assertEmpty(
                array_intersect($held, ['cash-accounts.create', 'cash-accounts.update']),
                "Role [{$role->name}] must not write a cash account",
            );
        }
    }

    /**
     * The rule: only super-admin deletes. Enforced by
     * PermissionRegistry::superAdminOnly(), which every preset is filtered
     * through — so a future `*.delete` permission is locked down the moment
     * it is registered, without touching a single role.
     */
    public function test_no_role_may_delete_anything(): void
    {
        $deletes = array_values(array_filter(
            PermissionRegistry::names(),
            static fn (string $name): bool => str_ends_with($name, '.delete'),
        ));

        $this->assertNotEmpty($deletes);

        foreach (Role::with('permissions')->get() as $role) {
            $held = $role->permissions->pluck('name')->all();

            $this->assertEmpty(
                array_intersect($held, $deletes),
                "Role [{$role->name}] must not hold a delete permission",
            );
        }

        // …yet a super-admin still deletes, via Gate::before.
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->assertTrue($user->can('students.delete'));
        $this->assertTrue($user->can('employees.delete'));
    }

    /**
     * A director holds the broadest preset in the app, and still cannot
     * reach any of the abilities reserved for the Gate::before bypass.
     */
    public function test_super_admin_only_abilities_reach_no_role(): void
    {
        foreach (Role::with('permissions')->get() as $role) {
            $held = $role->permissions->pluck('name')->all();

            $this->assertEmpty(
                array_intersect($held, PermissionRegistry::superAdminOnly()),
                "Role [{$role->name}] must not hold a super-admin-only permission",
            );
        }

        $director = Role::findByName('director');
        $this->assertFalse($director->hasPermissionTo('expenses.approve'));
        $this->assertFalse($director->hasPermissionTo('system-settings.update'));
        // ⚠ `cash-accounts.view` is NOT asserted false here any more: the
        // READ was released to the five management roles on 31/08/2026 (see
        // test_management_roles_read_the_cash_accounts_tab). Writing one is
        // still out of reach.
        $this->assertFalse($director->hasPermissionTo('cash-accounts.create'));
        $this->assertFalse($director->hasPermissionTo('cash-accounts.update'));
        $this->assertFalse($director->hasPermissionTo('banks.view'));
    }

    /**
     * Every job title the Employees form offers has a matching role, so a
     * newly hired employee can always be granted one. « Autre » is the
     * deliberate exception (no defined post ⇒ no access).
     */
    public function test_every_employee_category_has_a_matching_default_role(): void
    {
        foreach (Employee::CATEGORIES as $categorie) {
            $role = PermissionRegistry::defaultRoleFor($categorie);

            if ($categorie === Employee::CATEGORIE_AUTRE) {
                // « Autre » is the deliberate exception: no defined post ⇒ no
                // role, no access, until one is granted by hand.
                $this->assertNull($role);

                continue;
            }

            $this->assertNotNull($role, "Catégorie [$categorie] has no default role");

            if ($categorie === Employee::CATEGORIE_RESPONSABLE_SYSTEME) {
                // The ONLY catégorie mapped to super-admin (not a preset).
                $this->assertSame(Role::SUPER_ADMIN, $role);

                continue;
            }

            $this->assertNotSame(Role::SUPER_ADMIN, $role, "Only Responsable de système may map to super-admin");
            $this->assertArrayHasKey(
                $role,
                PermissionRegistry::roles(),
                "Catégorie [$categorie] maps to unknown role [$role]",
            );
            $this->assertNotNull(Role::findByName($role));
        }

        $this->assertNull(PermissionRegistry::defaultRoleFor('Nonexistent job title'));
    }

    public function test_a_user_can_receive_a_role_and_its_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $this->assertTrue($user->can('groups.view'));
        $this->assertTrue($user->can('students.view'));
        $this->assertFalse($user->can('payments.view'));
        $this->assertFalse($user->can('roles.view'));
    }

    public function test_super_admin_has_every_ability_via_gate_before(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);

        // super-admin has ZERO synced permissions…
        $this->assertSame(0, Role::findByName(Role::SUPER_ADMIN)->permissions()->count());

        // …yet passes every check, including abilities that don't exist.
        $this->assertTrue($user->can('roles.delete'));
        $this->assertTrue($user->can('cash-transfers.validate'));
        $this->assertTrue($user->can('some.future-permission'));
    }
}
