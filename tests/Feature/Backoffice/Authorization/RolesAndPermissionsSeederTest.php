<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Authorization;

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
        $this->assertTrue($director->hasPermissionTo('students.delete'));
        $this->assertTrue($director->hasPermissionTo('cash-transfers.validate'));
        $this->assertTrue($director->hasPermissionTo('centers.access-all'));
        $this->assertFalse($director->hasPermissionTo('roles.create'));
        $this->assertFalse($director->hasPermissionTo('roles.delete'));

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
