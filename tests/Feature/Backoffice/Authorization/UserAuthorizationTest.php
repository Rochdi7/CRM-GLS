<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Authorization;

use App\Livewire\Backoffice\Users\ManageAuthorization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class UserAuthorizationTest extends TestCase
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

    public function test_role_assignment_is_persisted(): void
    {
        $this->actingAs($this->superAdmin());
        $target = User::factory()->create();

        Livewire::test(ManageAuthorization::class, ['user' => $target])
            ->set('selectedRoles', ['teacher'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('backoffice.users.index'));

        $this->assertTrue($target->fresh()->hasRole('teacher'));
    }

    public function test_unknown_role_names_are_rejected(): void
    {
        $this->actingAs($this->superAdmin());
        $target = User::factory()->create();

        Livewire::test(ManageAuthorization::class, ['user' => $target])
            ->set('selectedRoles', ['made-up-role'])
            ->call('save')
            ->assertHasErrors('roles');

        $this->assertSame(0, $target->fresh()->roles()->count());
    }

    public function test_only_a_super_admin_may_grant_super_admin(): void
    {
        $director = User::factory()->create();
        $director->assignRole('director'); // has users.assign-roles
        $target = User::factory()->create();

        $this->actingAs($director);

        Livewire::test(ManageAuthorization::class, ['user' => $target])
            ->set('selectedRoles', [Role::SUPER_ADMIN])
            ->call('save')
            ->assertHasErrors('roles');

        $this->assertFalse($target->fresh()->hasRole(Role::SUPER_ADMIN));
    }

    public function test_direct_permissions_require_the_dedicated_permission(): void
    {
        $director = User::factory()->create();
        $director->assignRole('director'); // users.assign-roles but NOT users.assign-permissions
        $target = User::factory()->create();

        $this->actingAs($director);

        Livewire::test(ManageAuthorization::class, ['user' => $target])
            ->set('selectedRoles', [])
            ->set('directPermissions', ['payments.view'])
            ->call('save')
            ->assertHasErrors('permissions');

        $this->assertFalse($target->fresh()->hasDirectPermission('payments.view'));
    }

    public function test_super_admin_can_grant_direct_permissions(): void
    {
        $this->actingAs($this->superAdmin());
        $target = User::factory()->create();

        Livewire::test(ManageAuthorization::class, ['user' => $target])
            ->set('selectedRoles', ['teacher'])
            ->set('directPermissions', ['payments.view'])
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $target->fresh();
        $this->assertTrue($fresh->hasDirectPermission('payments.view'));
        $this->assertTrue($fresh->hasRole('teacher'));
    }

    public function test_unknown_direct_permissions_are_rejected(): void
    {
        $this->actingAs($this->superAdmin());
        $target = User::factory()->create();

        Livewire::test(ManageAuthorization::class, ['user' => $target])
            ->set('directPermissions', ['system.own-everything'])
            ->call('save')
            ->assertHasErrors('permissions');
    }

    public function test_authorization_changes_are_audit_logged(): void
    {
        $actor = $this->superAdmin();
        $this->actingAs($actor);
        $target = User::factory()->create();

        Livewire::test(ManageAuthorization::class, ['user' => $target])
            ->set('selectedRoles', ['teacher'])
            ->call('save');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'authorization',
            'causer_id' => $actor->id,
            'subject_id' => $target->id,
        ]);
    }
}
