<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Authorization;

use App\Livewire\Backoffice\Roles\RoleForm;
use App\Livewire\Backoffice\Roles\RolesIndex;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class RoleManagementLivewireTest extends TestCase
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

    public function test_unauthorized_user_cannot_mount_roles_index(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(RolesIndex::class)->assertForbidden();
    }

    public function test_viewer_cannot_call_the_delete_mutation(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('roles.view');
        $target = Role::findByName('teacher');

        $this->actingAs($viewer);

        Livewire::test(RolesIndex::class)
            ->call('deleteRole', $target->id)
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['name' => 'teacher']);
    }

    public function test_authorized_user_can_create_a_role(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(RoleForm::class)
            ->set('label', 'Comptable')
            ->set('name', 'accountant')
            ->set('selected', ['payments.view', 'expenses.view'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('backoffice.roles.index'));

        $role = Role::findByName('accountant');
        $this->assertSame('Comptable', $role->label);
        $this->assertEqualsCanonicalizing(
            ['payments.view', 'expenses.view'],
            $role->permissions()->pluck('name')->all(),
        );
    }

    public function test_validation_rejects_permissions_outside_the_registry(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(RoleForm::class)
            ->set('label', 'Pirate')
            ->set('name', 'pirate')
            ->set('selected', ['system.hack-everything'])
            ->call('save')
            ->assertHasErrors(['selected.0']);

        $this->assertDatabaseMissing('roles', ['name' => 'pirate']);
    }

    public function test_authorized_user_can_update_role_permissions(): void
    {
        $this->actingAs($this->superAdmin());
        $role = Role::findByName('marketing-manager');

        Livewire::test(RoleForm::class, ['role' => $role])
            ->set('label', 'Responsable marketing')
            ->set('selected', ['dashboard.view', 'students.view'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEqualsCanonicalizing(
            ['dashboard.view', 'students.view'],
            $role->fresh()->permissions()->pluck('name')->all(),
        );
    }

    public function test_the_super_admin_role_cannot_be_edited(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(RoleForm::class, ['role' => Role::findByName(Role::SUPER_ADMIN)])
            ->assertForbidden();
    }

    public function test_the_super_admin_role_cannot_be_deleted(): void
    {
        $this->actingAs($this->superAdmin());
        $protected = Role::findByName(Role::SUPER_ADMIN);

        Livewire::test(RolesIndex::class)
            ->call('deleteRole', $protected->id)
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('roles', ['name' => Role::SUPER_ADMIN]);
    }

    public function test_a_role_assigned_to_users_cannot_be_deleted(): void
    {
        $this->actingAs($this->superAdmin());

        $user = User::factory()->create();
        $user->assignRole('teacher');

        Livewire::test(RolesIndex::class)
            ->call('deleteRole', Role::findByName('teacher')->id)
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('roles', ['name' => 'teacher']);
    }

    public function test_an_unused_custom_role_can_be_deleted(): void
    {
        $this->actingAs($this->superAdmin());
        $role = Role::create(['name' => 'temp-role', 'label' => 'Temporaire', 'guard_name' => 'web']);

        Livewire::test(RolesIndex::class)
            ->call('deleteRole', $role->id)
            ->assertRedirect(route('backoffice.roles.index'));

        $this->assertDatabaseMissing('roles', ['name' => 'temp-role']);
    }
}
