<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Authorization;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\User;
use App\Support\Authorization\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Audit 05/10/2026: `superAdminOnly()` abilities (deletes, expense approval,
 * system settings, payment re-dating…) are kept off every ROLE by
 * `PermissionRegistry::matrix()` and refused by the Rôles form — but the
 * Autorisations screen offered every one of them as a DIRECT permission to
 * anybody holding `users.assign-permissions`. A director could therefore
 * hand a colleague `expenses.approve` or `payments.delete`, which no role,
 * his own included, may carry. "Delegated by hand to one user" (CLAUDE.md
 * §16) is a SUPER-ADMIN's decision: the service now refuses a reserved
 * direct grant from anyone else, and the picker draws it locked.
 */
final class ReservedDirectPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
    }

    private function userIn(?string $role = null, string ...$directPermissions): User
    {
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        if ($role !== null) {
            $user->assignRole($role);
        }
        foreach ($directPermissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user->fresh();
    }

    /** A director who was also handed the direct-permission ability. */
    private function directorWhoAssignsPermissions(): User
    {
        return $this->userIn('director', 'users.assign-permissions');
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);

        return $user->fresh();
    }

    public function test_a_non_super_admin_cannot_grant_a_reserved_permission_directly(): void
    {
        $actor = $this->directorWhoAssignsPermissions();
        $target = $this->userIn('accountant');

        $this->assertContains('expenses.approve', PermissionRegistry::superAdminOnly());

        $this->actingAs($actor)
            ->from(route('backoffice.users.authorization.edit', $target))
            ->put(route('backoffice.users.authorization.update', $target), [
                'roles' => ['accountant'],
                'directPermissions' => ['expenses.approve'],
            ])
            ->assertRedirect(route('backoffice.users.authorization.edit', $target))
            ->assertSessionHasErrors('permissions');

        $this->assertFalse($target->fresh()->hasDirectPermission('expenses.approve'));
        $this->assertFalse($target->fresh()->can('expenses.approve'));
    }

    public function test_a_non_super_admin_still_grants_an_ordinary_direct_permission(): void
    {
        $actor = $this->directorWhoAssignsPermissions();
        $target = $this->userIn('accountant');

        $this->actingAs($actor)
            ->put(route('backoffice.users.authorization.update', $target), [
                'roles' => ['accountant'],
                'directPermissions' => ['employees.view'],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('backoffice.users.index'));

        $this->assertTrue($target->fresh()->hasDirectPermission('employees.view'));
    }

    public function test_a_super_admin_delegates_a_reserved_permission_by_hand(): void
    {
        $target = $this->userIn('accountant');

        $this->actingAs($this->superAdmin())
            ->put(route('backoffice.users.authorization.update', $target), [
                'roles' => ['accountant'],
                'directPermissions' => ['expenses.approve'],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('backoffice.users.index'));

        $this->assertTrue($target->fresh()->can('expenses.approve'));
    }

    public function test_a_reserved_permission_already_delegated_survives_a_directors_later_edit(): void
    {
        // Delegated by a super-admin earlier…
        $target = $this->userIn('accountant', 'expenses.approve');
        $actor = $this->directorWhoAssignsPermissions();

        // …then a director adds a role and resubmits the form as it stands.
        $this->actingAs($actor)
            ->put(route('backoffice.users.authorization.update', $target), [
                'roles' => ['accountant', 'consultant'],
                'directPermissions' => ['expenses.approve'],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('backoffice.users.index'));

        $fresh = $target->fresh();
        $this->assertTrue($fresh->hasRole('consultant'));
        $this->assertTrue($fresh->hasDirectPermission('expenses.approve'));
    }

    public function test_the_picker_locks_reserved_permissions_for_a_non_super_admin_only(): void
    {
        $target = $this->userIn('accountant', 'payments.update-date');

        // Director: every reserved ability locked, except the one the target
        // already holds (a later edit may keep it).
        $this->actingAs($this->directorWhoAssignsPermissions())
            ->get(route('backoffice.users.authorization.edit', $target))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canAssignDirect', true)
                ->where('lockedPermissions', fn ($locked) => collect($locked)->contains('expenses.approve')
                    && ! collect($locked)->contains('payments.update-date')
                    && ! collect($locked)->contains('employees.view')));

        // Super-admin: nothing locked — delegating by hand is his call.
        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.users.authorization.edit', $target))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('lockedPermissions', []));
    }
}
