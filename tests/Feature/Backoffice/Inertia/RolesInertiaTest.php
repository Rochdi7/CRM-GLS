<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inertia;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Roles & Permissions — Inertia/React migration (Phase 7). Covers the new
 * App\Http\Controllers\Backoffice\Roles\RoleController endpoints, mirroring
 * the ground truth already proven by RoleManagementLivewireTest /
 * RoleManagementAuthorizationTest against the (still-kept-for-rollback)
 * Livewire components.
 */
final class RolesInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);

        return $user;
    }

    public function test_index_exposes_roles_with_protected_flag_and_counts(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get(route('backoffice.roles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // shouldExist: false — the React page component itself is
                // built by the parallel frontend agent (Phase 7 split); this
                // test only verifies the backend's Inertia contract (props).
                ->component('Backoffice/Roles/Index', false)
                ->has('roles.data', fn (Assert $roles) => $roles->each(fn (Assert $role) => $role
                    ->hasAll(['id', 'name', 'label', 'isProtected', 'permissionsCount', 'usersCount'])
                )->etc())
            );
    }

    public function test_viewer_without_delete_permission_cannot_delete(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('roles.view');
        $target = Role::findByName('teacher');

        $this->actingAs($viewer);

        $this->delete(route('backoffice.roles.destroy', $target))->assertForbidden();

        $this->assertDatabaseHas('roles', ['name' => 'teacher']);
    }

    public function test_create_page_exposes_permission_catalog_grouped_by_module(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get(route('backoffice.roles.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Roles/Create', false)
                ->has('permissionGroups.Rôles')
            );
    }

    public function test_store_slugifies_name_and_syncs_permissions(): void
    {
        $this->actingAs($this->superAdmin());

        $this->post(route('backoffice.roles.store'), [
            'label' => 'Comptable',
            'name' => 'Accountant Role',
            'permissions' => ['payments.view', 'expenses.view'],
        ])->assertRedirect(route('backoffice.roles.index'));

        $role = Role::findByName('accountant-role');
        $this->assertNotNull($role);
        $this->assertSame('Comptable', $role->label);
        $this->assertEqualsCanonicalizing(
            ['payments.view', 'expenses.view'],
            $role->permissions()->pluck('name')->all(),
        );
    }

    public function test_store_rejects_permissions_outside_the_registry(): void
    {
        $this->actingAs($this->superAdmin());

        $this->post(route('backoffice.roles.store'), [
            'label' => 'Pirate',
            'name' => 'pirate',
            'permissions' => ['system.hack-everything'],
        ])->assertSessionHasErrors(['permissions.0']);

        $this->assertDatabaseMissing('roles', ['name' => 'pirate']);
    }

    public function test_edit_page_is_hard_403_for_the_protected_role(): void
    {
        $this->actingAs($this->superAdmin());
        $protected = Role::findByName(Role::SUPER_ADMIN);

        $this->get(route('backoffice.roles.edit', $protected))->assertForbidden();
    }

    public function test_edit_page_exposes_selected_permissions_and_catalog(): void
    {
        $this->actingAs($this->superAdmin());
        $role = Role::findByName('marketing-manager');

        $this->get(route('backoffice.roles.edit', $role))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Roles/Edit', false)
                ->where('role.name', 'marketing-manager')
                ->has('selectedPermissions')
                ->has('permissionGroups')
            );
    }

    public function test_update_never_writes_a_submitted_name_change(): void
    {
        $this->actingAs($this->superAdmin());
        $role = Role::findByName('marketing-manager');

        $this->put(route('backoffice.roles.update', $role), [
            'label' => 'Responsable marketing',
            'name' => 'totally-different-name',
            'permissions' => ['dashboard.view', 'students.view'],
        ])->assertRedirect(route('backoffice.roles.index'));

        $role->refresh();
        $this->assertSame('marketing-manager', $role->name);
        $this->assertSame('Responsable marketing', $role->label);
        $this->assertEqualsCanonicalizing(
            ['dashboard.view', 'students.view'],
            $role->permissions()->pluck('name')->all(),
        );
    }

    public function test_update_is_hard_403_for_the_protected_role(): void
    {
        $this->actingAs($this->superAdmin());
        $protected = Role::findByName(Role::SUPER_ADMIN);

        $this->put(route('backoffice.roles.update', $protected), [
            'label' => 'Hacked',
            'permissions' => [],
        ])->assertForbidden();
    }

    public function test_destroy_blocks_protected_role_with_a_soft_validation_error(): void
    {
        $this->actingAs($this->superAdmin());
        $protected = Role::findByName(Role::SUPER_ADMIN);

        $this->delete(route('backoffice.roles.destroy', $protected))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('roles', ['name' => Role::SUPER_ADMIN]);
    }

    public function test_destroy_blocks_a_role_assigned_to_users(): void
    {
        $this->actingAs($this->superAdmin());

        $user = User::factory()->create();
        $user->assignRole('teacher');

        $this->delete(route('backoffice.roles.destroy', Role::findByName('teacher')))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('roles', ['name' => 'teacher']);
    }

    public function test_destroy_deletes_an_unused_custom_role(): void
    {
        $this->actingAs($this->superAdmin());
        $role = Role::create(['name' => 'temp-role', 'label' => 'Temporaire', 'guard_name' => 'web']);

        $this->delete(route('backoffice.roles.destroy', $role))
            ->assertRedirect(route('backoffice.roles.index'));

        $this->assertDatabaseMissing('roles', ['name' => 'temp-role']);
    }
}
