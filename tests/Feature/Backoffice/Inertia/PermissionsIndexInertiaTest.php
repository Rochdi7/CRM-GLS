<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inertia;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Inertia/React pilot module (docs/inertia-react-migration-plan.md §6).
 * Verifies the migrated PermissionController still enforces the exact same
 * authorization as the Blade version, and now also returns the expected
 * Inertia component/props contract.
 */
final class PermissionsIndexInertiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('backoffice.permissions.index'))
            ->assertRedirect(route('backoffice.login'));
    }

    public function test_user_without_permission_gets_403(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('backoffice.permissions.index'))
            ->assertForbidden();
    }

    public function test_authorized_user_receives_expected_inertia_page(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('permissions.view');

        $this->actingAs($user)
            ->get(route('backoffice.permissions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Permissions/Index')
                ->has('groups')
                ->has('seededCount')
                ->where('seededCount', fn (int $count) => $count > 0)
            );
    }

    public function test_shared_props_include_auth_and_permissions(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('permissions.view');

        $this->actingAs($user)
            ->get(route('backoffice.permissions.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.id', $user->id)
                ->where('auth.permissions', fn (Collection $permissions) => $permissions->contains('permissions.view'))
            );
    }
}
