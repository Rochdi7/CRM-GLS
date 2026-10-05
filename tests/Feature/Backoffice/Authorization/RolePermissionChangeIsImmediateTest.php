<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Authorization;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ReferentialDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Reported 05/10/2026: after a role's permissions were edited on the
 * « Modifier le rôle » screen, the affected user still saw his old sidebar.
 * The cause was a stale page in his browser (the user held another role
 * entirely), not a stale server — but nothing pinned that. This asserts the
 * contract the operator relies on: a permission added to or removed from a
 * role, or a role swapped on the Autorisations screen, takes effect on the
 * affected user's VERY NEXT request — no re-login, no `cache:clear`, no
 * `permission:cache-reset`.
 *
 * Both halves matter: the shared `auth.permissions` prop is what draws the
 * sidebar (§5), and the route middleware is what actually enforces (§16).
 * The spatie permission cache (24 h TTL, `database` store in production)
 * sits between the write and the next read, so this is exactly the place a
 * regression would hide.
 *
 * Every simulated request loads the user afresh (`asUser()`), the way a real
 * request does: reusing one in-memory model would keep its `roles.permissions`
 * relation from before the save and fail for a reason production never has.
 */
final class RolePermissionChangeIsImmediateTest extends TestCase
{
    use RefreshDatabase;

    private const ROLE = 'accountant';

    /** Held by the accountant preset — the sidebar's « Gestion des paiements ». */
    private const HELD = 'payments.view';

    /** NOT held by the accountant preset, yet grantable — « Rôles & Permissions ». */
    private const NOT_HELD = 'roles.view';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ReferentialDataSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);

        return $user;
    }

    private function accountantId(): int
    {
        $centre = Etablissement::query()->firstOrFail();
        $user = User::factory()->create();

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $centre->id,
        ]);
        $employee->syncEtablissements([$centre->id], $centre->id);

        $user->syncRoles([self::ROLE]);

        return $user->id;
    }

    /** A fresh model per request, exactly like a real HTTP request. */
    private function asUser(int $id): static
    {
        return $this->actingAs(User::query()->findOrFail($id));
    }

    /** The role's current permission names, as the edit form would submit them. */
    private function currentPermissions(): array
    {
        return Role::findByName(self::ROLE)->permissions()->pluck('name')->values()->all();
    }

    private function saveRole(User $admin, array $permissions): void
    {
        $this->actingAs($admin)
            ->put(route('backoffice.roles.update', Role::findByName(self::ROLE)), [
                'label' => 'Comptable',
                'permissions' => $permissions,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();
    }

    private function assertSidebarPermission(int $userId, string $permission, bool $expected): void
    {
        $this->asUser($userId)
            ->get(route('backoffice.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.permissions', fn ($perms) => collect($perms)->contains($permission) === $expected));
    }

    public function test_a_permission_removed_from_a_role_is_gone_on_the_users_next_request(): void
    {
        $admin = $this->superAdmin();
        $userId = $this->accountantId();

        $this->assertContains(self::HELD, $this->currentPermissions());

        // Warm the spatie cache through real requests, the way production does.
        $this->assertSidebarPermission($userId, self::HELD, true);
        // Encaissements redirects once to its canonical default-filter URL.
        $this->asUser($userId)->followingRedirects()->get(route('backoffice.encaissements.index'))->assertOk();

        $this->saveRole($admin, array_values(array_diff($this->currentPermissions(), [self::HELD])));

        // Next request, same user, no re-login: the sidebar prop no longer
        // carries it AND the route refuses.
        $this->assertSidebarPermission($userId, self::HELD, false);
        $this->asUser($userId)->get(route('backoffice.encaissements.index'))->assertForbidden();
    }

    public function test_a_permission_added_to_a_role_is_there_on_the_users_next_request(): void
    {
        $admin = $this->superAdmin();
        $userId = $this->accountantId();

        $this->assertNotContains(self::NOT_HELD, $this->currentPermissions());
        $this->assertSidebarPermission($userId, self::NOT_HELD, false);
        $this->asUser($userId)->get(route('backoffice.roles.index'))->assertForbidden();

        $this->saveRole($admin, [...$this->currentPermissions(), self::NOT_HELD]);

        $this->assertSidebarPermission($userId, self::NOT_HELD, true);
        $this->asUser($userId)->get(route('backoffice.roles.index'))->assertOk();
    }

    public function test_a_role_swapped_on_the_authorizations_screen_is_immediate_too(): void
    {
        $admin = $this->superAdmin();
        $userId = $this->accountantId();

        $this->assertSidebarPermission($userId, self::HELD, true);
        // Encaissements redirects once to its canonical default-filter URL.
        $this->asUser($userId)->followingRedirects()->get(route('backoffice.encaissements.index'))->assertOk();

        // Swap the user to `teacher` the way the Autorisations screen does.
        $this->actingAs($admin)
            ->put(route('backoffice.users.authorization.update', User::query()->findOrFail($userId)), [
                'roles' => ['teacher'],
                'directPermissions' => [],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSidebarPermission($userId, self::HELD, false);
        $this->asUser($userId)->get(route('backoffice.encaissements.index'))->assertForbidden();
    }
}
