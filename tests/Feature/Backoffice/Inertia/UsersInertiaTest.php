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
 * Users & user-authorization — Inertia/React migration (Phase 7). Covers
 * the new App\Http\Controllers\Backoffice\Users\{UserController,
 * UserAuthorizationController} endpoints, mirroring the ground truth already
 * proven by UsersCrudTest / UserAuthorizationTest against the (still-kept-
 * for-rollback) Livewire components.
 */
final class UsersInertiaTest extends TestCase
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

    private function manager(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['users.view', 'users.assign-roles']);

        return $user->fresh();
    }

    // --- Index ---------------------------------------------------------

    public function test_index_exposes_paginated_users(): void
    {
        $this->actingAs($this->manager());
        User::factory()->count(3)->create();

        $this->get(route('backoffice.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Users/Index')
                ->has('users.data', fn (Assert $users) => $users->each(fn (Assert $user) => $user
                    ->hasAll(['id', 'name', 'email', 'username', 'isActive', 'mustChangePassword', 'roles', 'employee'])
                )->etc())
                ->has('filters')
                ->has('perPageOptions')
            );
    }

    public function test_index_search_filters_by_name_email_or_username(): void
    {
        $this->actingAs($this->manager());
        User::factory()->create(['name' => 'Alpha Match', 'email' => 'zzz@gls.test', 'username' => 'zzz']);
        User::factory()->create(['name' => 'Nobody', 'email' => 'nobody@gls.test', 'username' => 'nobody']);

        $this->get(route('backoffice.users.index', ['search' => 'Alpha']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Users/Index')
                ->where('users.data.0.name', 'Alpha Match')
                ->count('users.data', 1)
            );
    }

    public function test_index_requires_users_view_permission(): void
    {
        $viewer = User::factory()->create();
        $this->actingAs($viewer);

        $this->get(route('backoffice.users.index'))->assertForbidden();
    }

    // --- Update ----------------------------------------------------------

    public function test_a_user_can_be_edited(): void
    {
        $this->actingAs($this->manager());
        $target = User::factory()->create(['is_active' => true]);

        $this->put(route('backoffice.users.update', $target), [
            'name' => 'Renamed User',
            'email' => $target->email,
            'username' => $target->username,
            'is_active' => false,
        ])->assertRedirect();

        $target->refresh();
        $this->assertSame('Renamed User', $target->name);
        $this->assertFalse($target->is_active);
    }

    public function test_email_must_stay_unique(): void
    {
        $this->actingAs($this->manager());
        $a = User::factory()->create(['email' => 'a@gls.test']);
        $b = User::factory()->create(['email' => 'b@gls.test']);

        $this->put(route('backoffice.users.update', $b), [
            'name' => $b->name,
            'email' => 'a@gls.test',
            'username' => $b->username,
            'is_active' => true,
        ])->assertSessionHasErrors('email');

        $this->assertSame('b@gls.test', $b->fresh()->email);
        $this->assertNotNull($a); // keep both fetched for clarity
    }

    public function test_update_requires_the_management_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('users.view'); // can list, cannot edit
        $target = User::factory()->create();
        $this->actingAs($viewer);

        $this->put(route('backoffice.users.update', $target), [
            'name' => 'Hacked',
            'email' => $target->email,
            'username' => $target->username,
            'is_active' => true,
        ])->assertForbidden();
    }

    // --- Password regeneration --------------------------------------------

    public function test_password_can_be_regenerated_and_returned_once(): void
    {
        $this->actingAs($this->manager());
        $target = User::factory()->create(['must_change_password' => false]);
        $oldHash = $target->password;

        $response = $this->post(route('backoffice.users.regenerate-password', $target));

        $response->assertRedirect();
        $this->assertTrue(session()->has('regeneratedPassword'));
        $plain = session()->get('regeneratedPassword');
        $this->assertIsString($plain);
        $this->assertGreaterThanOrEqual(12, strlen($plain));

        $target->refresh();
        $this->assertNotSame($oldHash, $target->password);
        $this->assertTrue($target->must_change_password);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'authorization',
            'subject_id' => $target->id,
            'description' => 'password regenerated',
        ]);
    }

    public function test_regenerated_password_is_shown_at_most_once(): void
    {
        $this->actingAs($this->manager());
        $target = User::factory()->create();

        $this->post(route('backoffice.users.regenerate-password', $target))
            ->assertRedirect();

        // The redirect's own target request is the one render this secret is
        // meant for.
        $this->get(route('backoffice.users.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Users/Index', false)
                ->where('flash.regeneratedPassword', fn ($value) => is_string($value) && strlen($value) >= 12)
            );

        // A second, later request in the same session must NOT see it again
        // — regression guard for the flash-lifecycle bug where session()->get()
        // (instead of ->pull()) let a one-time secret resurface on a
        // subsequent unrelated Inertia visit.
        $this->get(route('backoffice.users.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Users/Index', false)
                ->where('flash.regeneratedPassword', null)
            );
    }

    public function test_regenerate_password_requires_the_management_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('users.view');
        $target = User::factory()->create();
        $this->actingAs($viewer);

        $this->post(route('backoffice.users.regenerate-password', $target))->assertForbidden();
    }

    // --- Authorization edit/update ------------------------------------------

    public function test_authorization_edit_exposes_catalog_and_current_assignment(): void
    {
        $this->actingAs($this->superAdmin());
        $target = User::factory()->create();
        $target->assignRole('teacher');

        $this->get(route('backoffice.users.authorization.edit', $target))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Users/Authorization')
                ->where('targetUser.id', $target->id)
                ->where('selectedRoles', ['teacher'])
                ->has('roles')
                ->has('roleLabels')
                ->has('groups')
                ->has('totalPermissions')
                ->where('isSuperAdmin', false)
                ->where('canAssignDirect', true)
            );
    }

    public function test_authorization_edit_requires_users_assign_roles(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('users.view');
        $target = User::factory()->create();
        $this->actingAs($viewer);

        $this->get(route('backoffice.users.authorization.edit', $target))->assertForbidden();
    }

    public function test_role_assignment_is_persisted(): void
    {
        $this->actingAs($this->superAdmin());
        $target = User::factory()->create();

        $this->put(route('backoffice.users.authorization.update', $target), [
            'roles' => ['teacher'],
            'directPermissions' => [],
        ])->assertRedirect(route('backoffice.users.index'));

        $this->assertTrue($target->fresh()->hasRole('teacher'));
    }

    public function test_unknown_role_names_are_rejected(): void
    {
        $this->actingAs($this->superAdmin());
        $target = User::factory()->create();

        $this->put(route('backoffice.users.authorization.update', $target), [
            'roles' => ['made-up-role'],
            'directPermissions' => [],
        ])->assertSessionHasErrors('roles');

        $this->assertSame(0, $target->fresh()->roles()->count());
    }

    public function test_only_a_super_admin_may_grant_super_admin(): void
    {
        $director = User::factory()->create();
        $director->assignRole('director'); // has users.assign-roles
        $target = User::factory()->create();
        $this->actingAs($director);

        $this->put(route('backoffice.users.authorization.update', $target), [
            'roles' => [Role::SUPER_ADMIN],
            'directPermissions' => [],
        ])->assertSessionHasErrors('roles');

        $this->assertFalse($target->fresh()->hasRole(Role::SUPER_ADMIN));
    }

    public function test_direct_permissions_require_the_dedicated_permission(): void
    {
        $director = User::factory()->create();
        $director->assignRole('director'); // users.assign-roles but NOT users.assign-permissions
        $target = User::factory()->create();
        $this->actingAs($director);

        $this->put(route('backoffice.users.authorization.update', $target), [
            'roles' => [],
            'directPermissions' => ['payments.view'],
        ])->assertSessionHasErrors('permissions');

        $this->assertFalse($target->fresh()->hasDirectPermission('payments.view'));
    }

    public function test_super_admin_can_grant_direct_permissions(): void
    {
        $this->actingAs($this->superAdmin());
        $target = User::factory()->create();

        $this->put(route('backoffice.users.authorization.update', $target), [
            'roles' => ['teacher'],
            'directPermissions' => ['payments.view'],
        ])->assertRedirect(route('backoffice.users.index'));

        $fresh = $target->fresh();
        $this->assertTrue($fresh->hasDirectPermission('payments.view'));
        $this->assertTrue($fresh->hasRole('teacher'));
    }

    public function test_authorization_update_requires_users_assign_roles(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('users.view');
        $target = User::factory()->create();
        $this->actingAs($viewer);

        $this->put(route('backoffice.users.authorization.update', $target), [
            'roles' => ['teacher'],
            'directPermissions' => [],
        ])->assertForbidden();
    }

    public function test_authorization_changes_are_audit_logged(): void
    {
        $actor = $this->superAdmin();
        $this->actingAs($actor);
        $target = User::factory()->create();

        $this->put(route('backoffice.users.authorization.update', $target), [
            'roles' => ['teacher'],
            'directPermissions' => [],
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'authorization',
            'causer_id' => $actor->id,
            'subject_id' => $target->id,
        ]);
    }
}
