<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Authorization;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RoleManagementAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('backoffice.roles.index'))
            ->assertRedirect(route('backoffice.login'));
    }

    public function test_user_without_any_permission_gets_403(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('backoffice.roles.index'))
            ->assertForbidden();
    }

    public function test_teacher_cannot_access_role_management(): void
    {
        $this->actingAs($this->userWithRole('teacher'))
            ->get(route('backoffice.roles.index'))
            ->assertForbidden();
    }

    public function test_administrative_assistant_cannot_access_role_management(): void
    {
        $this->actingAs($this->userWithRole('administrative-assistant'))
            ->get(route('backoffice.roles.index'))
            ->assertForbidden();
    }

    public function test_director_can_view_roles_but_not_create_them(): void
    {
        $director = $this->userWithRole('director');

        $this->actingAs($director)->get(route('backoffice.roles.index'))->assertOk();
        $this->actingAs($director)->get(route('backoffice.permissions.index'))->assertOk();
        $this->actingAs($director)->get(route('backoffice.users.index'))->assertOk();

        $this->actingAs($director)->get(route('backoffice.roles.create'))->assertForbidden();
    }

    public function test_user_with_only_view_permission_cannot_reach_create_page(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('roles.view');

        $this->actingAs($user)->get(route('backoffice.roles.index'))->assertOk();
        $this->actingAs($user)->get(route('backoffice.roles.create'))->assertForbidden();
    }

    public function test_super_admin_can_access_the_whole_authorization_module(): void
    {
        $admin = $this->userWithRole(Role::SUPER_ADMIN);
        $target = User::factory()->create();

        $this->actingAs($admin)->get(route('backoffice.roles.index'))->assertOk();
        $this->actingAs($admin)->get(route('backoffice.roles.create'))->assertOk();
        $this->actingAs($admin)->get(route('backoffice.permissions.index'))->assertOk();
        $this->actingAs($admin)->get(route('backoffice.users.index'))->assertOk();
        $this->actingAs($admin)
            ->get(route('backoffice.users.authorization.edit', $target))
            ->assertOk();
    }

    public function test_existing_module_routes_are_now_permission_protected(): void
    {
        // Teacher holds students.view but NOT payments.view / centers.view.
        $teacher = $this->userWithRole('teacher');

        $this->actingAs($teacher)->get(route('backoffice.encaissements.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('backoffice.etablissements.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('backoffice.caisse-transfers.index'))->assertForbidden();
    }
}
