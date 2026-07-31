<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Authorization;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SuperAdminProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_gate_before_grants_abilities_only_to_super_admins(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        $director = User::factory()->create();
        $director->assignRole('director');

        $this->assertTrue($admin->can('roles.delete'));
        $this->assertFalse($director->can('roles.delete'));
        $this->assertFalse(User::factory()->create()->can('dashboard.view'));
    }

    public function test_assign_super_admin_command_assigns_the_role(): void
    {
        $user = User::factory()->create(['email' => 'chef@gls.test']);

        $this->artisan('auth:assign-super-admin', ['email' => 'chef@gls.test'])
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->hasRole(Role::SUPER_ADMIN));
    }

    public function test_assign_super_admin_command_fails_for_unknown_email(): void
    {
        $this->artisan('auth:assign-super-admin', ['email' => 'ghost@gls.test'])
            ->assertFailed();
    }

    public function test_non_super_admin_users_do_not_bypass_unknown_abilities(): void
    {
        $director = User::factory()->create();
        $director->assignRole('director');

        $this->assertFalse($director->can('some.future-permission'));
    }
}
